// Clustering works, non clustreing doesn't
import React, { useEffect, useState, useRef, useCallback } from 'react'
import { GoogleMap } from '@react-google-maps/api'
import { MarkerClusterer } from '@googlemaps/markerclusterer'
import { useGoogleMapsAPI } from '../../contexts/GoogleMapsContext'
import { useHoveredPin } from '../../contexts/useHoveredPinState'
import addCurrentLocation from 'google-maps-current-location'
import PropertyModal from '../modal/PropertyModal'
import SearchErrors from '../searchfilter/SearchErrors'
import MapLegend from './MapLegend'

const defaultCenter = { lat: -33.8688, lng: 151.2093 }

const MapComponent = ({
  pins,
  enableClustering = true,
  filterTerms,
  filterLabel,
  modalCtaLabel,
}) => {
  const {
    isLoaded,
    loadError,
    mapId,
    visiblePins,
    updateVisiblePins,
    updateMapInstance,
    filtersApplied,
    selectedPin,
    setSelectedPin,
    isModalOpen,
    setIsModalOpen,
    setListDataReady,
    activeTab,
    searchFilterPins,
    searchValue,
    initialBoundsSet,
    setInitialBoundsSet,
    setUserDraggedMapContext,
  } = useGoogleMapsAPI()
  const [mapLoaded, setMapLoaded] = useState(false)
  const [clustererInitialised, setclustererInitialised] = useState(false)
  const { setHoveredPin, hoveredPin } = useHoveredPin()
  const mapRef = useRef(null)
  const clustererRef = useRef(null)
  const markerRefs = useRef(new Map())
  const [userDraggedMap, setUserDraggedMap] = useState(false)

  const handlePinClick = pin => {
    // console.log('PIN CLICKED', pin)
    setSelectedPin(pin)
    setIsModalOpen(true)
  }

  const closeModal = () => {
    setIsModalOpen(false)
    setSelectedPin(null)
  }

  // Utility function to debounce another function
  const debounce = (func, wait) => {
    let timeout
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout)
        func(...args)
      }
      clearTimeout(timeout)
      timeout = setTimeout(later, wait)
    }
  }

  const mapClusterRenderer = {
    render: cluster => {
      return new google.maps.Marker({
        icon: {
          url:
            'data:image/svg+xml;charset=utf-8,' +
            encodeURIComponent(
              `<svg width="116" height="116" viewBox="0 -2 116 114" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="58" cy="54" r="28" fill="#D1B99C"/>
              <circle cx="58" cy="54" r="20" fill="#EEF3F8" stroke="#B68D5E" stroke-width="4"/></svg>`,
            ),
          scaledSize: new google.maps.Size(80, 80),
        },
        label: {
          text: cluster.markers.length.toString(),
          color: '#000',
          fontSize: '14px',
        },
        position: cluster.position,
        // adjust zIndex to be above other markers
        // zIndex: Number(google.maps.Marker.MAX_ZINDEX) + cluster.markers.length,
      })
    },
  }

  const updatePinsInView = useCallback(() => {
    if (!mapRef.current || filtersApplied) return

    const bounds = mapRef.current.getBounds()
    if (!bounds) return // Ensure bounds are defined

    const viewportPins = pins.filter(pin => {
      const position = new window.google.maps.LatLng(
        pin.lat_long.lat,
        pin.lat_long.long,
      )
      return bounds.contains(position)
    })

    updateVisiblePins(viewportPins)
    // Check if there are any visible pins and update userDraggedMap state
    setUserDraggedMap(viewportPins.length === 0)
    setUserDraggedMapContext(viewportPins.length === 0)
  }, [pins, updateVisiblePins])

  const debouncedUpdatePinsInView = useCallback(
    debounce(updatePinsInView, 200),
    [updatePinsInView],
  )

  // Initialise a MarkerClusterer
  useEffect(() => {
    if (isLoaded && enableClustering && mapLoaded && !clustererInitialised) {
      // Initialize clusterer
      clustererRef.current = new MarkerClusterer({
        map: mapRef.current,
        markers: [],
        renderer: mapClusterRenderer,
      })
      setclustererInitialised(true) // Indicate that the clusterer is initialized
    } else {
      setclustererInitialised(false) // Reset if conditions are not met
    }
  }, [isLoaded, enableClustering, mapLoaded])

  // Set the bounds based on available pins
  useEffect(() => {
    if (!mapRef.current || !isLoaded || pins.length === 0 || initialBoundsSet)
      return // Check if initialBoundsSet is true to skip

    const bounds = new window.google.maps.LatLngBounds()
    pins.forEach(pin => {
      const latLng = new window.google.maps.LatLng(
        parseFloat(pin.lat_long.lat),
        parseFloat(pin.lat_long.long),
      )
      bounds.extend(latLng)
    })

    // Delay fitting bounds to ensure all asynchronous operations have completed
    const timeoutId = setTimeout(() => {
      if (mapRef.current && pins.length > 0) {
        mapRef.current.fitBounds(bounds, {
          top: 10,
          right: 10,
          bottom: 10,
          left: 10,
        }) // Added padding for visual clarity
        setInitialBoundsSet(true) // Indicate that the initial fitting has been done
        setListDataReady(true) // Set data for the list as ready to avoid showing 'No results found' unintentionally
      } else {
        console.warn('No valid pins provided to set bounds')
      }
    }, 500)

    return () => clearTimeout(timeoutId) // Cleanup timeout on component unmount or props change
  }, [isLoaded, pins, mapLoaded, updateMapInstance])

  // Add the location button once map loaded and ready
  useEffect(() => {
    if (mapLoaded && mapRef.current) {
      addCurrentLocation(mapRef.current)

      setTimeout(() => {
        // Find the <img> element inside the generated button
        const locationButton = document.querySelector(
          'button[title="Your Location"]',
        )
        if (locationButton) {
          const img = locationButton.querySelector('img')
          if (img) {
            img.alt = 'Your Location'
          }
        }
      }, 3000)
    }
  }, [mapLoaded])

  // Listen for tab change in mobile and update the bounds correctly using the filteredPins
  useEffect(() => {
    if (activeTab === 'map' && mapRef.current && searchFilterPins.length > 0) {
      const bounds = new window.google.maps.LatLngBounds()
      searchFilterPins.forEach(pin => {
        const latLng = new window.google.maps.LatLng(
          parseFloat(pin.lat_long.lat),
          parseFloat(pin.lat_long.long),
        )
        bounds.extend(latLng)
      })

      // Adjust the map's bounds based on the filtered pins
      if (mapRef.current) {
        mapRef.current.fitBounds(bounds)
      }
    }
  }, [activeTab, searchFilterPins])

  // Create markers and set as clustered or un-clustered
  useEffect(() => {
    if (!isLoaded || !mapRef.current) return

    // Function to create a marker for a given pin
    const createMarker = pin => {
      const matchingTerm = filterTerms.find(term => term.id === pin.pin_type)
      if (!matchingTerm) return null

      let glyphImg
      if (matchingTerm.iconUrl) {
        glyphImg = document.createElement('img')
        glyphImg.src = matchingTerm.iconUrl
        glyphImg.className = 'map-pin-icon'
        glyphImg.alt = 'Marker icon'
      }

      const pinBackground = new window.google.maps.marker.PinElement({
        background: matchingTerm.colour,
        borderColor: matchingTerm.colour,
        glyph: glyphImg,
      })

      const marker = new window.google.maps.marker.AdvancedMarkerElement({
        title: pin.name,
        position: new window.google.maps.LatLng(
          pin.lat_long.lat,
          pin.lat_long.long,
        ),
        content: pinBackground.element,
        map: enableClustering ? null : mapRef.current, // Conditionally add to map or not based on clustering
        zIndex: 99999, //Set high z-index on pins so cluster markers do not conflict with hover interactions
      })

      // Add click listener
      marker.addListener('click', () => handlePinClick(pin))

      // Hover listeners
      marker.content.addEventListener('mouseover', () => {
        setHoveredPin(pin.id)
        marker.content.classList.add('is-hover')
      })

      marker.content.addEventListener('mouseout', () => {
        setHoveredPin(null)
        marker.content.classList.remove('is-hover')
      })

      markerRefs.current.set(pin.id, {
        marker,
        domElement: pinBackground.element,
      })

      return marker
    }

    // Clear existing markers before adding new ones
    markerRefs.current.forEach(value => {
      if (enableClustering && clustererRef.current) {
        clustererRef.current.removeMarker(value.marker)
      } else {
        value.marker.setMap(null)
      }
    })
    markerRefs.current.clear()

    // Create markers for visiblePins
    const markers = visiblePins
      .map(createMarker)
      .filter(marker => marker !== null)

    if (enableClustering && clustererRef.current) {
      clustererRef.current.clearMarkers()
      clustererRef.current.addMarkers(markers)
    }
  }, [visiblePins, isLoaded, enableClustering, filterTerms])

  // React to hoveredPin changes
  useEffect(() => {
    markerRefs.current.forEach((value, id) => {
      if (hoveredPin === id) {
        value.domElement.classList.add('is-hover')
      } else {
        value.domElement.classList.remove('is-hover')
      }
    })
  }, [hoveredPin])

  if (loadError) return <div>Error loading map</div>
  if (!isLoaded) return <div>Loading Map...</div>

  return (
    <>
      <MapLegend filterTerms={filterTerms} />
      <GoogleMap
        mapContainerClassName="map"
        center={defaultCenter}
        zoom={10}
        onLoad={map => {
          mapRef.current = map
          setMapLoaded(true) // Indicate that the map is loaded and ref is set
          updateMapInstance(map) // Update the context with the map instance
          updatePinsInView() // Initial update for visible pins
        }}
        onBoundsChanged={debouncedUpdatePinsInView} // Update visible pins on map movement (debounced)
        options={{
          mapId: mapId,
          zoomControl: true,
          streetViewControl: false,
          fullscreenControl: false,
          mapTypeControl: false,
          gestureHandling: 'cooperative',
          minZoom: 6,
          maxZoom: 20,
        }}
      >
        {selectedPin && (
          <PropertyModal
            isOpen={isModalOpen}
            closeModal={closeModal}
            content={selectedPin}
            filterTerms={filterTerms}
            modalCtaLabel={modalCtaLabel}
          />
        )}
      </GoogleMap>
      <SearchErrors filterLabel={filterLabel} />
    </>
  )
}

export default MapComponent
