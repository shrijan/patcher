# Content Hierarchy

This module was build specifically for NSW Department of Planning and
Environment as a tool for handling sites which have hundreds of nodes and need
to see them as a hierarchy based on a menu.

> To meet library licencing requirements, if you are altering anything inside
> this `devExtreme` directory you **must** have a current
> [licence for DevExtreme](https://js.devexpress.com/Buy/) (one per developer).
>
> [Install the licence](https://js.devexpress.com/React/Documentation/Guide/Common/Licensing/#:~:text=Obtain%20Your%20License%20Key,instructions%20to%20copy%20your%20key.)

## Development

In order to have the best experience working on this module you will need to:

- Set up basic Drupal project using ddev
- Open the 5173 port in ddev:
  ```yaml
  web_extra_exposed_ports:
    - name: vite
      container_port: 5173
      http_port: 5174
      https_port: 5173
  ```
- Start the ddev server
- Install the vite module `ddev composer require drupal/vite && ddev drush en vite`
- Make sure `../js/react-refresh.js` references your local ddev domain on line 1
- Install dependencies and start the dev server inside the ddev container:
  ```bash
  ddev ssh
  cd web/modules/custom/dphi_components/devExtreme
  npm install
  npm run dev
  ```
- In another terminal window clear caches `ddev drush cr`

With all that in place you should be able to navigate to pages that use the
React components, make edits to those components and see your changes without
refreshing the browser (hot module reloading).

If you find the page is failing, try clearing the Drupal cache because the
vite module alters the library definitions based on whether the dev server is
running or not.

### Theme

The DevExtreme library has a theme generator for its widgets that must be used.
If you add a new widget and the layout or styling is off, add the relevant key
to app/theme/theme_metadata.json under "widgets" and regenerate the theme:
```bash
ddev ssh
cd web/modules/custom/dphi_components/devExtreme
npm install -g devextreme-cli
npm run build:theme
```
You can work out the relevant key using the online
[Theme Builder](https://devexpress.github.io/ThemeBuilder/)
