# Development tools

Node.js tooling used only during development and in CI. Nothing here is needed
on a server, and a release archive (`git archive`) leaves this directory out.

- `jest/`: Jest tests for the scripts in `js/` (`make js-test`, runs in the
  `node:22` image, so no local Node is needed).
- `browser/`: Playwright checks for the docker audit suite (`make audit-full`,
  see `docker/README.md`): add/edit forms, layout, tab switching and pop-ups.
  Install with `cd tools/browser && npm ci`; they drive an installed Chrome.
- `brand/` and `icons/`: Python scripts that build the logo files (`images/brand/`) and
  the interface icons (SVG next to the bitmaps they replace; `--preview` writes a page of all
  of them).
