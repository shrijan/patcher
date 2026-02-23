import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.jsx'
import config from 'devextreme/core/config'
import { licenseKey } from '../../devextreme-license.ts'

config({ licenseKey })

const rootElement = document.getElementById('media-hierarchy-root')
const folders = JSON.parse(rootElement.dataset.folders)

ReactDOM.createRoot(rootElement).render(
  <React.StrictMode>
    <App folders={folders} />
  </React.StrictMode>,
)
