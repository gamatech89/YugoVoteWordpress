"""Spaja meta JSON i import-meta.php u jednu PHP skriptu za `wp eval-file -` (ništa se ne kopira na server).

Upotreba: python3 -I scripts/seo/bundle-import.py scripts/seo/meta-etapa1.json > /tmp/seo-import-bundle.php
"""
import base64
import json
import pathlib
import sys

data = json.load(open(sys.argv[1], encoding='utf-8'))
payload = base64.b64encode(json.dumps(data, ensure_ascii=False).encode('utf-8')).decode('ascii')
script = (pathlib.Path(__file__).parent / 'import-meta.php').read_text(encoding='utf-8')
sys.stdout.write(f"<?php\n$GLOBALS['YGV_SEO_DATA'] = json_decode(base64_decode('{payload}'), true);\n?>\n{script}")
