"""Provera ručno pisanih SEO naslova/opisa.

Upotreba: python3 -I scripts/seo/validate-meta.py scripts/seo/meta-etapa1.json export.json
Izlaz 1 ako postoji ijedna tvrda greška; upozorenja se samo ispisuju.
"""
import json
import re
import sys
from collections import Counter

BANNED = ['otkrijte', 'otkrij', 'fascinantn', 'zaronite', 'zaroni ', 'ultimativn', 'neverovatan svet',
          'svet čuda', 'putovanje kroz', 'nezaboravn', 'očekuje vas', 'čeka vas']
IJE_WARN = ['lijep', 'bijel', 'vrijem', 'mjest', 'pjesm', 'pjev', 'djet', 'rijek', 'svijet', 'mlijek',
            'cvijet', 'vjer', 'smje', 'ljep', 'dje', 'gdje', 'uvijek']
CYR = re.compile(r'[Ѐ-ӿ]')
EMOJI = re.compile('[\U0001F300-\U0001FAFF☀-➿⭐⬆↔-↪]')
CAPS = re.compile(r'\b[A-ZČĆŠŽĐ]{5,}\b')

DESC_MIN, DESC_MAX, TITLE_MAX, TITLE_MAX_SOFT = 120, 155, 50, 60


def main(meta_path, export_path):
    meta = json.load(open(meta_path, encoding='utf-8'))
    export = json.load(open(export_path, encoding='utf-8'))
    exp = {(r['kind'], r['id']): r for r in export}
    errors, warns = [], []

    keys = Counter((m.get('kind'), m.get('id')) for m in meta)
    for k, n in keys.items():
        if n > 1:
            errors.append(f'{k}: duplikat u meta fajlu ({n}x)')
        if k not in exp:
            errors.append(f'{k}: ne postoji u izvozu')
    for k in exp:
        if k not in keys:
            errors.append(f'{k}: nedostaje ({exp[k]["type"]}: {exp[k]["title"]})')

    descs = Counter()
    titles = Counter()
    for m in meta:
        k = (m.get('kind'), m.get('id'))
        src = exp.get(k, {})
        t = (m.get('title') or '').strip()
        d = (m.get('desc') or '').strip()
        tag = f'{k} {src.get("title", "?")!r}'
        if m.get('type') and src and m['type'] != src.get('type'):
            errors.append(f'{tag}: type {m["type"]} != {src["type"]}')
        if not t:
            errors.append(f'{tag}: prazan naslov')
        if not d:
            errors.append(f'{tag}: prazan opis')
        if t.lower().endswith('yugovote'):
            errors.append(f'{tag}: naslov ne sme sadržati sufiks YugoVote')
        if len(t) > TITLE_MAX:
            long_name = len(src.get('title', '')) >= TITLE_MAX - 5
            if long_name and len(t) <= TITLE_MAX_SOFT:
                warns.append(f'{tag}: naslov {len(t)} > {TITLE_MAX} (dugo ime) — {t}')
            else:
                errors.append(f'{tag}: naslov {len(t)} > {TITLE_MAX} — {t}')
        if not (DESC_MIN <= len(d) <= DESC_MAX):
            errors.append(f'{tag}: opis {len(d)} znakova (treba {DESC_MIN}–{DESC_MAX}) — {d}')
        for txt, what in ((t, 'naslov'), (d, 'opis')):
            if CYR.search(txt):
                errors.append(f'{tag}: ćirilica u {what}')
            if EMOJI.search(txt):
                errors.append(f'{tag}: emodži u {what}')
            low = txt.lower()
            for b in BANNED:
                if b in low:
                    errors.append(f'{tag}: zabranjena fraza "{b}" u {what}')
            for w in IJE_WARN:
                if w in low:
                    warns.append(f'{tag}: moguća ijekavica "{w}" u {what} — {txt}')
            for c in CAPS.findall(txt):
                warns.append(f'{tag}: VELIKA SLOVA "{c}" u {what}')
        intro = (src.get('intro') or '').strip()
        if intro and len(d) >= 60 and intro[:60].lower() == d[:60].lower():
            errors.append(f'{tag}: opis je prepisan početak uvoda')
        descs[d.lower()] += 1
        titles[t.lower()] += 1

    for d, n in descs.items():
        if d and n > 1:
            errors.append(f'duplikat opisa ({n}x): {d}')
    for t, n in titles.items():
        if t and n > 1:
            warns.append(f'duplikat naslova ({n}x): {t}')

    for w in warns:
        print('WARN ', w)
    for e in errors:
        print('ERROR', e)
    print(f'\n{len(meta)} unosa, {len(errors)} grešaka, {len(warns)} upozorenja')
    return 1 if errors else 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1], sys.argv[2]))
