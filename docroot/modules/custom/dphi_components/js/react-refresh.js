function loadRefreshRuntime(RefreshRuntime) {
  const injectIntoGlobalHook = RefreshRuntime.default.injectIntoGlobalHook;
  injectIntoGlobalHook(window);
  window.$RefreshReg$ = () => {};
  window.$RefreshSig$ = () => (type) => type;
  window.__vite_plugin_react_preamble_installed__ = true;
}

import('https://' + window.location.hostname + ':5173/@react-refresh')
  .then((RefreshRuntime) => {
    loadRefreshRuntime(RefreshRuntime);
  })
  .catch(() => {
    import('https://' + window.location.hostname + ':5175/@react-refresh')
      .then((RefreshRuntime) => {
        loadRefreshRuntime(RefreshRuntime);
      })
      .catch(() => {
        console.log('Could not load RefreshRuntime from the vite dev server');
      });
  });
