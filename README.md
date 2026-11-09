<h1 align="center">Maestro</h1>

Maestro builds the [Craft CMS starter kits](https://craftcms.com/starters). Kits are made of layers in this repository. On every push to `main`, Maestro builds each kit and pushes the result to that kit's repository, so a fix lands once here instead of once per starter.

Forked from [laravel/maestro](https://github.com/laravel/maestro), which does the same for the Laravel starter kits.

## How kits are built

`kits.json` lists every kit and the layers it is built from. A build copies each layer into `build/` in order, and later layers override earlier ones:

```
kits/
  Craft5/
    Blank/      mirror of craftcms/craft@5.x (written only by `skeleton:sync`)
    Starter/    what every Craft 5 starter shares: content model, templates, DDEV, Cloud
    Twig/       what only starter-twig adds
    Headless/   the Craft backend headless kits share: headless mode, GraphQL, CORS, content model
    Nuxt/       starter-nuxt's Craft 5 DDEV config, backend .env example, and README
    Next/       starter-next's frontend/, DDEV config, backend .env example, and README
  Craft6/
    Blank/      mirror of craftcms/craft@6.x
    Starter/
    Twig/
    Headless/
    Nuxt/
  Shared/
    Nuxt/       starter-nuxt's frontend/, which works with either Craft version
```

| Kit | Layers | Pushed to |
| --- | --- | --- |
| `twig` | Craft5/Blank → Craft5/Starter → Craft5/Twig | `craftcms/starter-twig@5.x` |
| `twig-6` | Craft6/Blank → Craft6/Starter → Craft6/Twig | `craftcms/starter-twig@6.x` |
| `nuxt` | Craft5/Blank → Craft5/Headless (both into `backend/`) → Shared/Nuxt → Craft5/Nuxt | `craftcms/starter-nuxt@5.x` |
| `next` | Craft5/Blank → Craft5/Headless (both into `backend/`) → Craft5/Next | `craftcms/starter-next@5.x` |
| `nuxt-6` | Craft6/Blank → Craft6/Headless (both into `backend/`) → Shared/Nuxt → Craft6/Nuxt | `craftcms/starter-nuxt@6.x` |

Put a file in the lowest layer that every kit needing it shares. To add a kit, add its layer folder and an entry in `kits.json`.

A layer can be copied into a subfolder instead of the build root, the way headless kits keep Craft in `backend/`:

```json
"layers": [
    { "layer": "Craft5/Blank", "into": "backend" },
    { "layer": "Craft5/Headless", "into": "backend" },
    "Craft5/Nuxt"
]
```

Headless kits share one Craft backend, so its project config can't name the site after one front end. The site and system name are `$CRAFT_SITE_NAME`, which each kit sets in its `backend/.env.example.dev`.

## Working on a kit

```bash
cd orchestrator
composer install

php artisan build twig     # build a kit into ../build
# ...install, run, and edit the kit in ../build...
php artisan sync           # copy your edits back into kits/
```

`build/` is its own Git repository, snapshotted at build time, so `sync` copies back exactly what changed since then, following the kit's `.gitignore`:

- A changed or deleted file goes to the most specific layer that has it.
- A new file goes to the most specific layer that already has its directory, or to `--layer=`.
- Skeleton layers are never written; changing a skeleton file creates an override in a layer above it.

Review `git status` before committing. Files Craft generates during install, such as `config/license.key`, will show up too.

### Updating Craft in a kit

Project config YAML is tied to the Craft version that wrote it, so updating Craft means installing on the old version, then upgrading:

1. Build the kit and `craft install` it with its current `composer.lock`.
2. Raise the constraint in `composer.json`, run `composer update`, then `craft up`.
3. Run `php artisan sync` to bring back the new `composer.lock` and the rewritten project config.

The Tests workflow fails when a kit's project config doesn't match its Craft version.

## Automation

- **Tests** runs the orchestrator tests, then builds every kit, installs it against Postgres from its own project config, fails if installing rewrote that config, builds the frontend if the kit has one, and requests the control panel login page (plus the home page for non-headless kits).
- **Build and Push Kit Changes** builds every kit on `main` and pushes the result to the kit's repository and branch. It needs a `STARTER_KIT_TOKEN` secret that can push to those repositories.
- **Sync Craft Skeleton** opens a weekly pull request that refreshes the `*/Blank` layers from `craftcms/craft`.

## Craft 6 notes

Craft 6 projects are Laravel applications. The Craft 6 layers follow the `craftcms/craft@6.x` skeleton: templates live in `resources/views`, Craft config in `config/craft`, and the web root is `public/`. The Craft 6 project config was produced by upgrading the Craft 5 kit's database with `php artisan craft:up` and writing it out again.

While the Craft 6 ecosystem is in alpha:

- `pageContent` is a core Markdown field, because `craftcms/ckeditor` has no Craft 6 release yet.
- The `cloud` filesystem is a local Laravel disk in `config/filesystems.php`, because `craftcms/cloud` has no Craft 6 release yet.

The Craft 6 headless backend replaces Craft 5 pieces with their Laravel equivalents:

- The GraphQL API is served at `/api` by `routes/api.php`, which `bootstrap/app.php` registers. It runs Craft's `craft` middleware but not `craft.web`, so requests are stateless: they authenticate with a bearer token or fall back to the public schema, and need no CSRF token. Craft 6 no longer reads site URL rules from `config/craft/routes.php`.
- `config/cors.php` allows the front end (`PRIMARY_SITE_URL`) to call it, replacing `allowedGraphqlOrigins`.
- `app/Listeners/AddAuthorFieldsToGql.php` adds `authorId` and `authorName` to entries, replacing the `authorinfo` Yii module. Laravel discovers it from its type-hinted event.
- `@preview` defaults to an empty string, because Craft 6 alpha crashes on a null alias.
