import React, { createContext, useContext, useState } from 'react'
import { useJsApiLoader } from '@react-google-maps/api'
import { googleMapsLibraries } from '../config/googleMapsConfig'

const GoogleMapsAPIContext = createContext()

export const GoogleMapsAPIProvider = ({ children, apiKey, mapID }) => {
  const { isLoaded, loadError } = useJsApiLoader({
    googleMapsApiKey: apiKey,
    libraries: googleMapsLibraries,
    mapIds: [mapID],
  })

  // State to hold the map instance
  const [mapInstance, setMapInstance] = useState(null)
  // State to track if initial bounds are set or not
  const [initialBoundsSet, setInitialBoundsSet] = useState(false)
  // State to track visible pins
  const [visiblePins, setVisiblePins] = useState([])
  // State to track the results of a search and filter
  const [searchFilterPins, setSearchFilterPins] = useState([])
  //State to track if filters are applied
  const [filtersApplied, areFiltersApplied] = useState(false)
  //State to track active tab in mobile view
  const [activeTab, setActiveTab] = useState('map')
  //State for selected pin
  const [selectedPin, setSelectedPin] = useState(null)
  //State for modal open status
  const [isModalOpen, setIsModalOpen] = useState(false)
  //State to ensure the Map is fully loaded, pins set and bounds calculated
  const [listDataReady, setListDataReady] = useState(false)
  //State for more concise no results found messaging
  const [searchAction, setSearchAction] = useState(null) // 'search', 'filter', 'both'
  const [noResultsAction, setNoResultsAction] = useState(false)
  const [searchValue, setSearchValue] = useState('')
  const [userDraggedMapContext, setUserDraggedMapContext] = useState(false)

  // Method to update the map instance
  const updateMapInstance = map => {
    setMapInstance(map)
  }

  // Method to update visible pins
  const updateVisiblePins = pins => {
    setVisiblePins(pins)
  }

  const value = {
    mapInstance,
    updateMapInstance,
    isLoaded,
    loadError,
    mapId: mapID,
    visiblePins,
    updateVisiblePins,
    filtersApplied,
    areFiltersApplied,
    activeTab,
    setActiveTab,
    selectedPin,
    setSelectedPin,
    isModalOpen,
    setIsModalOpen,
    listDataReady,
    setListDataReady,
    searchAction,
    setSearchAction,
    noResultsAction,
    setNoResultsAction,
    searchValue,
    setSearchValue,
    searchFilterPins,
    setSearchFilterPins,
    initialBoundsSet,
    setInitialBoundsSet,
    userDraggedMapContext,
    setUserDraggedMapContext,
  }

  return (
    <GoogleMapsAPIContext.Provider value={value}>
      {children}
    </GoogleMapsAPIContext.Provider>
  )
}

export const useGoogleMapsAPI = () => useContext(GoogleMapsAPIContext)
