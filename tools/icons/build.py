"""Builds the dotPulse interface icons (SVG) next to the bitmaps they replace.

Plain Python 3, no packages:
    python3 tools/icons/build.py            writes the SVGs
    python3 tools/icons/build.py --preview  also writes tools/icons/preview.html (git-ignored)

Every icon is drawn on a 24-unit grid with round strokes, in the brand palette of
tools/brand/build.py, so it stays sharp at any size and screen density.
- Module tiles (page titles, 48 px): a rounded square in the colour of the module's
  area, a white glyph and one brand dot.
    planning (blue): projects, tasks, calendar, PMBOK phases...
    people (teal): companies, contacts, users, communication, support...
    money and time (coral): costs, invoices, timesheets...
    records and system (slate): files, history, backup, settings...
- Small icons (actions and states, 16 px): the glyph alone, coloured by what it does
  (edit blue, delete coral, add and confirm teal, the rest slate).
Each SVG has the name of the bitmap it replaces, next to it. Only themes that define
DP_VECTOR_ICONS (dotpulse) show them: dPfindImage() prefers the SVG and dPvectorIconFilter()
swaps the bitmaps in the page; the classic themes keep the bitmaps.
"""
import os, sys

ROOT = os.path.normpath(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..'))

C = {
    'blue': '#1B4F9C', 'blue_deep': '#123A73', 'blue_light': '#7BA9F7',
    'teal': '#0A8080', 'coral': '#F06449', 'coral_text': '#C93D27',
    'slate': '#5A6474', 'ink': '#23262B', 'line': '#B8C3D4', 'white': '#FFFFFF',
    'green': '#2E9E5B', 'amber': '#E0A100',
}

# Areas: tile colour (top of the gradient, bottom) and the colour of the brand dot.
AREA = {
    'plan':   ('#2A63B8', C['blue'], C['coral']),
    'people': ('#13999A', C['teal'], C['coral']),
    'money':  ('#E2553C', C['coral_text'], C['blue_deep']),
    'system': ('#6D7889', C['slate'], C['coral']),
}

# --- Glyphs: stroke paths on the 24 grid; 'fill' paths are drawn filled; 'dot' is
# where the brand dot goes on a tile (x, y), None for none.
G = {}
def glyph(name, d, dot=None, fill=''):
    G[name] = (d, dot, fill)

glyph('gantt', 'M4 4v16h16M7 8h7M10 12h7M8 16h5', (17, 16))
glyph('layers', 'M12 3.5l8.5 4.5-8.5 4.5-8.5-4.5zM3.5 12l8.5 4.5 8.5-4.5M3.5 16l8.5 4.5 8.5-4.5', (12, 8))
glyph('checklist', 'M3.5 6.5l1.8 1.8L8.8 4.8M12 7h8.5M3.5 12.5l1.8 1.8 3.5-3.5M12 13h8.5M12 19h8.5M4 17h4v4H4z', (6, 19))
glyph('template', 'M9 3h7l4 4v10a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zM16 3v4h4M5 7v13a1 1 0 0 0 1 1h10')
glyph('links-gantt', 'M4 6h8M10 12h10M6 18h8M12 6h1a2 2 0 0 1 2 2v1.5M13.3 8.5l1.7 1.7 1.7-1.7', (20, 12))
glyph('design', 'M15 4l5 5L9 20H4v-5zM12.5 6.5l5 5', (6.5, 17.5))
glyph('network', 'M6 6l12 0M6 6l6 12M18 6l-6 12M6 3.8a2.2 2.2 0 1 1 0 4.4 2.2 2.2 0 0 1 0-4.4zM18 3.8a2.2 2.2 0 1 1 0 4.4 2.2 2.2 0 0 1 0-4.4z', (12, 18))
glyph('flag', 'M5 21V4M5 4h11l-2 4 2 4H5', (5, 21))
glyph('archive', 'M3 4h18v4H3zM5 8v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8M9 14l2 2 4-4', None)
glyph('gauge', 'M4 17a8 8 0 1 1 16 0M12 17l4-5M6.5 11.5l1 .8M12 9v1.2M17.5 11.5l-1 .8', (12, 17))
glyph('hourglass', 'M6.5 3h11M6.5 21h11M8 3v2.5a4 4 0 0 0 1.6 3.2L12 10.5l2.4-1.8A4 4 0 0 0 16 5.5V3M8 21v-2.5a4 4 0 0 1 1.6-3.2L12 13.5l2.4 1.8A4 4 0 0 1 16 18.5V21', (12, 18.5))
glyph('calendar', 'M5 5h14a1.5 1.5 0 0 1 1.5 1.5v12A1.5 1.5 0 0 1 19 20H5a1.5 1.5 0 0 1-1.5-1.5v-12A1.5 1.5 0 0 1 5 5zM3.5 10h17M8 3v4M16 3v4', (12, 15))
glyph('calendar-star', 'M5 5h14a1.5 1.5 0 0 1 1.5 1.5v12A1.5 1.5 0 0 1 19 20H5a1.5 1.5 0 0 1-1.5-1.5v-12A1.5 1.5 0 0 1 5 5zM3.5 10h17M8 3v4M16 3v4M12 12.3l1 2 2.2.3-1.6 1.5.4 2.2-2-1-2 1 .4-2.2-1.6-1.5 2.2-.3z', None)
glyph('calendar-sync', 'M5 5h14a1.5 1.5 0 0 1 1.5 1.5v12A1.5 1.5 0 0 1 19 20H5a1.5 1.5 0 0 1-1.5-1.5v-12A1.5 1.5 0 0 1 5 5zM3.5 10h17M8 3v4M16 3v4M12 12.5v5M9.8 15.5l2.2 2.2 2.2-2.2', None)
glyph('warning', 'M10.3 4.2L2.6 17.5A2 2 0 0 0 4.3 20.5h15.4a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0zM12 9.5v4.5', (12, 17))
glyph('flask', 'M9 3h6M10 3v6l-5.2 8.8A2 2 0 0 0 6.5 21h11a2 2 0 0 0 1.7-3.2L14 9V3M7.2 15h9.6', (12, 18))
glyph('target', 'M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18zM12 7.5a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9z', (12, 12))
glyph('pulse', 'M3 14h4.5L11 6.5l4 11 3-5.5h3', (11, 6.5))
glyph('bars', 'M4 20h16M7.5 20v-5M12 20V9M16.5 20v-8', (16.5, 7))
glyph('building', 'M4 21V5.5L12 3v18M12 9h7a1 1 0 0 1 1 1v11M3 21h18M7.5 8h1.5M7.5 12h1.5M7.5 16h1.5M15.5 13h1.5M15.5 17h1.5', (16.25, 6))
glyph('orgchart', 'M9.5 3h5a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zM3.5 16h5a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1zM15.5 16h5a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1zM12 8v4M6 12h12M6 12v4M18 12v4', (12, 12))
glyph('card', 'M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zM9 8.7a2.3 2.3 0 1 1 0 4.6 2.3 2.3 0 0 1 0-4.6zM5.5 16.5a3.5 3.5 0 0 1 7 0M15 10h3.5M15 14h3.5', (16.75, 14))
glyph('user', 'M12 4a4 4 0 1 1 0 8 4 4 0 0 1 0-8zM4.5 21a7.5 7.5 0 0 1 15 0')
glyph('users', 'M9 4.5a3.5 3.5 0 1 1 0 7 3.5 3.5 0 0 1 0-7zM2.5 20a6.5 6.5 0 0 1 13 0M16 4.8a3.4 3.4 0 0 1 0 6.4M18 14.2a6.5 6.5 0 0 1 3.5 5.8', None)
glyph('badge', 'M7 3.5h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-14a2 2 0 0 1 2-2zM10 3.5V6h4V3.5M12 9a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zM8.5 18a3.5 3.5 0 0 1 7 0', None)
glyph('cube', 'M12 3l8 4.5v9L12 21l-8-4.5v-9zM4 7.5l8 4.5 8-4.5M12 12v9', (12, 12))
glyph('chat', 'M4 4h11a1.5 1.5 0 0 1 1.5 1.5v6.5a1.5 1.5 0 0 1-1.5 1.5H9l-4 3v-3H4a1.5 1.5 0 0 1-1.5-1.5V5.5A1.5 1.5 0 0 1 4 4zM19.5 8.5h.5a1.5 1.5 0 0 1 1.5 1.5v6a1.5 1.5 0 0 1-1.5 1.5h-1v3l-4-3h-3.5', (19, 6))
glyph('mail', 'M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zM3.5 7l8.5 6 8.5-6', (19.5, 5.5))
glyph('megaphone', 'M4 10v4h3l8 5V5L7 10zM18 9a4 4 0 0 1 0 6M7 14l1.5 6.5h2.5L10 15', (18, 12))
glyph('link', 'M10 14a4.2 4.2 0 0 0 6 0l3-3a4.2 4.2 0 0 0-6-6l-1 1M14 10a4.2 4.2 0 0 0-6 0l-3 3a4.2 4.2 0 0 0 6 6l1-1', None)
glyph('lifebuoy', 'M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18zM12 8a4 4 0 1 1 0 8 4 4 0 0 1 0-8zM5.6 5.6l3.6 3.6M14.8 14.8l3.6 3.6M18.4 5.6l-3.6 3.6M9.2 14.8l-3.6 3.6', None)
glyph('ticket', 'M5 5h14a2 2 0 0 1 2 2v2.5a2.5 2.5 0 0 0 0 5V17a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.5a2.5 2.5 0 0 0 0-5V7a2 2 0 0 1 2-2zM15 5.5v2M15 11v2M15 16.5v2', (9, 12))
glyph('bug', 'M12 7a4 4 0 0 1 4 4v4a4 4 0 0 1-8 0v-4a4 4 0 0 1 4-4zM9.5 7.5a2.5 2.5 0 0 1 5 0M12 11v8M3.5 10l4.5 1.5M20.5 10L16 11.5M3.5 15H8M20.5 15H16M4.5 20.5l3.8-2.5M19.5 20.5l-3.8-2.5', None)
glyph('document', 'M14 3H6.5A1.5 1.5 0 0 0 5 4.5v15A1.5 1.5 0 0 0 6.5 21h11a1.5 1.5 0 0 0 1.5-1.5V8zM14 3v5h5M8.5 13h7M8.5 17h5', (17.5, 17))
glyph('documents', 'M8 7V4.5A1.5 1.5 0 0 1 9.5 3H16l4 4v10.5a1.5 1.5 0 0 1-1.5 1.5H16M5.5 7H12l4 4v8.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 4 19.5v-11A1.5 1.5 0 0 1 5.5 7z', None)
glyph('history', 'M3.5 12a8.5 8.5 0 1 0 2.5-6M3.5 4v4h4M12 7.5V12l3 2', (12, 12))
glyph('search', 'M10.5 4a6.5 6.5 0 1 1 0 13 6.5 6.5 0 0 1 0-13zM15.5 15.5L21 21', (10.5, 10.5))
glyph('gear', 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z', None)
glyph('database', 'M12 3c4.4 0 8 1.1 8 2.5S16.4 8 12 8 4 6.9 4 5.5 7.6 3 12 3zM4 5.5v13C4 19.9 7.6 21 12 21s8-1.1 8-2.5v-13M4 12c0 1.4 3.6 2.5 8 2.5s8-1.1 8-2.5', None)
glyph('import', 'M12 3v11M8 10l4 4 4-4M4 15v4a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 20 19v-4', None)
glyph('bell', 'M6 16.5V11a6 6 0 0 1 12 0v5.5l1.5 1.5h-15zM10 21h4', (18, 5.5))
glyph('coins', 'M12 3.5c3.9 0 7 1.1 7 2.5s-3.1 2.5-7 2.5S5 7.4 5 6s3.1-2.5 7-2.5zM5 6v4c0 1.4 3.1 2.5 7 2.5s7-1.1 7-2.5V6M5 10v4c0 1.4 3.1 2.5 7 2.5s7-1.1 7-2.5v-4M5 14v4c0 1.4 3.1 2.5 7 2.5s7-1.1 7-2.5v-4', None)
glyph('wallet', 'M17 7.5V5a1.5 1.5 0 0 0-1.5-1.5H5.5a2 2 0 0 0 0 4H19a1.5 1.5 0 0 1 1.5 1.5v3M20.5 16v3a1.5 1.5 0 0 1-1.5 1.5H5.5a2 2 0 0 1-2-2v-13M21 12h-4a2 2 0 0 0 0 4h4z', (17, 14))
glyph('receipt', 'M6 3h12v18l-2-1.5-2 1.5-2-1.5-2 1.5-2-1.5L6 21zM9 8h6M9 12h6M9 16h3', (15, 16))
glyph('banknote', 'M5 6h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2zM12 9.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5z', (6.5, 9.5))
glyph('creditcard', 'M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zM3 10h18M7 15h4', (17, 15))
glyph('pie', 'M11 4a8.5 8.5 0 1 0 9 9h-9zM14 3.2a8 8 0 0 1 6.8 6.8H14z', None)
glyph('tag', 'M3.5 12.5v-8a1 1 0 0 1 1-1h8l8.5 8.5-9 9z', (8.5, 8.5))
glyph('car', 'M3.5 16v-3.5L6 7h12l2.5 5.5V16a1 1 0 0 1-1 1h-15a1 1 0 0 1-1-1zM3.5 12.5h17M7.5 17v2M16.5 17v2', (7.5, 14.5))
glyph('timecard', 'M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zM9 8.5a3.5 3.5 0 1 1 0 7 3.5 3.5 0 0 1 0-7zM9 10.3V12l1.3 1M15 10h3M15 14h3', None)
glyph('timesheet', 'M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zM3 9h18M9 9v11M3 14.5h18', (15, 17.25))
glyph('stopwatch', 'M12 5.5a7.75 7.75 0 1 1 0 15.5 7.75 7.75 0 0 1 0-15.5zM12 9.5v3.75l2.5 1.75M10 2.5h4M12 2.5v3M18.5 6.5l1.5-1.5', (12, 13.25))
glyph('package', 'M12 3l8.5 4v10L12 21l-8.5-4V7zM3.5 7l8.5 4 8.5-4M12 11v10M7.8 5l8.5 4', None)
glyph('server', 'M5 4h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zM5 13h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2zM11 7.5h6M11 16.5h6', (7, 7.5))
glyph('note', 'M5 4h14a1 1 0 0 1 1 1v9l-6 6H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1zM14 20v-6h6M8 9h8M8 12.5h4', None)
glyph('ledger', 'M6.5 3H19v15H6.5A1.5 1.5 0 0 0 5 19.5v0A1.5 1.5 0 0 0 6.5 21H19v-3M5 19.5v-15A1.5 1.5 0 0 1 6.5 3M9 7.5h6M9 11h4', None)
glyph('notebook', 'M6 3h12a1.5 1.5 0 0 1 1.5 1.5v15A1.5 1.5 0 0 1 18 21H6zM9.5 3v18M13 8h3.5M13 12h3.5M4 7h3.5M4 12h3.5M4 17h3.5', None)
glyph('image', 'M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zM21 15.5l-5-5L6.5 20', (8.5, 9.5))
glyph('help', 'M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18zM9.3 9.2a2.8 2.8 0 0 1 5.4 1c0 1.9-2.7 2.3-2.7 4.3', (12, 17.3))
glyph('folder', 'M3.5 7V5.5A1.5 1.5 0 0 1 5 4h4.5l2 2.5H19A1.5 1.5 0 0 1 20.5 8v10.5A1.5 1.5 0 0 1 19 20H5a1.5 1.5 0 0 1-1.5-1.5zM3.5 10h17', None)

# Small glyphs (actions and states).
glyph('edit', 'M15.5 4.5l4 4L9 19l-5 1 1-5zM13 7l4 4')
glyph('trash', 'M4 6.5h16M9.5 6.5V4.5a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v2M6 6.5l1 13a1.5 1.5 0 0 0 1.5 1.5h7a1.5 1.5 0 0 0 1.5-1.5l1-13M10 10.5v6.5M14 10.5v6.5')
glyph('plus-circle', 'M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18zM12 8v8M8 12h8')
glyph('check', 'M4.5 12.5l5 5L19.5 7')
glyph('x-circle', 'M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18zM9 9l6 6M15 9l-6 6')
glyph('remove-circle', 'M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18zM8 12h8')
glyph('clip', 'M20 11.5l-8 8a5 5 0 0 1-7-7l8.5-8.5a3.3 3.3 0 0 1 4.7 4.7L9.7 17.2a1.7 1.7 0 0 1-2.4-2.4l7.5-7.5')
glyph('printer', 'M7 9V3.5h10V9M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v6.5H7z')
glyph('plus-square', 'M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1zM12 8v8M8 12h8')
glyph('minus-square', 'M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1zM8 12h8')
glyph('chev-left', 'M14.5 5.5L8 12l6.5 6.5')
glyph('chev-right', 'M9.5 5.5L16 12l-6.5 6.5')
glyph('chev-first', 'M17 5.5L10.5 12l6.5 6.5M7 5.5v13')
glyph('chev-last', 'M7 5.5l6.5 6.5L7 18.5M17 5.5v13')
glyph('caret-right', '', fill='M9 6l8 6-8 6z')
glyph('caret-down', '', fill='M6 9l6 8 6-8z')
glyph('caret-up', '', fill='M6 15l6-8 6 8z')
glyph('diamond', '', fill='M12 3.5l8.5 8.5-8.5 8.5L3.5 12z')
glyph('pin', 'M9 3.5h6M10 3.5v6L6.5 14h11L14 9.5v-6M12 14v6.5')
glyph('unpin', 'M9 3.5h6M10 3.5v6L6.5 14h11L14 9.5v-6M12 14v6.5M3.5 3.5l17 17')
glyph('arrow-up', 'M12 20V5M6 11l6-6 6 6')
glyph('arrows-up', 'M12 21V9M7 13l5-5 5 5M7 7.5l5-5 5 5')
glyph('arrow-down', 'M12 4v15M6 13l6 6 6-6')
glyph('arrows-down', 'M12 3v12M7 11l5 5 5-5M7 16.5l5 5 5-5')
glyph('updown', 'M8 4v16M4.5 7.5L8 4l3.5 3.5M16 20V4M12.5 16.5L16 20l3.5-3.5')
glyph('info-circle', 'M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18zM12 11v5.5M12 7.5v.5')
glyph('alert-circle', 'M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18zM12 7.5v5.5M12 16.5v.5')
glyph('warning-small', 'M10.3 4.2L2.6 17.5A2 2 0 0 0 4.3 20.5h15.4a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0zM12 9.5v4.5M12 17v.5')
glyph('lock', 'M6 10.5h12a1.5 1.5 0 0 1 1.5 1.5v7.5A1.5 1.5 0 0 1 18 21H6a1.5 1.5 0 0 1-1.5-1.5V12A1.5 1.5 0 0 1 6 10.5zM8 10.5V7a4 4 0 0 1 8 0v3.5')
glyph('phone', 'M6.5 3.5h3l1.5 4.5-2 1.5a11 11 0 0 0 5.5 5.5l1.5-2 4.5 1.5v3a2 2 0 0 1-2 2A16.5 16.5 0 0 1 4.5 5.5a2 2 0 0 1 2-2z')
glyph('palette', 'M12 3a9 9 0 0 0 0 18c1.2 0 1.8-.8 1.8-1.7 0-1.4-1.3-1.8-1.3-3 0-.9.7-1.6 1.6-1.6H17a4 4 0 0 0 4-4C21 6.5 17 3 12 3zM7.5 12.5v.5M9 8v.5M14 6.5V7M17.5 9.5v.5')
glyph('eye', 'M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12zM12 9a3 3 0 1 1 0 6 3 3 0 0 1 0-6z')
glyph('cake', 'M4 13.5h16v7H4zM4 17c2 1.5 4 1.5 6 0s4-1.5 6 0 4 1.5 4 0M8 13.5v-3M12 13.5v-3M16 13.5v-3M8 7.5v.5M12 7.5v.5M16 7.5v.5')
glyph('folder-shared', 'M3.5 7V5.5A1.5 1.5 0 0 1 5 4h4.5l2 2.5H19A1.5 1.5 0 0 1 20.5 8v10.5A1.5 1.5 0 0 1 19 20H5a1.5 1.5 0 0 1-1.5-1.5zM12 11a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM8.5 19a3.5 3.5 0 0 1 7 0')
glyph('week', 'M5 5h14a1.5 1.5 0 0 1 1.5 1.5v12A1.5 1.5 0 0 1 19 20H5a1.5 1.5 0 0 1-1.5-1.5v-12A1.5 1.5 0 0 1 5 5zM3.5 10h17M8 3v4M16 3v4M6.5 14.5h11')
glyph('fields', 'M4 5h16v4H4zM4 15h16v4H4zM7 7h.01M7 17h.01')
glyph('plus', 'M12 5v14M5 12h14')
glyph('save', 'M5 3.5h11l4.5 4.5v11a1.5 1.5 0 0 1-1.5 1.5H5A1.5 1.5 0 0 1 3.5 19V5A1.5 1.5 0 0 1 5 3.5zM7.5 3.5V8h7V3.5M7.5 20.5v-6h9v6')
glyph('export', 'M12 15V4M8 8l4-4 4 4M4 15v4a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 20 19v-4')
glyph('dot', '', fill='M12 5a7 7 0 1 1 0 14 7 7 0 0 1 0-14z')
glyph('smartfolder', 'M3.5 7V5.5A1.5 1.5 0 0 1 5 4h4.5l2 2.5H19A1.5 1.5 0 0 1 20.5 8v10.5A1.5 1.5 0 0 1 19 20H5a1.5 1.5 0 0 1-1.5-1.5zM11 10.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zM13 15l2.5 2.5')


def tile(glyph_name, area):
    d, dot, fill = G[glyph_name]
    top, bottom, dot_colour = AREA[area]
    gid = 'g'
    s = ('<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48">'
         f'<defs><linearGradient id="{gid}" x1="0" y1="0" x2="0" y2="1">'
         f'<stop offset="0" stop-color="{top}"/><stop offset="1" stop-color="{bottom}"/></linearGradient></defs>'
         f'<rect x="2" y="2" width="44" height="44" rx="11" fill="url(#{gid})"/>'
         '<g transform="translate(9 9) scale(1.25)">')
    if d:
        s += f'<path d="{d}" fill="none" stroke="#FFFFFF" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>'
    if fill:
        s += f'<path d="{fill}" fill="#FFFFFF"/>'
    if dot:
        s += (f'<circle cx="{dot[0]}" cy="{dot[1]}" r="2.6" fill="{dot_colour}" stroke="#FFFFFF" stroke-width="1.2"/>')
    return s + '</g></svg>\n'


def small(glyph_name, colour, size=16, accent=None):
    d, _, fill = G[glyph_name]
    s = f'<svg xmlns="http://www.w3.org/2000/svg" width="{size}" height="{size}" viewBox="0 0 24 24">'
    if fill:
        s += f'<path d="{fill}" fill="{accent or colour}"/>'
    if d:
        s += f'<path d="{d}" fill="none" stroke="{colour}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
    return s + '</svg>\n'


# --- Page-title tiles: (file next to the bitmap it replaces, glyph, area).
TILES = [
    ('modules/projects/images/applet3-48.svg', 'gantt', 'plan'),
    ('modules/macroprojects/images/applet3-48.svg', 'layers', 'plan'),
    ('modules/tasks/images/applet-48.svg', 'checklist', 'plan'),
    ('modules/tasks_template/images/messages.svg', 'template', 'plan'),
    ('modules/igantt/images/applet3-48.svg', 'links-gantt', 'plan'),
    ('modules/projectdesigner/images/projectdesigner.svg', 'design', 'plan'),
    ('modules/dotproject_plus/images/applet3-48.svg', 'network', 'plan'),
    ('modules/initiating/images/applet3-48.svg', 'flag', 'plan'),
    ('modules/closure/images/closed.svg', 'archive', 'plan'),
    ('modules/monitoringandcontrol/images/graph-up.svg', 'gauge', 'plan'),
    ('modules/timeplanning/images/applet3-48.svg', 'hourglass', 'plan'),
    ('modules/calendar/images/myevo-appointments.svg', 'calendar', 'plan'),
    ('modules/holiday/images/calendar.svg', 'calendar-star', 'plan'),
    ('modules/risks/images/risks.svg', 'warning', 'plan'),
    ('modules/testing/images/testing.svg', 'flask', 'plan'),
    ('modules/opportunities/images/opportunities.svg', 'target', 'plan'),
    ('modules/dashboard/images/dashboard.svg', 'pulse', 'plan'),
    ('modules/reports/images/applet3-48.svg', 'bars', 'plan'),

    ('modules/companies/images/handshake.svg', 'building', 'people'),
    ('modules/departments/images/users.svg', 'orgchart', 'people'),
    ('modules/contacts/images/monkeychat-48.svg', 'card', 'people'),
    ('modules/admin/images/helix-setup-user.svg', 'user', 'people'),
    ('modules/admin/images/helix-setup-users.svg', 'users', 'people'),
    ('modules/human_resources/images/applet3-48.svg', 'badge', 'people'),
    ('modules/resources/images/helpdesk.svg', 'cube', 'people'),
    ('modules/resource_m/images/helpdesk.svg', 'users', 'people'),
    ('modules/forums/images/support.svg', 'chat', 'people'),
    ('modules/messages/images/messages.svg', 'mail', 'people'),
    ('modules/communication/images/applet3-48.svg', 'megaphone', 'people'),
    ('modules/links/images/folder5.svg', 'link', 'people'),
    ('modules/helpdesk/images/helpdesk.svg', 'lifebuoy', 'people'),
    ('modules/ticketsmith/images/gconf-app-icon.svg', 'ticket', 'people'),
    ('modules/mantis/images/mantis_logo_button.svg', 'bug', 'people'),

    ('modules/costs/images/costs.svg', 'wallet', 'money'),
    ('modules/invoices/images/applet3-48.svg', 'receipt', 'money'),
    ('modules/earnings/images/earnings.svg', 'banknote', 'money'),
    ('modules/payments/images/applet3-48.svg', 'creditcard', 'money'),
    ('modules/finances/images/applet3-48.svg', 'pie', 'money'),
    ('modules/mileagelog/images/MileageLog.svg', 'car', 'money'),
    ('modules/timecard/images/TimeCard.svg', 'timecard', 'money'),
    ('modules/timesheet/images/applet3-48.svg', 'timesheet', 'money'),
    ('modules/timetrack/images/applet3-48.svg', 'stopwatch', 'money'),
    ('modules/inventory/images/48_my_computer.svg', 'package', 'money'),

    ('modules/files/images/folder5.svg', 'document', 'system'),
    ('modules/mngdocument/images/applet3-48.svg', 'documents', 'system'),
    ('modules/history/images/stock_book_blue_48.svg', 'history', 'system'),
    ('modules/smartsearch/images/kfind.svg', 'search', 'system'),
    ('modules/system/images/myevo-weather.svg', 'gear', 'system'),
    ('modules/backup/images/companies.svg', 'database', 'system'),
    ('modules/dataimport/images/companies.svg', 'import', 'system'),
    ('modules/informer/images/iCandy_Regional_Settings.svg', 'bell', 'system'),
    ('modules/hosting/images/applet3-48.svg', 'server', 'system'),
    ('modules/annotations/images/annotations.svg', 'note', 'system'),
    ('modules/registers/images/applet3-48.svg', 'ledger', 'system'),
    ('modules/journal/images/applet3-48.svg', 'notebook', 'system'),

    # The large shared icons in images/icons/.
    ('images/icons/admin.svg', 'user', 'people'),
    ('images/icons/users.svg', 'users', 'people'),
    ('images/icons/companies.svg', 'building', 'people'),
    ('images/icons/calendar.svg', 'calendar', 'plan'),
    ('images/icons/mail.svg', 'mail', 'people'),
    ('images/icons/ticketsmith.svg', 'ticket', 'people'),
    ('images/icons/graph.svg', 'bars', 'plan'),
    ('images/icons/dp.svg', 'pulse', 'plan'),
    ('images/icons/customfields.svg', 'fields', 'system'),
    ('images/icons/dialog-warning5.svg', 'warning', 'money'),
    ('images/icons/stock_new.svg', 'plus-circle', 'people'),
    ('images/helpdesk.svg', 'lifebuoy', 'people'),
]

# --- Small icons: (file, glyph, colour, size).
B, T, R, S = C['blue'], C['teal'], C['coral_text'], C['slate']
SMALL = [
    ('images/calendar.svg', 'calendar', B, 16),
    ('images/icons/pencil.svg', 'edit', B, 16),
    ('images/icons/stock_edit-16.svg', 'edit', B, 16),
    ('images/icons/stock_delete-16.svg', 'trash', R, 16),
    ('images/icons/trash.svg', 'trash', R, 16),
    ('images/icons/stock_trash_full-16.svg', 'trash', R, 16),
    ('images/trash_small.svg', 'trash', R, 16),
    ('images/icons/stock_new_small.svg', 'plus-circle', T, 24),
    ('images/icons/stock_ok-16.svg', 'check', T, 16),
    ('images/icons/stock_cancel-16.svg', 'x-circle', R, 16),
    ('images/icons/stock_help-16.svg', 'help', B, 16),
    ('images/obj/help.svg', 'help', B, 16),
    ('images/icons/stock_attach-16.svg', 'clip', S, 16),
    ('images/clip.svg', 'clip', S, 16),
    ('images/icons/stock_print-16.svg', 'printer', S, 16),
    ('images/icons/expand.svg', 'plus-square', S, 12),
    ('images/icons/collapse.svg', 'minus-square', S, 12),
    ('images/prev.svg', 'chev-left', S, 16),
    ('images/next.svg', 'chev-right', S, 16),
    ('images/navleft.svg', 'chev-left', S, 16),
    ('images/navright.svg', 'chev-right', S, 16),
    ('images/navfirst.svg', 'chev-first', S, 16),
    ('images/navlast.svg', 'chev-last', S, 16),
    ('images/arrow-right.svg', 'caret-right', S, 12),
    ('images/arrow-down.svg', 'caret-down', S, 12),
    ('images/arrow-up.svg', 'caret-up', S, 12),
    ('images/icons/milestone.svg', 'diamond', C['coral'], 14),
    ('images/icons/pin.svg', 'pin', B, 14),
    ('images/icons/unpin.svg', 'unpin', S, 14),
    ('images/icons/priority+1.svg', 'arrow-up', C['coral'], 16),
    ('images/icons/priority+2.svg', 'arrows-up', C['coral_text'], 16),
    ('images/icons/priority-1.svg', 'arrow-down', B, 16),
    ('images/icons/priority-2.svg', 'arrows-down', B, 16),
    ('images/icons/low.svg', 'arrow-down', B, 16),
    ('images/icons/updown.svg', 'updown', S, 16),
    ('images/icons/birthday.svg', 'cake', C['coral'], 16),
    ('images/log-error.svg', 'alert-circle', R, 14),
    ('images/log-info.svg', 'info-circle', B, 14),
    ('images/log-notice.svg', 'warning-small', C['amber'], 14),
    ('images/obj/alert.svg', 'warning-small', C['coral'], 16),
    ('images/obj/warning.svg', 'warning-small', C['amber'], 16),
    ('images/obj/error.svg', 'alert-circle', R, 16),
    ('images/obj/lock.svg', 'lock', S, 16),
    ('images/obj/contact.svg', 'user', B, 16),
    ('images/obj/email.svg', 'mail', B, 16),
    ('images/obj/phone.svg', 'phone', B, 16),
    ('images/obj/event.svg', 'calendar', B, 16),
    ('images/obj/event-attendees.svg', 'users', B, 16),
    ('images/obj/folder.svg', 'folder', S, 16),
    ('images/obj/folder-shared.svg', 'folder-shared', S, 16),
    ('images/obj/palette.svg', 'palette', S, 16),
    ('images/obj/dotgreen.svg', 'dot', C['green'], 14),
    ('images/obj/dotgrey.svg', 'dot', C['line'], 14),
    ('images/obj/dotredanim.svg', 'dot', C['coral'], 12),
    ('images/obj/dotyellowanim.svg', 'dot', C['amber'], 12),
    ('images/smartfolder.svg', 'smartfolder', S, 16),
    ('images/view.week.svg', 'week', S, 16),
    # Module copies of the same actions.
    ('modules/monitoringandcontrol/images/img.svg', 'calendar', B, 16),
    ('modules/timeplanning/images/img.svg', 'calendar', B, 16),
    ('modules/risks/images/view_icon.svg', 'eye', S, 16),
    ('modules/costs/images/view_icon.svg', 'eye', S, 16),
    ('modules/monitoringandcontrol/images/view_icon.svg', 'eye', S, 16),
    ('modules/timeplanning/images/view_icon.svg', 'eye', S, 16),
    ('modules/risks/images/stock_edit-16.svg', 'edit', B, 16),
    ('modules/costs/images/stock_edit-16.svg', 'edit', B, 16),
    ('modules/files/images/kedit.svg', 'edit', B, 16),
    ('modules/files/images/remove.svg', 'x-circle', R, 16),
    ('modules/files/images/folder5_small.svg', 'folder', S, 16),
    ('modules/files/images/filesaveas.svg', 'save', S, 16),
    ('modules/files/images/duplicate.svg', 'documents', S, 16),
    ('modules/files/images/co.svg', 'export', B, 16),
    ('modules/files/images/ci.svg', 'import', T, 16),
    ('modules/dotproject_plus/images/mais_verde.svg', 'plus', T, 18),
    ('modules/dotproject_plus/images/mais_azul.svg', 'plus', B, 18),
    ('modules/dotproject_plus/images/lapis.svg', 'edit', B, 18),
    ('modules/dotproject_plus/images/icone_seta.svg', 'caret-down', S, 12),
    ('modules/dotproject_plus/images/icone_seta_cima.svg', 'caret-up', S, 12),
    ('modules/dotproject_plus/images/dicionario.svg', 'ledger', S, 18),
    ('modules/projectdesigner/images/printer.svg', 'printer', S, 16),
    ('modules/projectdesigner/images/add.svg', 'plus-circle', T, 16),
    ('modules/macroprojects/images/plus.svg', 'plus-circle', T, 18),
    ('modules/macroprojects/images/minus.svg', 'remove-circle', R, 18),
    ('modules/human_resources/images/ok.svg', 'check', T, 16),
    ('modules/human_resources/images/not.svg', 'x-circle', R, 16),
]


def main():
    written = []
    for path, g, area in TILES:
        written.append((path, tile(g, area)))
    for path, g, colour, size in SMALL:
        written.append((path, small(g, colour, size)))
    for path, svg in written:
        full = os.path.join(ROOT, path)
        if not os.path.isdir(os.path.dirname(full)):
            sys.exit('no such directory: ' + os.path.dirname(path))
        with open(full, 'w') as f:
            f.write(svg)
    print(f'{len(written)} icons written')
    if '--preview' in sys.argv:
        preview(written)


def preview(written):
    """A page of every icon at 1x and enlarged, to check them side by side."""
    rows = []
    for path, svg in written:
        name = path.split('/')[-1]
        where = path.split('/')[1] if path.startswith('modules/') else path.rsplit('/', 1)[0]
        src = '../../' + path
        rows.append(f'<figure><img class="i" src="{src}"><img class="i big" src="{src}">'
                    f'<figcaption>{where}<br>{name}</figcaption></figure>')
    html = ('<!doctype html><meta charset="utf-8"><title>dotPulse icons</title><style>'
            'body{font:12px system-ui,sans-serif;background:#F4F6FA;color:#23262B;margin:20px}'
            'main{display:flex;flex-wrap:wrap;gap:10px}figure{margin:0;width:150px;background:#fff;'
            'border:1px solid #D9E0EA;border-radius:8px;padding:8px;text-align:center}'
            '.i{display:inline-block;vertical-align:middle;margin:4px}.big{width:64px;height:64px}'
            'figcaption{color:#5A6474;word-break:break-all}</style><main>' + ''.join(rows) + '</main>')
    with open(os.path.join(ROOT, 'tools', 'icons', 'preview.html'), 'w') as f:
        f.write(html)


if __name__ == '__main__':
    main()
