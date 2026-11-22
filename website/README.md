# dotPulse documentation website

Built with [Zensical](https://zensical.org/) (the successor of Material for MkDocs, same
authors) from `mkdocs.yml`, published to GitHub Pages from `master` by `.github/workflows/docs.yml`
(every pull request builds it with `--strict`, which fails on a broken link, and
`tools/checks/check-doc-images.py`, which fails on a missing image).

    make docs          # build into website/site/ (as CI does)
    make docs-serve    # preview on http://127.0.0.1:8000, reloads on save

The Spanish site is `docs-es/` with `mkdocs.es.yml`, built into `site/es/` by `make docs`
(`make docs-serve-es` previews it on port 8001). It has the same pages, file names and screenshot
names as `docs/`, and each Spanish heading keeps the English anchor (`## Añadir un usuario { #add-a-user }`),
so links, the language button and the in-app Help, which shows the Spanish pages to users whose
language is Spanish, work the same in both. UI labels in the Spanish pages are those of the Spanish
interface (`locales/es/`). When you change an English page, change the Spanish one too.

Keep the configuration in `mkdocs.yml` (Zensical reads it; don't convert it to
`zensical.toml`): the in-app Help (`classes/help.class.php`) takes its page list from its `nav`.
`theme.variant: classic` keeps the Material look with the brand-blue header; the colours are in
`docs/stylesheets/dotpulse.css`. The Zensical image is pinned by digest in the `Makefile` and
the workflow; update both together.

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

For the Spanish site, switch the sandbox to Spanish (`UPDATE dotp_user_preferences SET pref_value='es'
WHERE pref_user=0 AND pref_name='LOCALE'` and `host_locale` = `es`), prepare the same data (the admin
shots' notes, in Spanish), and run with `SHOT_LANG=es ... website/docs-es/img`: the texts the steps
click are looked up in `locales/es/`. The installer shots stay English (the installer is not
translated): copy them from `docs/img/`.

## Licence

The documentation (text and screenshots in `website/`) is under
[Creative Commons Attribution-ShareAlike 4.0](https://creativecommons.org/licenses/by-sa/4.0/).
Code examples in it (commands, configuration, PHP) are under the code's licence,
GPL-2.0-or-later, so they can be copied into the program. By contributing to these
pages you agree to publish your changes under these terms.
