#!/usr/bin/env python3
"""Fails when a page of the documentation website (website/docs) shows an image that
does not exist. Zensical's --strict reports broken links to pages, not missing images.

    python3 tools/checks/check-doc-images.py
"""
import os
import re
import sys

ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..', 'website', 'docs')
IMAGE = re.compile(r'!\[[^\]]*\]\(\s*<?([^)\s>]+)>?(?:\s+"[^"]*")?\s*\)|<img\b[^>]*\bsrc="([^"]+)"')

missing = []
pages = 0
for folder, _, files in os.walk(ROOT):
    for name in sorted(files):
        if not name.endswith('.md'):
            continue
        pages += 1
        path = os.path.join(folder, name)
        with open(path, encoding='utf-8') as f:
            for number, line in enumerate(f, 1):
                for match in IMAGE.finditer(line):
                    src = (match.group(1) or match.group(2)).split('#')[0].split('?')[0]
                    if re.match(r'^[a-z]+:', src) or src.startswith('/'):
                        continue
                    if not os.path.isfile(os.path.join(folder, src)):
                        missing.append('%s:%d: %s' % (os.path.relpath(path, os.path.join(ROOT, '..', '..')), number, src))

for item in missing:
    print('missing image: ' + item)
if missing:
    print('FAIL  doc images: %d missing' % len(missing))
    sys.exit(1)
print('PASS  doc images: %d pages' % pages)
