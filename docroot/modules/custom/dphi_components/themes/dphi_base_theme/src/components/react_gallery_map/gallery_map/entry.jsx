import React from 'react'
import ReactDOM from 'react-dom/client'
import ReactModal from 'react-modal'
import App from './App.jsx'
import { GoogleMapsAPIProvider } from './contexts/GoogleMapsContext.jsx'

document.addEventListener('DOMContentLoaded', () => {
  const rootElement = document.getElementById('map-root')
  ReactModal.setAppElement('#map-root') // Set element for react-modal
  if (rootElement) {
    const mapApiKey = rootElement.getAttribute('data-map-api-key')
    const mapID = rootElement.getAttribute('data-map-id')

    const pinClustering =
      rootElement.getAttribute('data-pin-clustering') === '1'

    const pinData = rootElement.getAttribute('data-pin-data')
    const pins = pinData ? JSON.parse(pinData) : []

    const termsData = rootElement.getAttribute('data-terms-data')
    const filterTerms = termsData ? JSON.parse(termsData) : []
    const filterLabel = rootElement.getAttribute('data-filter-label')
    const modalCtaLabel = rootElement.getAttribute('data-modal-cta-label')

    const parentTermSvg = rootElement.getAttribute('data-parent-svg')
    // console.log(pins)

    ReactDOM.createRoot(rootElement).render(
      <GoogleMapsAPIProvider apiKey={mapApiKey} mapID={mapID}>
        <App
          pins={pins}
          pinClustering={pinClustering}
          filterTerms={filterTerms}
          filterLabel={filterLabel}
          modalCtaLabel={modalCtaLabel}
          parentTermSvg={parentTermSvg}
        />
      </GoogleMapsAPIProvider>,
    )
  }
})
