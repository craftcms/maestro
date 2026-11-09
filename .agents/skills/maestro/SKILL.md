---
name: maestro
description: >
  Maestro builds the Craft CMS starter kits and demos for Craft 5 and Craft 6 from layers in kits/.
  Use this skill when changing any kit or layer, adding a kit, building or syncing kits, updating
  Craft or the upstream skeleton, editing Europa's content, or changing the orchestrator or workflows.
---

# Maestro

Maestro builds each Craft starter kit by copying its layers from `kits/` into `build/`, in the order `kits.json` lists them. On every push to `main`, the Build and Push Kit Changes workflow builds every kit and pushes it to the repository and branch in `kits.json`. A fix lands once in a layer and reaches every kit that uses it.

README.md covers each mechanism in detail. This skill covers how to find the right layer, how to make a change, and how to check it.

## Layout

```
maestro/
├── kits.json       # every kit: its layers, Craft version, target repository and branch, optional seed
├── kits/           # the layers
├── orchestrator/   # Laravel app with the build, sync and update commands
├── build/          # the last built kit, git-ignored; edit it, then sync
└── .github/        # Tests, publishing, and the weekly skeleton and Craft updates
```

Kit handles end in their Craft version: `twig-5`, `wordpress-5`, `europa-5`, `nuxt-5`, `next-5`, `astro-5`, `twig-6`, `blade-6`, `nuxt-6`, `next-6`, `astro-6`. Craft 5 kits publish to the `5.x` branch of their repository and Craft 6 kits to `6.x`.

## Layers

Every kit stacks the same base layers for its Craft version, then up to three of its own. A later layer overrides an earlier one.

| Layer | What it holds | Used by |
| --- | --- | --- |
| `CraftN/Skeleton` | A mirror of `craftcms/craft@N.x`. Only `skeleton:sync` writes it. | every kit |
| `CraftN/DDEV` | `.ddev/config.yaml` with the project type, PHP and database | every kit |
| `CraftN/Base` | Composer files and the shared `composer.lock`, `.gitignore`, licence, and on Craft 5 the database `.env` settings | every kit |
| `CraftN/Guestbook` | The guestbook site's content model | `twig-5`, `twig-6`, `blade-6` |
| `CraftN/Headless` | Headless mode, GraphQL, CORS and content model under `backend/`, plus the backend's DDEV settings | Nuxt, Next and Astro kits |
| `Frontend/<fe>` | A front end that works on both Craft versions: `frontend/` and its DDEV ports for Nuxt, Next and Astro, and the shared templates for Twig | the kits of that front end |
| Kit layer | Everything only one kit needs: README, DDEV name, version-specific templates or `.env` overrides | one kit |

The kit layer is always last: `Craft5/Twig`, `Craft6/Blade`, `Craft5/WordPress`, `Craft5/Europa`, `Craft6/Nuxt` and so on. WordPress and Europa have no site-type layer and carry their own content model and `composer.lock`.

Two kit families copy layers into a subfolder with `{ "layer": ..., "into": ... }`:

- Headless kits copy `CraftN/Skeleton` and `CraftN/Base` into `backend/`. `CraftN/Headless` is copied to the root and keeps its Craft files under its own `backend/`, so it can also add to the root `.ddev/config.yaml`.
- Twig kits copy `Frontend/Twig` into `templates/` on Craft 5 and `resources/views/` on Craft 6.

Read `kits.json` for the exact stack of a kit before changing it.

### Where a change goes

Put a file in the lowest layer that every kit needing it shares, and nowhere else.

- A change for every kit of one Craft version goes in `CraftN/Base`, or in `CraftN/DDEV` for DDEV settings.
- A change to an upstream skeleton file goes in an override in a layer above the skeleton. Never edit `CraftN/Skeleton`. To drop a skeleton file from every kit, add it to that skeleton's `exclude` list in `kits.json` and run `php artisan skeleton:sync`.
- A change to a front end that works on both Craft versions goes in `Frontend/<fe>`. Code that only works on one version, such as `craft.app` on Craft 5 or Laravel helpers on Craft 6, goes in the kit layer as an override.
- A change for one kit goes in its kit layer.

Before duplicating a file into a second layer, check whether a shared layer below both kits should hold it. Before adding a new shared layer, check whether it saves enough to be worth another name in every stack. A layer for a handful of identical files across unrelated kits usually isn't.

### Merged files

`.env` files (`.env.example`, `.env.example.dev`) and `.ddev/config.yaml` merge across layers instead of overriding:

- The first layer with the file, usually the skeleton, provides all of it. Later layers hold only the keys they change or add.
- A `.env` key that an earlier layer has is replaced where it stands. A new key is appended with the comments directly above it.
- A `.ddev/config.yaml` top-level key replaces the earlier value whole, including nested maps and lists.

Keep these files minimal in every layer above the first. Never copy a whole `.env` or DDEV config into a kit layer.

## Commands

Run these from `orchestrator/`:

| Command | What it does |
| --- | --- |
| `php artisan build <kit>` | Builds a kit into `build/`, which becomes a Git repository snapshotted at build time |
| `php artisan sync [--layer=<layer>]` | Copies changes in `build/` back to the layers they came from |
| `php artisan skeleton:sync [<layer>...]` | Refreshes the skeleton layers from `craftcms/craft` |
| `php artisan craft:update <5\|6> [--constraint=^5.13.0] --db-database=<scratch db>` | Updates Craft, the locks and the project config for every kit of a version |
| `php artisan kits:matrix` | Prints the kits as the GitHub Actions matrix |
| `php artisan test` | Runs the orchestrator tests |
| `vendor/bin/pint` | Formats the orchestrator |

`craft:update` drops every table in the Postgres database it uses. Always point it at a scratch database.

## Making a change

1. Read the kit's stack in `kits.json` and find the layer that should own the change.
2. Edit `kits/` directly for small changes. For changes you need to run, build the kit, edit in `build/`, then run `php artisan sync`.
3. Check where `sync` put each file. It reports `Synced <path> to <layer>`.
   - A changed or deleted file goes to the most specific layer that has it.
   - A new file goes to the most specific layer that already has its directory. Pass `--layer=` to put it lower, for example `--layer=Frontend/Twig` for a template that works on both versions.
   - A `.env` or DDEV key goes to the most specific layer that sets it.
4. Rebuild every kit that uses the changed layers and check the result, as described below.
5. Commit in `kits/`, never in `build/`. Review `git status` first: files Craft writes during install, such as `config/license.key`, must not be committed.

Kits' `.gitignore` files apply inside `kits/` too. Add files they ignore on purpose, such as `web/uploads/img/starter-logo.svg`, with `git add -f`.

Kits ship `composer.lock` but not npm lock files. The lock pins Craft to the version that wrote the project config, because Craft refuses to install when the schema versions differ. Don't edit `composer.lock` or change the Craft version by hand. Use `craft:update`, which also upgrades the project config.

## Checking a change

**Refactors that should not change any kit.** Build every kit before and after the change and compare the output:

```bash
cd orchestrator
snapshot() { mkdir -p "$1"; for k in $(jq -r '.kits|keys[]' ../kits.json); do php artisan build $k >/dev/null && rsync -a --delete --exclude .git ../build/ "$1/$k/"; done; rm -rf ../build; }
snapshot /tmp/before
# ...make the change...
snapshot /tmp/after
diff -r /tmp/before /tmp/after
```

**Changes to what a kit does.** Install and run it the way the Tests workflow does. Follow the Install Craft step in `.github/workflows/tests.yml`. It installs against Postgres from the kit's own project config and fails if installing rewrote that config. For headless kits, install from `build/backend`. Craft 5 kits run `php craft` and serve `web/`. Craft 6 kits are Laravel applications that run `php artisan` and serve `public/`. Request the pages you changed. Kits with a `seed` in `kits.json` restore it with `php craft db/restore` and run `craft up` instead of installing.

**Orchestrator changes.** Run `php artisan test` and `vendor/bin/pint --test` in `orchestrator/`.

**Workflow changes.** Lint them with actionlint, for example `uvx --from actionlint-py actionlint .github/workflows/*.yml`.

DDEV doesn't run in every environment. If you change DDEV config and can't run DDEV, say so.

## Adding a kit

1. Choose the stack: the base layers for its Craft version, a site-type or front-end layer if one fits, and a new kit layer.
2. Create the kit layer with only what the kit needs: a README with the Contributing section the other kits have, the DDEV `name` and hostnames, and its `.env` overrides.
3. Add an entry to `kits.json` with `name`, `craft`, `layers`, `repository` and `branch` (`5.x` or `6.x`).
4. Add the kit to the matching table in README.md.
5. Build it, install it as described above, and check that the other kits still build unchanged.

The target repository must exist before the publish workflow can push to it.

## Europa

`europa-5` is a demo with real content. It installs from `kits/Craft5/Europa/seed.sql` instead of `craft install`. To change its content or content model:

1. Build it and restore `seed.sql` into a Postgres database.
2. Make the changes.
3. Run `php craft demos/dump` in `build/`, then `php artisan sync`.

Don't run `demos/seed` before dumping, because it randomises news dates and adds fake Freeform submissions. Images in `web/uploads` are at most 2560px wide and are committed. Their transforms are generated on demand and ignored.

## Craft 6

Craft 6 projects are Laravel applications. Their templates live in `resources/views`, Craft config in `config/craft`, and the web root is `public/`. The DDEV project type is `laravel`. While the Craft 6 ecosystem is in alpha, some Craft 5 plugins have no Craft 6 release, so the Craft 6 kits use stand-ins, such as a Markdown field instead of CKEditor. README.md's Craft 6 notes list them. There is no Craft 6 WordPress kit until `wp-import` and `verbb/comments` support Craft 6, and no Craft 6 Europa kit yet.

## Rules

1. Never edit `kits/*/Skeleton` by hand.
2. Commit in `kits/`, never in `build/`.
3. Keep merged files minimal: a layer holds only the keys it changes or adds.
4. Put each file in the lowest layer that every kit needing it shares, and keep `Frontend/*` layers free of version-specific code.
5. Update Craft only with `craft:update`, against a scratch database.
6. Pushing to `main` publishes every kit to its repository. Push only when asked.
