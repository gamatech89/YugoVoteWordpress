"""Pravi assets/images/og-default.jpg.  Upotreba: rsvg-convert -w 380 wp-content/uploads/2025/01/Logo.svg -o DIR/icon.png && python3 scripts/seo/make-og-default.py DIR scripts/ig-reel/fonts/Oswald.ttf wp-content/themes/hello-elementor-child/assets/images/og-default.jpg"""
import sys
from PIL import Image, ImageDraw, ImageFont
sp, font, out = sys.argv[1], sys.argv[2], sys.argv[3]
W, H = 1200, 630
NAVY, CORAL, YELLOW, ORANGE = (0x44,0x56,0xA6), (0xFE,0x65,0x55), (0xFF,0xCB,0x05), (0xFA,0xA7,0x4A)
im = Image.new('RGB', (W, H), (0xFF, 0xF9, 0xF0))
d = ImageDraw.Draw(im)
# traka u bojama logoa na dnu
band = 22
for i, c in enumerate([NAVY, CORAL, YELLOW, ORANGE]):
    d.rectangle([i * W // 4, H - band, (i + 1) * W // 4, H], fill=c)
icon = Image.open(f'{sp}/icon.png').convert('RGBA')
im.paste(icon, (90, (H - band - icon.height) // 2), icon)
def f(size, weight):
    ft = ImageFont.truetype(font, size)
    try: ft.set_variation_by_name(weight)
    except Exception as e: print('variation:', e)
    return ft
x = 520
d.text((x, 150), 'YugoVote', font=f(150, 'Bold'), fill=NAVY)
d.text((x + 4, 340), 'Rang liste, kvizovi i ankete', font=f(50, 'Medium'), fill=CORAL)
d.text((x + 4, 405), 'sa ex-YU prostora. Glasaj!', font=f(50, 'Medium'), fill=CORAL)
im.save(out, 'JPEG', quality=86, optimize=True, progressive=True)
