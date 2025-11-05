import React from 'react'
import { useGoogleMapsAPI } from './contexts/GoogleMapsContext'
import MapComponent from './components/map/MapComponent'
import ListView from './components/listing/ListView'
import SearchAndFilter from './components/searchfilter/SearchAndFilter'
import { HoveredPinProvider } from './contexts/useHoveredPinState'
import './assets/scss/style.scss'

function TabsComponent({
  pins,
  pinClustering,
  filterTerms,
  filterLabel,
  parentTermSvg,
  modalCtaLabel,
}) {
  const { activeTab, setActiveTab } = useGoogleMapsAPI()

  const listClass = `list-view-container ${activeTab === 'list' ? 'active' : ''}`
  const mapClass = `mapContainer ${activeTab === 'map' ? 'active' : ''}`

  const mapButtonClass = activeTab === 'map' ? 'active' : ''
  const listButtonClass = activeTab === 'list' ? 'active' : ''

  return (
    <HoveredPinProvider>
      <>
        <div className="select-view-tabs">
          <button
            className={mapButtonClass}
            onClick={() => setActiveTab('map')}
          >
            <span
              className="material-icons nsw-material-icons"
              focusable="false"
              aria-hidden="true"
            >
              map
            </span>
            <span className="tab-name">Map View</span>
          </button>
          <button
            className={listButtonClass}
            onClick={() => setActiveTab('list')}
          >
            <span
              className="material-icons nsw-material-icons"
              focusable="false"
              aria-hidden="true"
            >
              list
            </span>
            <span className="tab-name">List View</span>
          </button>
        </div>
        <div className="map-list-container" role="application">
          <div className={listClass}>
            <ListView
              filterTerms={filterTerms}
              pins={pins}
              parentTermSvg={parentTermSvg}
              filterLabel={filterLabel}
            />
          </div>
          <div className={mapClass}>
            <MapComponent
              pins={pins}
              enableClustering={pinClustering}
              filterTerms={filterTerms}
              modalCtaLabel={modalCtaLabel}
              filterLabel={filterLabel}
            />
          </div>
        </div>
      </>
    </HoveredPinProvider>
  )
}

function App({
  pins,
  pinClustering,
  filterTerms,
  filterLabel,
  parentTermSvg,
  modalCtaLabel,
}) {
  return (
    <div className="app-container">
      <SearchAndFilter
        pins={pins}
        filterTerms={filterTerms}
        filterLabel={filterLabel}
      />
      <TabsComponent
        pins={pins}
        pinClustering={pinClustering}
        filterTerms={filterTerms}
        parentTermSvg={parentTermSvg}
        modalCtaLabel={modalCtaLabel}
        filterLabel={filterLabel}
      />
    </div>
  )
}

export default App
