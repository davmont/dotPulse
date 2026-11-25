# Shared theme files

The layout and rules of the themes built on dotPulse's layout (dotpulse, and the coming
Facet and Linen). A folder of `style/` whose name starts with `_` is not a
theme: it is not offered in the theme lists (`dPstyleOptions()`).

- `*.css`: imported by a theme's `main.css` **in this order** (the cascade depends on it):
  `base`, `forms`, `header`, `tables`, `tabs`, `calendar`, `responsive`. They hold no
  colour, font or size of their own: those come from the theme's `--dp-*` variables.
- `tabbox.php`: the tab boxes and title blocks; a theme's `overrides.php` includes it.
- `appearance.js`: the Automatic appearance; `dPthemeStylesheets()` loads it in the
  `<head>` of the pages of a theme with a dark palette.

A theme's `main.css` imports these files and then defines every variable below in
`:root` (see `style/dotpulse/main.css`). Its dark palette, `dark.css` next to it,
redefines them; the page links it after `main.css` when it is dark (the Appearance
preference: `dPappearance()`, `dPthemeStylesheets()` and `dPappearanceAttributes()` in
`includes/main_functions.php`). Its `theme.json` declares the rest (`dPthemeInfo()`), and
`make theme-contrast` checks the colour pairs, light and dark.

| Variable | Used by the shared CSS |
| --- | --- |
| `--dp-blue` | yes |
| `--dp-blue-deep` | yes |
| `--dp-blue-light` | yes |
| `--dp-blue-wash` | yes |
| `--dp-blue-tint` | yes |
| `--dp-coral` | no (read by scripts or other stylesheets) |
| `--dp-coral-text` | yes |
| `--dp-coral-wash` | no (read by scripts or other stylesheets) |
| `--dp-teal` | yes |
| `--dp-teal-wash` | no (read by scripts or other stylesheets) |
| `--dp-ink` | yes |
| `--dp-slate` | yes |
| `--dp-line` | yes |
| `--dp-line-soft` | yes |
| `--dp-ground` | yes |
| `--dp-surface` | yes |
| `--dp-surface-alt` | yes |
| `--dp-row-line` | yes |
| `--dp-today` | yes |
| `--dp-weekend` | yes |
| `--dp-holiday` | yes |
| `--dp-muted` | yes |
| `--dp-faint` | yes |
| `--dp-control-line` | yes |
| `--dp-focus` | yes |
| `--dp-primary` | yes |
| `--dp-primary-hover` | yes |
| `--dp-on-primary` | yes |
| `--dp-banner` | yes |
| `--dp-hover-tile` | no (read by scripts or other stylesheets) |
| `--dp-shadow` | no (read by scripts or other stylesheets) |
| `--dp-focus-ring` | no (read by scripts or other stylesheets) |
| `--dp-font` | yes |
| `--dp-font-display` | yes |
