"""Integracioni test SEO <head> izlaza.  python3 -I tests/seo/check_head.py BASE_URL [--sections head,robots]"""
import re, sys, urllib.request, urllib.error, html

BASE = sys.argv[1].rstrip('/')
SECTIONS = (sys.argv[3] if len(sys.argv) > 3 and sys.argv[2] == '--sections' else 'head,robots').split(',')
UA = {'User-Agent': 'Mozilla/5.0 (compatible; ygv-seo-check)'}
fails = 0

def get(url):
    req = urllib.request.Request(url, headers=UA)
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            return r.status, r.read().decode('utf-8', 'replace')
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode('utf-8', 'replace')

def ok(name, cond, detail=''):
    global fails
    if cond:
        print(f'PASS  {name}')
    else:
        fails += 1
        print(f'FAIL  {name}  {detail}')

def first_loc(sitemap):
    st, body = get(f'{BASE}/{sitemap}')
    locs = re.findall(r'<loc>([^<]+)</loc>', body)
    return locs[0] if st == 200 and locs else None

def metas(body, attr, key):
    return re.findall(r'<meta\s+[^>]*%s=["\']%s["\'][^>]*>' % (attr, re.escape(key)), body)

def content(tag):
    m = re.search(r'content=(["\'])(.*?)\1', tag, re.S)
    return html.unescape(m.group(2)) if m else ''

pages = {
    'home': BASE + '/',
    'lista': first_loc('wp-sitemap-posts-voting_list-1.xml'),
    'stavka': first_loc('wp-sitemap-posts-voting_items-1.xml'),
    'kviz': first_loc('wp-sitemap-posts-quiz-1.xml'),
    'anketa': first_loc('wp-sitemap-posts-voting_poll-1.xml'),
    'showdown': first_loc('wp-sitemap-posts-yuv_showdown-1.xml'),
    'kategorija liste': first_loc('wp-sitemap-taxonomies-voting_list_category-1.xml'),
    'arhiva kvizova': BASE + '/kvizovi/',
    'arhiva lista': BASE + '/liste/',
}

if 'head' in SECTIONS:
    for name, url in pages.items():
        if not url:
            ok(f'{name}: URL pronađen u sitemap-u', False); continue
        st, b = get(url)
        ok(f'{name}: HTTP 200', st == 200, f'{st} {url}')
        d = metas(b, 'name', 'description')
        ok(f'{name}: tačno jedan meta description', len(d) == 1, f'{len(d)} {url}')
        ok(f'{name}: description nije prazan i ≤155', bool(d) and 0 < len(content(d[0])) <= 155, content(d[0]) if d else '')
        for p in ['og:title', 'og:description', 'og:image', 'og:url', 'og:type', 'og:locale', 'og:site_name']:
            ok(f'{name}: {p}', len(metas(b, 'property', p)) == 1, url)
        ok(f'{name}: twitter:card', len(metas(b, 'name', 'twitter:card')) == 1, url)
        ok(f'{name}: lang="sr-Latn"', '<html lang="sr-Latn"' in b, re.search(r'<html[^>]*>', b).group(0) if re.search(r'<html[^>]*>', b) else '')
        t = re.search(r'<title>(.*?)</title>', b, re.S)
        ok(f'{name}: <title> završava sa "| YugoVote"', bool(t) and html.unescape(t.group(1)).strip().endswith('| YugoVote'), t.group(1) if t else '')
    st, b = get(BASE + '/kvizovi/?difficulty=hard')
    c = re.findall(r'<link[^>]+rel=["\']canonical["\'][^>]*>', b)
    ok('arhiva sa parametrom: jedan canonical bez "?"', len(c) == 1 and '?' not in c[0], str(c))
    for name in ['lista', 'kviz']:
        st, b = get(pages[name])
        c = re.findall(r'<link[^>]+rel=["\']canonical["\'][^>]*>', b)
        ok(f'{name}: tačno jedan canonical', len(c) == 1, str(c))

print(f'\n{fails} FAIL' if fails else '\nSVE PASS')
sys.exit(1 if fails else 0)
