# Decision Tool

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
  cd web/modules/custom/dphi_components/modules/decision_tool/react
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
