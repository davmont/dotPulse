# dotPulse documentation website

MkDocs Material, published to GitHub Pages from `master` by `.github/workflows/docs.yml`
(every pull request builds it with `--strict`: a broken link or a missing image fails).

    make docs          # build into website/site/ (as CI does)
    make docs-serve    # preview on http://127.0.0.1:8000, reloads on save

Pages are Markdown in `docs/`. Write from the running application, never from the old
dotProject manuals (they have no licence): check labels in `locales/en/` and the code.

## Screenshots

`screenshots.json` lists every picture: page, user, crop and the clicks before it.
`make docs-screenshots` takes them all from the sandbox into `docs/img/`.

Prepare the sandbox first:

1. `docker/up.sh && (cd docker && ./reset-db.sh)`, then `docker/tests/modules.sh install`,
   `docker/tests/modules.sh toggle` and `docker/tests/modules.sh menu links smartsearch holiday helpdesk`;
   load the sample data: `docker compose -f docker/compose.yml exec web php db/sample/sample-data.php`.
2. Turn off the add-ons the guide does not cover, so pages show the tabs a typical site has
   (the guide uses dotproject_plus, holiday, links and smartsearch).
3. Add what the sample data lacks: three files on project 1 (one checked out), two links,
   the dark theme for marco (`UISTYLE` = `dotpulse-dark`), two-step login for lena.

Shots with a `note` need more than that; the note says what:

- `gs-modules-upgrade`: a module with an older version in the database.
- `ug-project-tips`: the add-ons the tips look at (initiating, risks, costs...) turned on.
- `gs-installer-*`: a second web container serving the code with an empty, writable
  `includes/config.php` on port 8092, and an empty database it may use; `gs-installer-upgrade`
  uses the sandbox itself.

Take one or a few again with `node tools/browser/screenshots.js website/screenshots.json website/docs/img <name> ...`.
