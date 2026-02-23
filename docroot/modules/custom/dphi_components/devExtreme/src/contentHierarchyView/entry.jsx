import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.jsx'
import config from 'devextreme/core/config'
import { licenseKey } from '../../devextreme-license.ts'

config({ licenseKey })

const rootElement = document.getElementById('content-hierarchy-root')
const contentTypes = JSON.parse(rootElement.dataset.contentTypes)
const menuNames = JSON.parse(rootElement.dataset.menuNames)
const moderationStates = JSON.parse(rootElement.dataset.moderationStates)

ReactDOM.createRoot(rootElement).render(
  <React.StrictMode>
    <App
      contentTypes={contentTypes}
      menuNames={menuNames}
      moderationStates={moderationStates}
    />
  </React.StrictMode>,
)
