<h1 align="center">Maestro</h1>

Maestro builds the [Craft CMS starter kits](https://craftcms.com/starters). Kits are made of layers in this repository. On every push to `main`, Maestro builds each kit and pushes the result to that kit's repository, so a fix lands once here instead of once per starter.

Forked from [laravel/maestro](https://github.com/laravel/maestro), which does the same for the Laravel starter kits.

## How kits are built

`kits.json` lists every kit and the layers it is built from. A build copies each layer into `build/` in order, and later layers override earlier ones:

```
kits/
  Craft5/
    Skeleton/   mirror of craftcms/craft@5.x (written only by `skeleton:sync`)
    DDEV/       the DDEV config every Craft 5 kit starts from: project type, PHP, database
    Base/       what every Craft 5 kit shares: Composer files and lock, database .env settings, .gitignore, licence
    Guestbook/  the guestbook site: content model, Cloud
    Twig/       starter-twig: the Twig templates that only work on Craft 5, DDEV project name, README
    WordPress/  starter-wordpress: WordPress-style content model, templates, its own Composer files for comments and wp-import, README
    Headless/   what headless kits share: backend/ with headless mode, GraphQL, CORS and the content model; DDEV backend settings and API server
    Nuxt/       starter-nuxt's DDEV project name, backend .env example, and README
    Next/       starter-next's DDEV project name, backend .env example, and README
    Astro/      starter-astro's DDEV project name, backend .env example, and README
    Europa/     the Europa Museum demo: its own Composer files, content model, templates, front-end build, images and seed.sql
  Craft6/
    Skeleton/   mirror of craftcms/craft@6.x
    DDEV/       the DDEV config every Craft 6 kit starts from
    Base/       what every Craft 6 kit shares: Composer files and lock, .gitignore, licence (the skeleton's .env.example already has database settings)
    Guestbook/  the guestbook site's content model, shared by Twig and Blade
    Twig/
    Blade/      starter-blade: the guestbook site in Blade templates, DDEV project name, README
    Headless/
    Nuxt/
    Next/
    Astro/
  Frontend/
    Twig/       starter-twig's templates that work with either Craft version, copied into templates/ or resources/views/
    Nuxt/       starter-nuxt's frontend/ and its DDEV ports, which work with either Craft version
    Next/       starter-next's frontend/ and its DDEV ports, which work with either Craft version
    Astro/      starter-astro's frontend/ and its DDEV ports, which work with either Craft version
```

Craft 5 kits:

| Kit | Layers | Pushed to |
| --- | --- | --- |
| `twig-5` | Craft5/Skeleton → Craft5/DDEV → Craft5/Base → Craft5/Guestbook → Frontend/Twig (into `templates/`) → Craft5/Twig | `craftcms/starter-twig@5.x` |
| `wordpress-5` | Craft5/Skeleton → Craft5/DDEV → Craft5/Base → Craft5/WordPress | `craftcms/starter-wordpress@5.x` |
| `nuxt-5` | Craft5/Skeleton → Craft5/Base (both into `backend/`) → Craft5/DDEV → Craft5/Headless → Frontend/Nuxt → Craft5/Nuxt | `craftcms/starter-nuxt@5.x` |
| `next-5` | Craft5/Skeleton → Craft5/Base (both into `backend/`) → Craft5/DDEV → Craft5/Headless → Frontend/Next → Craft5/Next | `craftcms/starter-next@5.x` |
| `astro-5` | Craft5/Skeleton → Craft5/Base (both into `backend/`) → Craft5/DDEV → Craft5/Headless → Frontend/Astro → Craft5/Astro | `craftcms/starter-astro@5.x` |
| `europa-5` | Craft5/Skeleton → Craft5/DDEV → Craft5/Base → Craft5/Europa | `craftcms/europa-museum@5.x` |

Craft 6 kits:

| Kit | Layers | Pushed to |
| --- | --- | --- |
| `twig-6` | Craft6/Skeleton → Craft6/DDEV → Craft6/Base → Craft6/Guestbook → Frontend/Twig (into `resources/views/`) → Craft6/Twig | `craftcms/starter-twig@6.x` |
| `blade-6` | Craft6/Skeleton → Craft6/DDEV → Craft6/Base → Craft6/Guestbook → Craft6/Blade | `craftcms/starter-blade@6.x` |
| `nuxt-6` | Craft6/Skeleton → Craft6/Base (both into `backend/`) → Craft6/DDEV → Craft6/Headless → Frontend/Nuxt → Craft6/Nuxt | `craftcms/starter-nuxt@6.x` |
| `next-6` | Craft6/Skeleton → Craft6/Base (both into `backend/`) → Craft6/DDEV → Craft6/Headless → Frontend/Next → Craft6/Next | `craftcms/starter-next@6.x` |
| `astro-6` | Craft6/Skeleton → Craft6/Base (both into `backend/`) → Craft6/DDEV → Craft6/Headless → Frontend/Astro → Craft6/Astro | `craftcms/starter-astro@6.x` |

Put a file in the lowest layer that every kit needing it shares. To add a kit, add its layer folder and an entry in `kits.json`.

A layer can be copied into a subfolder instead of the build root, the way headless kits keep Craft in `backend/`:

```json
"layers": [
    { "layer": "Craft5/Skeleton", "into": "backend" },
    { "layer": "Craft5/Base", "into": "backend" },
    "Craft5/DDEV",
    "Craft5/Headless",
    "Craft5/Nuxt"
]
```

`Craft5/Headless` is copied to the build root, with its Craft files under its own `backend/`, so it can also add to the root `.ddev/` config.

`Frontend/Twig` is copied into `templates/` on Craft 5 and `resources/views/` on Craft 6. It holds only templates that work on both. `Craft5/Twig` and `Craft6/Twig` override the few that use version-specific APIs, such as `craft.app` on Craft 5 or Laravel's `session()` on Craft 6. `sync` puts a new template in `CraftN/Twig`; pass `--layer=Frontend/Twig` when it works on both.

### .env files

`.env` files (`.env.example`, `.env.example.dev`, and so on) merge across layers instead of overriding. The first layer that has one provides the whole file, usually the skeleton. Later layers hold only the keys they change or add:

- A key the file already has is replaced where it stands. Comment lines directly above the key replace its comments; without any, its comments stay.
- A new key is appended at the end, with the comment lines directly above it. Keys separated by a blank line stay separate groups.
- A comment block at the top of the layer's file, followed by a blank line, is added to the top of the merged file.

For example, `Craft6/Nuxt/backend/.env.example` is one line, `APP_NAME="Nuxt Starter"`, and the rest comes from the skeleton and `Craft6/Headless`. A skeleton update then reaches every kit without touching their `.env` files.

### DDEV config

`.ddev/config.yaml` merges the same way, by top-level key. A later layer's copy holds only the keys it changes or adds, and each key replaces the earlier value whole, including nested maps and lists. `CraftN/DDEV` sets the project type, PHP version (8.5 for both Craft versions) and database (MySQL 8.0 on Craft 5, PostgreSQL 17 on Craft 6). `CraftN/Headless` adds the `backend/` docroot and Composer root, `Frontend/<FE>` adds the front end's ports and dev server, and each kit sets its `name` and the hostnames built from it. `sync` writes each changed key back to the layer that sets it.

The headless kits' `api-site.conf` lives in `CraftN/Headless` and answers on `server_name api.*`, so it works for any project name.

Headless kits share one Craft backend, so its project config can't name the site after one front end. The site and system name are `$CRAFT_SITE_NAME` on Craft 5 and `$APP_NAME` on Craft 6, which each kit sets in its `backend/.env.example.dev` or `backend/.env.example`.

## Working on a kit

```bash
cd orchestrator
composer install

php artisan build twig-5   # build a kit into ../build
# ...install, run, and edit the kit in ../build...
php artisan sync           # copy your edits back into kits/
```

`build/` is its own Git repository, snapshotted at build time, so `sync` copies back exactly what changed since then, following the kit's `.gitignore`:

- A changed or deleted file goes to the most specific layer that has it.
- A new file goes to the most specific layer that already has its directory, or to `--layer=`.
- Skeleton layers are never written; changing a skeleton file creates an override in a layer above it.
- Deleting a file only the skeleton has doesn't stick, because it comes back on the next build. To drop a skeleton file from every kit, list it under `exclude` for that skeleton in `kits.json` and run `php artisan skeleton:sync`. The Craft 6 skeleton excludes `resources/views/index.twig`, Craft's welcome page, which no starter serves.
- A merged `.env` file syncs key by key. A changed key goes to the most specific layer that sets it, or to the layer that owns the file if only the skeleton sets it. A new key goes to the layer that owns the file. A deleted key is removed from every layer that sets it; a key only the skeleton sets comes back on the next build. Moved keys and comments that aren't directly above a key can't be synced, so `sync` warns about them.

Kits ship `composer.lock` but not `package-lock.json`, which `build` keeps out of `sync`. Laravel's starter kits ship neither, but Craft refuses to install when its schema version differs from the one in the project config, so the lock pins Craft to the version that wrote it.

Review `git status` before committing. Files Craft generates during install, such as `config/license.key`, will show up too.

### Updating Craft

The kits of each Craft version share one `composer.lock` in `Craft5/Base` or `Craft6/Base`, except `wordpress-5`, whose plugins need their own in `Craft5/WordPress`. Project config YAML is tied to the Craft version that wrote it, so updating Craft means installing on the old version, then upgrading. `craft:update` does that for every kit of a Craft version, using a scratch Postgres database whose tables it drops:

```bash
php artisan craft:update 5 --constraint=^5.13.0 --db-database=craft_update
php artisan craft:update 6 --db-database=craft_update   # the newest alpha within ^6.0.0
```

For each lock and project config pair (`twig-5` or `twig-6` for Guestbook, `wordpress-5`, and `nuxt-5` or `astro-6` for Headless), it builds the kit, installs it on the locked version, raises the `craftcms/cms` constraint, runs `composer update` and `craft up`, and syncs the new lock and project config back. Nothing else the install writes, such as `config/license.key`, is synced. A kit whose lock an earlier run already updated still installs on the old lock first, then takes the updated one. Leave out `--constraint` to update within the current one. The database options default to `craft`/`craft` on `127.0.0.1:5432`.

While Craft 6 is in alpha, nearly every alpha changes the schema version, so without the lock a fresh install would pick up a newer alpha than the project config was written with and fail.

The Tests workflow fails when a kit's project config doesn't match its Craft version.

### Kits with content

A demo such as Europa ships real content, so it installs from a database dump instead of `craft install`. Its `kits.json` entry names the dump and the command that writes it:

```json
"seed": { "file": "seed.sql", "dump": "php craft demos/dump" }
```

The Tests workflow restores the dump and runs `craft up`, and `craft:update` restores it, updates, then rewrites it with the dump command and syncs it back with the lock and project config.

To change Europa's content or content model, build it, restore `seed.sql` into a Postgres database, make the changes, run `php craft demos/dump`, and `php artisan sync`. Don't run `demos/seed` before dumping: it randomises news dates and adds fake Freeform submissions. The dump keeps only the latest revision of each element, leaves out caches, sessions and logs, and sorts each table's rows, so its diff shows only what changed. Images in `web/uploads` are at most 2560px; their transforms are generated on demand and ignored.

## Automation

- **Tests** runs the orchestrator tests, then builds every kit, installs it against Postgres from its own project config, fails if installing rewrote that config, builds the frontend if the kit has one, and requests the control panel login page (plus the home page for non-headless kits).
- **Build and Push Kit Changes** builds every kit on `main` and pushes the result to the kit's repository and branch. It needs a `STARTER_KIT_TOKEN` secret that can push to those repositories. The two workflows below open their pull requests with it too, because pull requests opened with the default `GITHUB_TOKEN` don't trigger Tests. So it also needs write access to contents and pull requests on this repository.
- **Sync Craft Skeleton** opens a weekly pull request that refreshes the `*/Skeleton` layers from `craftcms/craft`.
- **Update Craft** runs `craft:update 5` and `craft:update 6` weekly against a Postgres service and opens a pull request per Craft version with the updated locks and project config. Each version runs on its own, so a broken Craft release only blocks its own update.

## Craft 6 notes

Craft 6 projects are Laravel applications, so their DDEV project type is `laravel`. The Craft 6 layers follow the `craftcms/craft@6.x` skeleton: templates live in `resources/views`, Craft config in `config/craft`, and the web root is `public/`. The Craft 6 project config was produced by upgrading the Craft 5 kit's database with `php artisan craft:up` and writing it out again.

While the Craft 6 ecosystem is in alpha:

- `pageContent` is a core Markdown field, because `craftcms/ckeditor` has no Craft 6 release yet.
- The `cloud` filesystem is a local Laravel disk in `config/filesystems.php`, because `craftcms/cloud` has no Craft 6 release yet.

The Craft 6 headless backend replaces Craft 5 pieces with their Laravel equivalents:

- The GraphQL API is served at `/api` by `routes/api.php`, which `bootstrap/app.php` registers. It runs Craft's `craft` middleware but not `craft.web`, so requests are stateless: they authenticate with a bearer token or fall back to the public schema, and need no CSRF token. Craft 6 no longer reads site URL rules from `config/craft/routes.php`.
- `config/cors.php` allows the front end (`PRIMARY_SITE_URL`) to call it, replacing `allowedGraphqlOrigins`.
- `app/Listeners/AddAuthorFieldsToGql.php` adds `authorId` and `authorName` to entries, replacing the `authorinfo` Yii module. Laravel discovers it from its type-hinted event.
- `@preview` defaults to an empty string, because Craft 6 alpha crashes on a null alias.
