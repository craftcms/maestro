# Maestro

Maestro builds the Craft CMS starter kits from layers in `kits/`. `kits.json` lists each kit's layers and the repository and branch it is pushed to. See README.md.

- Build: `cd orchestrator && php artisan build <kit>` writes the kit to `build/`.
- Sync edits back: `php artisan sync` (from `orchestrator/`) after editing in `build/`. Commit in `kits/`, never in `build/`.
- Test the orchestrator: `cd orchestrator && php artisan test`.
- `.env` files merge across layers: a layer above the skeleton holds only the keys it changes or adds (see README.md).
- Never edit `kits/*/Blank` by hand. These layers mirror `craftcms/craft` and are only written by `php artisan skeleton:sync`. Override a skeleton file in a layer above it.
- Project config YAML must match the Craft version in the kit's `composer.lock`. When updating Craft, install on the old version, update, run `craft up`, then sync (see README.md).
- Kits' `.gitignore` files apply inside `kits/` too. Add files they ignore, such as `web/uploads/img/starter-logo.svg`, with `git add -f`.
