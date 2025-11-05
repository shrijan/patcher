# Rapid Start Components and Base Theme

This module/theme pair contains everything needed to install Rapid Start on a Drupal website.

The consuming website should:

* Require and enable the module.
* Create a sub-theme in `/themes/custom` using `dphi_base_theme` as the base theme (try `drush generate theme`).
* Put any theme alterations in the sub-theme, not the base theme.

## Deployment

To release a new stable version with an automatically set calver tag (YYYY.MM.x), merge `develop` into `main`.

The Rapid Start website uses the `develop` branch in the `dev` environment and the latest release at the time of build
in `test` and `prod`.

## Development

This module should primarily be developed from within the
[Rapid Start Latest](https://bitbucket.org/oehgovernance/rapid-start-latest/src/master/) repository where it is
installed as a git submodule.

In that project use `ddev ssh` to enter the container shell, then within that shell:

```bash
cd docroot/modules/custom/dphi_components/themes/dphi_base_theme
npm install
npm run dev
```

To benefit from HMR you need to clear Drupal's library cache **once vite is running**. Use `ddev drush cr` in another
window.

** Note that JQuery is loaded by the admin interface, it is not available to anonymous users! **

## Library creation

Drupal has an excellent library system that only loads libraries to the browser when they're required, this theme
uses vite to output individual libraries and the Drupal vite module to help with this while still providing HMR for
development purposes. There are some structural rules to follow:

* For each library create a folder in a relevant place, such as a subfolder of 'components'
* Ensure your folder name is unique, your assets will be named after the folder
* Create an `entry.js` or `entry.scss` file in that folder which is the root for that library
* Your entry file must import all other assets, it could be an entire React application or just one line importing a DDS
  component
* Start or restart the vite dev server to ensure the new entry point is picked up - this will update the manifest
* Open `dist/.vite/manifest.json` and ensure your entry file is there along with any expected assets
* Create an entry in `dphi_base_theme.libraries.yml` for your library from an existing one
* Clear Drupal's cache

Once this is done you should be able to see any changes you make as soon as you save the file you're working on as long
as your new library is attached to the current page.

## Site project development

The module can also be developed as part of a project to craft a site which utilises it. This is typically necessary
when such a site has commissioned additional components which are to be included in the Rapid Start framework.

### Set up git submodule

In the consuming site, run:

```bash
cd docroot/modules/custom
git submodule add git@bitbucket.org:oehgovernance/rapid-start-dphi-components.git dphi_components
```

Then:

* Create a sub-theme
* Expose the desired ports in DDEV
* Add the bitbucket repository to composer.json
* Set up the pipeline using rapid-start-latest as an example

### Sub-theme creation

Sites which use this module should create a sub-theme which has dphi_base_theme as the base theme. A starterkit is
available to make creating a sub-theme easy:

```bash
cd docroot
php core/scripts/drupal generate-theme --starterkit rapid_start_starterkit new_theme_name --path themes/custom
```

Change `new_them_name` in the above commands to something site-specific.

Once the sub-theme is generated, it is recommended to remove the version, generator and starterkit keys from the .info
file.
