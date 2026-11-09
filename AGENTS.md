# Maestro

Maestro builds the Craft CMS starter kits from layers in `kits/`. `kits.json` lists each kit's layers and the repository and branch it is pushed to. See README.md. Use the `maestro` skill before making changes.

- Build: `cd orchestrator && php artisan build <kit>` writes the kit to `build/`.
- Sync edits back: `php artisan sync` (from `orchestrator/`) after editing in `build/`. Commit in `kits/`, never in `build/`.
- Test the orchestrator: `cd orchestrator && php artisan test`.
- `.env` files and `.ddev/config.yaml` merge across layers: a later layer holds only the keys it changes or adds (see README.md).
- Never edit `kits/*/Skeleton` by hand. These layers mirror `craftcms/craft` and are only written by `php artisan skeleton:sync`. Override a skeleton file in a layer above it, or drop it from every kit by adding it to the skeleton's `exclude` list in `kits.json` and running `skeleton:sync`.
- Project config YAML must match the Craft version in the kit's `composer.lock`. Update Craft with `php artisan craft:update <5|6> --constraint=<constraint>`, which installs on the old version, updates, runs `craft up`, and syncs (see README.md). It drops every table in the Postgres database you point it at, so use a scratch one.
- Europa (`europa-5`) installs from `kits/Craft5/Europa/seed.sql`, not `craft install`. After changing its content or content model on a database restored from that dump, run `php craft demos/dump` in `build/`, then `php artisan sync`.
- Kits' `.gitignore` files apply inside `kits/` too. Add files they ignore, such as `web/uploads/img/starter-logo.svg`, with `git add -f`.
- `.agents/skills/maestro/SKILL.md` and `.claude/skills/maestro/SKILL.md` are the same skill for different agents. Change both together.
