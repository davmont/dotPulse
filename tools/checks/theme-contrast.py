#!/usr/bin/env python3
"""Contrast of the dotPulse themes (make theme-contrast).

Reads the colour variables of style/dotpulse/main.css and style/dotpulse-dark/main.css
(the dark one on top of the light one, as the browser does), and System Admin's own set
(modules/system/admin.css, redefined by the dark theme's "body .dpa" block), and checks
the pairs the stylesheets use: text 4.5:1 on what it sits on, lines, focus rings and disabled text
3:1 (WCAG 2.1 AA, 1.4.3 and 1.4.11). The icons are checked by tools/icons/build.py.
"""
import os, re, sys

ROOT = os.path.normpath(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..'))

# (foreground, background, minimum): variables without the --dp- prefix.
PAIRS = [
    # Body text and headings, on every surface.
    *[('ink', bg, 4.5) for bg in ('ground', 'surface', 'surface-alt', 'blue-wash', 'blue-tint', 'today', 'weekend', 'hover-tile', 'coral-wash', 'teal-wash')],
    # Secondary text: hints, the sub bar, inactive tabs and menu items.
    *[('slate', bg, 4.5) for bg in ('ground', 'surface', 'blue-tint', 'blue-wash')],
    # Links and accents.
    *[('blue', bg, 4.5) for bg in ('ground', 'surface', 'surface-alt', 'blue-tint', 'today', 'weekend')],
    *[('blue-deep', bg, 4.5) for bg in ('surface', 'blue-wash', 'blue-tint', 'today', 'hover-tile')],
    *[('coral-text', bg, 4.5) for bg in ('ground', 'surface', 'coral-wash')],
    ('teal', 'surface', 4.5),
    # Buttons and banners.
    ('on-primary', 'primary', 4.5), ('on-primary', 'primary-hover', 4.5), ('on-primary', 'banner', 4.5),
    # Disabled text and the days of other months: 3:1 (WCAG exempts them; kept readable).
    ('muted', 'surface', 3), ('faint', 'surface-alt', 3),
    # Edges of form controls and the focus ring (decorative lines, --dp-line, have no minimum).
    ('control-line', 'surface', 3), ('control-line', 'ground', 3), ('focus', 'surface', 3), ('focus', 'ground', 3),
]


# System Admin (--a-* variables): the same rules.
ADMIN_PAIRS = [
    *[('ink', bg, 4.5) for bg in ('surface', 'surface-head', 'tint', 'wash', 'off', 'coral-wash', 'teal-wash')],
    *[('slate', bg, 4.5) for bg in ('surface', 'surface-head', 'tint', 'wash')],
    *[('blue', bg, 4.5) for bg in ('surface', 'tint', 'wash')],
    ('blue-deep', 'wash', 4.5),
    *[('coral-text', bg, 4.5) for bg in ('surface', 'coral-wash')],
    *[('teal', bg, 4.5) for bg in ('surface', 'teal-wash')],
    ('on-primary', 'primary', 4.5), ('on-primary', 'primary-deep', 4.5),
    ('faint', 'surface', 3),
    # Edges of inputs, buttons, switches and the day picker; the focus ring.
    ('control-line', 'surface', 3), ('control-line', 'surface-head', 3), ('focus', 'surface', 3),
]


def variables(css_file, block=r':root', prefix='dp'):
    css = open(os.path.join(ROOT, css_file), encoding='utf-8').read()
    root = re.search(block + r'\s*\{(.*?)\}', css, re.S).group(1)
    return dict(re.findall(r'--' + prefix + r'-([a-z-]+)\s*:\s*(#[0-9A-Fa-f]{6})\s*;', root))


def luminance(hex_colour):
    c = [int(hex_colour[i:i + 2], 16) / 255 for i in (1, 3, 5)]
    c = [v / 12.92 if v <= 0.03928 else ((v + 0.055) / 1.055) ** 2.4 for v in c]
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2]


def contrast(a, b):
    la, lb = luminance(a), luminance(b)
    return (max(la, lb) + 0.05) / (min(la, lb) + 0.05)


def check(label, prefix, pairs, v):
    failed = 0
    for fg, bg, minimum in pairs:
        ratio = contrast(v[fg], v[bg])
        if ratio < minimum:
            failed += 1
            print(f'{label}: --{prefix}-{fg} {v[fg]} on --{prefix}-{bg} {v[bg]} is {ratio:.2f}:1, needs {minimum}:1')
    return failed


def main():
    light = variables('style/dotpulse/main.css')
    admin = variables('modules/system/admin.css', r'\.dpa', 'a')
    themes = {
        'dotpulse': (light, admin),
        'dotpulse-dark': ({**light, **variables('style/dotpulse-dark/main.css')},
                          {**admin, **variables('style/dotpulse-dark/main.css', r'body \.dpa', 'a')}),
    }
    failed = 0
    for theme, (v, a) in themes.items():
        failed += check(theme, 'dp', PAIRS, v)
        failed += check(theme + ' System Admin', 'a', ADMIN_PAIRS, a)
    if failed:
        sys.exit(f'FAIL: {failed} colour pairs below the WCAG AA minimum.')
    print(f'OK: {len(PAIRS) + len(ADMIN_PAIRS)} colour pairs reach WCAG AA in dotpulse and dotpulse-dark'
          ' (System Admin included).')


if __name__ == '__main__':
    main()
