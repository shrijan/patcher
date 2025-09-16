import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.jsx'
import '../react-lists.scss'

const rootElement = document.getElementById('react-filtered-lists-app')
const items = JSON.parse(rootElement.dataset.items)
const filters = JSON.parse(rootElement.dataset.filters)
const displayLayout = parseInt(rootElement.dataset.displayLayout)
const showFilters = rootElement.dataset.showFilters === '1'
const theme = rootElement.dataset.theme

ReactDOM.createRoot(rootElement).render(
  <React.StrictMode>
    <App
      items={items}
      filters={filters}
      displayLayout={displayLayout}
      showFilters={showFilters}
      theme={theme}
    />
  </React.StrictMode>,
)
