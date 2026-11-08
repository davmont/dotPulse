# Development tools

Tooling used only during development and in CI. Nothing here is needed on a
server, and a release archive (`git archive`) leaves this directory out.

- `phpstan/`: PHPStan configuration, its baseline of existing findings and the
  constants it needs (`make phpstan`; `make phpstan-baseline` rewrites the baseline).
- `checks/`: the raw superglobal and inline JavaScript checks with their baselines
  (`make raw-input`, `make inline-js`; `--update` rewrites a baseline).
  `theme-contrast.py` checks the colours of the dotPulse themes against WCAG AA
  (`make theme-contrast`).
- `locales/`: the translation check (`make locales`).
- `jest/`: Jest tests for the scripts in `js/` (`make js-test`, runs in the
  `node:22` image, so no local Node is needed).
- `browser/`: Playwright checks for the docker audit suite (`make audit-full`,
  see `docker/README.md`): add/edit forms, layout, tab switching and pop-ups.
  Install with `cd tools/browser && npm ci`; they drive an installed Chrome.
- `brand/` and `icons/`: Python scripts that build the logo files (`images/brand/`) and
  the interface icons (SVG next to the bitmaps they replace; `--preview` writes a page of all
  of them).
