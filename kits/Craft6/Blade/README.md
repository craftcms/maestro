# Craft CMS + Blade Starter (Craft 6)

> [!WARNING]
> This branch targets Craft 6, which is in alpha. Page content uses Craft's core Markdown field until CKEditor supports Craft 6. For production sites, use the Craft 5 version on the `5.x` branch.

This is a basic blog starter project for [Craft CMS](https://craftcms.com/), with templates written in [Laravel Blade](https://laravel.com/docs/blade) instead of Twig. It's a simple install that requires no database dump, no plugins, and no front-end tooling.

> [!TIP]
> Check out our full list of [starter projects](https://craftcms.com/starters)!

## Features

- Dedicated places to edit global information and the primary top-level pages (_Home_, _Blog_, and _Guestbook_);
- _Blog_ and category feeds;
- Page content written in Markdown;
- Front-end _Guestbook_ form, protected by login;
- Arbitrary _Pages_ structure for additional, hierarchical content;
- Uploads stored in `public/uploads`, configured as the `cloud` disk in `config/filesystems.php`;

The Entries sidebar has also been configured to make these pages and sections easier to find for this setup.

## Installation

1. Clone this repository and move into the folder:

    ```bash
    git clone https://github.com/craftcms/starter-blade.git
    cd starter-blade/
    ```

2. Start DDEV:

    ```bash
    ddev start
    ```

3. Install dependencies:

    ```bash
    ddev composer install
    ```

4. Run the Craft setup wizard:

    ```bash
    ddev php artisan craft:install
    ```

Congratulations—Craft has been installed! You can start exploring the control panel by visiting `https://starter-blade.ddev.site/admin` in your browser, or running `ddev launch admin`.

## Post-Install

### Control Panel

The Craft [control panel](https://craftcms.com/docs/5.x/system/control-panel.html) is available at `https://starter-blade.ddev.site/admin`. Log in with the username and password you created during installation!

- Create some content by visiting the **Entries** screen;
- Use Live Preview to visualize edits as you make them;
- Adjust or augment the content model by exploring the **Settings** screen;
- Open the front-end by clicking the site name in the upper-left corner;

> [!TIP]
> You can always get back to the control panel by appending `/admin` to the project’s base URL.

### Project Structure

This project was scaffolded from our blank-slate started kit, and therefore follows its overall [directory structure](https://craftcms.com/docs/5.x/system/directory-structure.html) and [configuration scheme](https://craftcms.com/docs/5.x/configure.html).

### Templates

Templates live in `resources/views` as `.blade.php` files. Craft renders Blade templates the same way it renders Twig: each section's entry template is `_pages`, which picks a template from `resources/views/pages` based on the URL, section, and entry type.

Craft's template variables (`$entry`, `$craft`, `$currentSite`, `$currentUser`, `$siteName`, and so on) are available in Blade, and Craft's facades (`Sections`, `Markdown`, `I18N`, `Addresses`) are aliased globally. Craft also adds Blade directives such as `@craftPaginate`, `@craftCache`, and `@craftHead`/`@craftBeginBody`/`@craftEndBody`, which the layout uses to output registered scripts.

### Licensing

The free Craft _Solo_ [edition](https://craftcms.com/docs/5.x/editions.html) is active by default, and will automatically acquire a perpetual license to evaluate, develop, and deploy a site for yourself or a friend. This key will be written out to `config/craft/license.key`, and is safe to commit to version control.

You can upgrade to Craft _Team_ or _Pro_ any time! Read more about [licensing](https://craftcms.com/knowledge-base/how-craft-license-enforcement-works) in the Knowledge Base.

## Tips + Tricks

### Running on a Different Domain

The DDEV configuration files shipped with this project have a hard-coded project “name” that determines (among other things) the URL prefix. If you would like to change this (say, prior to pushing the project to a [new remote](#pushing-to-a-new-remote)), open `.ddev/config.yml` and update the `name` key.

Apply that change by running `ddev start` in the project directory. The site will restart, and be available at `https://{new-name}.ddev.site`!

### Pushing to a new Remote

When cloning a git repository, it typically sets up the origin as a default _remote_. To push the project to your own repository, follow these steps:

1. Remove the default origin:

    ```bash
    git remote remove origin
    ```

1. Create a new repository. This can be on GitHub, or any other git provider.
1. Add the new origin:

    ```bash
    git remote add origin https://github.com/your-username-or-org/new-repo-name.git
    ```

1. Push to the new origin:

    ```bash
    git push origin
    ```

> [!TIP]
> You may be able to create repositories in contexts that your local environment is not authorized to read from or write to! If you encounter permissions issues at this stage, consult the git provider’s documentation.

## Contributing

Thank you for considering contributing to this starter! It is generated by [Maestro](https://github.com/craftcms/maestro), which builds all of Craft's starter kits, so changes made directly in this repository are overwritten. Please make all contributions through Maestro.

## Getting Help

If you have any questions or suggestions, you can always reach us at <support@craftcms.com> or [post a GitHub issue](https://github.com/craftcms/starter-blade/issues).

Thanks for trying Craft!

:lemon:
