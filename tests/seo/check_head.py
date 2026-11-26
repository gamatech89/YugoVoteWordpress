"""Integracioni test SEO <head> izlaza.  python3 -I tests/seo/check_head.py BASE_URL [--sections head,robots]"""
import re, sys, json, urllib.request, urllib.error, html

BASE = sys.argv[1].rstrip('/')
SECTIONS = (sys.argv[3] if len(sys.argv) > 3 and sys.argv[2] == '--sections' else 'head,robots,h1,schema,landing').split(',')
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

def clean(fragment):
    s = re.sub(r'<img\b[^>]*\balt=(["\'])(.*?)\1[^>]*>', lambda m: ' ' + m.group(2) + ' ', fragment, flags=re.S)
    s = re.sub(r'<(svg|script|style)\b.*?</\1>', ' ', s, flags=re.S)
    s = re.sub(r'<[^>]+>', ' ', s)
    return re.sub(r'\s+', ' ', html.unescape(s)).strip()

def h1s(body):
    """Tekst svakog <h1> (slika u H1 se računa kroz alt)."""
    return [clean(x) for x in re.findall(r'<h1\b[^>]*>(.*?)</h1>', body, re.S)]

def crumbs(body, cls):
    """Vidljiva putanja: tekst linkova i spanova u <nav class="cls">, bez separatora."""
    m = re.search(r'<nav\b[^>]*class=["\'][^"\']*\b%s\b[^"\']*["\'][^>]*>(.*?)</nav>' % re.escape(cls), body, re.S)
    if not m:
        return None
    parts = [clean(x[1]) for x in re.findall(r'<(a|span)\b[^>]*>(.*?)</\1>', m.group(1), re.S)]
    return [x for x in parts if x not in ('', '›')]

def lds(body):
    """Svi JSON-LD blokovi; None na mestu bloka koji nije ispravan JSON."""
    out = []
    for raw in re.findall(r'<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>', body, re.S):
        try:
            out.append(json.loads(raw))
        except ValueError:
            out.append(None)
    return out

def ld_breadcrumbs(body):
    for block in lds(body):
        for node in (block or {}).get('@graph', []):
            if node.get('@type') == 'BreadcrumbList':
                return node.get('itemListElement', [])
    return None

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
    'kategorija kvizova': first_loc('wp-sitemap-taxonomies-quiz_category-1.xml'),
    'kategorija stavki': first_loc('wp-sitemap-taxonomies-voting_item_category-1.xml'),
    'vip arhiva': BASE + '/vip-osobe/',
    'dvoboji': BASE + '/showdown/',
}
_cache = {}
def page(name):
    if name not in _cache:
        _cache[name] = get(pages[name]) if pages.get(name) else (0, '')
    return _cache[name]

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
    st, b = get(BASE + '/kvizovi/page/2/')
    c = re.findall(r'<link[^>]+rel=["\']canonical["\'][^>]*>', b)
    ok('arhiva strana 2: canonical na samu sebe', len(c) == 1 and '/kvizovi/page/2/' in c[0], str(c))
    t2 = re.search(r'<title>(.*?)</title>', b, re.S)
    ok('arhiva strana 2: naslov ima "strana 2"', bool(t2) and 'strana 2' in html.unescape(t2.group(1)), t2.group(1) if t2 else '')
    for name in ['lista', 'kviz']:
        st, b = get(pages[name])
        c = re.findall(r'<link[^>]+rel=["\']canonical["\'][^>]*>', b)
        ok(f'{name}: tačno jedan canonical', len(c) == 1, str(c))

def robots_meta(body):
    return ' '.join(re.findall(r'<meta\s+name=["\']robots["\'][^>]*>', body))

if 'robots' in SECTIONS:
    q = first_loc('wp-sitemap-posts-question-1.xml') or BASE + '/question/kako-se-zove-film-u-kome-putnici-autobusom-putuju-ka-beogradu-uz-razne-nezgode/'
    noindex_urls = {
        'pitanje': q,
        'prijava': BASE + '/prijava/',
        'registracija': BASE + '/registracija/',
        'moj-nalog': BASE + '/moj-nalog/',
        'test stranica': BASE + '/test/',
        'hello world post': BASE + '/hello-world/',
        'pretraga': BASE + '/?s=fudbal',
        'arhiva po datumu': BASE + '/2024/12/',
    }
    for name, url in noindex_urls.items():
        st, b = get(url)
        r = robots_meta(b)
        ok(f'{name}: noindex', 'noindex' in r, f'{st} {r} {url}')
        ok(f'{name}: follow (ne nofollow)', 'nofollow' not in r, r)
    for name in ['home', 'lista', 'stavka', 'kviz', 'anketa', 'showdown', 'kategorija liste', 'arhiva kvizova']:
        url = pages[name]
        if not url:
            ok(f'{name}: URL', False); continue
        st, b = get(url)
        ok(f'{name}: NIJE noindex', 'noindex' not in robots_meta(b), robots_meta(b))
    st, idx = get(BASE + '/wp-sitemap.xml')
    ok('sitemap index: 200', st == 200, str(st))
    for frag in ['posts-question-', 'posts-quiz_levels-', 'posts-post-', 'users-', 'taxonomies-question_category-', 'taxonomies-category-']:
        ok(f'sitemap index bez "{frag}"', frag not in idx)
    for frag in ['posts-voting_list-', 'posts-voting_items-', 'posts-quiz-', 'posts-voting_poll-', 'posts-page-', 'taxonomies-voting_list_category-']:
        ok(f'sitemap index ima "{frag}"', frag in idx)
    st, pg = get(BASE + '/wp-sitemap-posts-page-1.xml')
    for slug in ['/prijava/', '/registracija/', '/moj-nalog/', '/test/', '/pocetna-old/']:
        ok(f'sitemap stranica bez {slug}', slug not in pg)
    ok('sitemap stranica ima /kontakt/', '/kontakt/' in pg)

if 'h1' in SECTIONS:
    for name in pages:
        st, b = page(name)
        h = h1s(b)
        ok(f'{name}: tačno jedan H1 koji nije prazan', len(h) == 1 and h[0] != '', f'{h} {pages[name]}')
    expect = {'home': 'YugoVote', 'arhiva lista': 'Sve rang liste za glasanje',
              'vip arhiva': 'VIP liste poznatih ličnosti', 'dvoboji': '1 na 1',
              'arhiva kvizova': 'Pokaži šta znaš'}
    for name, want in expect.items():
        ok(f'{name}: H1 = {want}', h1s(page(name)[1]) == [want], str(h1s(page(name)[1])))
    home_h1 = re.findall(r'<h1\b[^>]*>(.*?)</h1>', page('home')[1], re.S)
    ok('home: H1 bez <div> (samo fraze)', bool(home_h1) and '<div' not in home_h1[0], 'div u H1')

if 'schema' in SECTIONS:
    for name in pages:
        st, b = page(name)
        blocks = lds(b)
        ok(f'{name}: JSON-LD je ispravan JSON', None not in blocks, pages[name])
        if name == 'home':
            types = {n.get('@type') for blk in blocks if blk for n in blk.get('@graph', [])}
            ok('home: tačno jedan JSON-LD blok', len(blocks) == 1, str(len(blocks)))
            ok('home: WebSite + Organization', types == {'WebSite', 'Organization'}, str(types))
            org = next((n for blk in blocks if blk for n in blk.get('@graph', []) if n.get('@type') == 'Organization'), {})
            ok('home: Organization sameAs ima Instagram @yugovote', 'https://www.instagram.com/yugovote/' in org.get('sameAs', []), str(org.get('sameAs')))
            ok('home: futer vodi na Instagram @yugovote', 'instagram.com/yugovote/' in b, 'nema linka')
            ok('home: nigde stari nalog @yugo_vote', 'yugo_vote' not in b, 'stari nalog još postoji')
            kontakt = get(BASE + '/kontakt/')[1]
            for mreza, url in (('TikTok', 'https://www.tiktok.com/@yugovote'), ('YouTube', 'https://www.youtube.com/@YugoVote')):
                ok(f'home: Organization sameAs ima {mreza}', url in org.get('sameAs', []), str(org.get('sameAs')))
                ok(f'home: futer ima {mreza} ikonicu', f'href="{url}"' in b and f'elementor-social-icon-{mreza.lower()}' in b, 'nema ikonice')
                ok(f'kontakt: {mreza} link', f'href="{url}"' in kontakt, 'nema linka')
        elif name in ('lista', 'kategorija liste', 'kviz', 'anketa'):
            items = ld_breadcrumbs(b) or []
            names = [i.get('name') for i in items]
            ok(f'{name}: BreadcrumbList', len(items) >= 2, str(names))
            ok(f'{name}: pozicije 1..n', [i.get('position') for i in items] == list(range(1, len(items) + 1)), str(items))
            ok(f'{name}: sve adrese apsolutne na sajtu', all(str(i.get('item', '')).startswith(BASE) for i in items), str([i.get('item') for i in items]))
            og = metas(b, 'property', 'og:url')
            ok(f'{name}: poslednja stavka = og:url', bool(items) and bool(og) and items[-1].get('item') == content(og[0]), f"{items[-1].get('item') if items else None} vs {content(og[0]) if og else None}")
            cls = {'lista': 'vlp-breadcrumb', 'kategorija liste': 'ygv-cat-hero__breadcrumb', 'kviz': 'ygv-landing-crumbs', 'anketa': 'ygv-landing-crumbs'}[name]
            ok(f'{name}: JSON-LD putanja = vidljiva', names == crumbs(b, cls), f'{names} vs {crumbs(b, cls)}')
        else:
            ok(f'{name}: bez JSON-LD', blocks == [], str(len(blocks)))

if 'landing' in SECTIONS:
    title_link = 'class="ygv-quiz-card__title-link" href="%s/quiz/' % BASE
    ok('arhiva kvizova: naslov kartice je link ka kvizu', title_link in page('arhiva kvizova')[1])
    ok('početna (slajder): naslov kartice je link ka kvizu', title_link in page('home')[1])
    st, b = page('kviz')
    pid = re.search(r'\bpostid-(\d+)\b', b)
    ok('kviz: dugme otvara prozor za ovaj kviz', bool(pid) and re.search(r'class="ygv-landing-cta ygv-quiz-start-btn"\s+data-quiz-id="%s"' % (pid.group(1) if pid else 'x'), b) is not None, pid.group(1) if pid else 'nema postid')
    ok('kviz: putanja se vidi', bool(crumbs(b, 'ygv-landing-crumbs')), str(crumbs(b, 'ygv-landing-crumbs')))
    ok('kviz: još kvizova', 'ygv-landing-more__grid' in b and 'class="ygv-quiz-card ' in b.split('ygv-landing-more__grid', 1)[-1])
    st, b = page('anketa')
    pid = re.search(r'\bpostid-(\d+)\b', b)
    ok('anketa: forma za glasanje ove ankete', bool(pid) and re.search(r'class="ygv-poll [^"]*"\s+data-poll-id="%s"' % (pid.group(1) if pid else 'x'), b) is not None, pid.group(1) if pid else 'nema postid')
    ok('anketa: putanja se vidi', bool(crumbs(b, 'ygv-landing-crumbs')), str(crumbs(b, 'ygv-landing-crumbs')))
    # fetchpriority dodaje Elementor (izlazni bafer) ili WP; dupli atribut je neispravan HTML
    for name in ('kviz', 'anketa'):
        m = re.search(r'class="ygv-landing-card__img">\s*(<img\b[^>]*>)', page(name)[1])
        if m:
            ok(f'{name}: slika nema dupli fetchpriority', m.group(1).count('fetchpriority=') <= 1, m.group(1)[:160])
        else:
            print(f'SKIP  {name}: stranica nema sliku ({pages[name]})')

print(f'\n{fails} FAIL' if fails else '\nSVE PASS')
sys.exit(1 if fails else 0)
