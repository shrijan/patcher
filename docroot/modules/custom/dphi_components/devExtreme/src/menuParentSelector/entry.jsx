import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.jsx'
import config from 'devextreme/core/config'
import { licenseKey } from '../../devextreme-license.ts'

config({ licenseKey })

const rootElement = document.getElementById('menu-parent-selector-root')
const menuLinks = JSON.parse(rootElement.dataset.menuOptions)
const selectedParent = rootElement.dataset.menuParent

ReactDOM.createRoot(rootElement).render(
  <React.StrictMode>
    <App menuLinks={menuLinks} selectedParent={selectedParent} />
  </React.StrictMode>,
)
