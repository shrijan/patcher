import React, { useEffect, useState } from 'react'
import { useGoogleMapsAPI } from '../../contexts/GoogleMapsContext'
import LocationIcon from '../../assets/icons/location_icon.svg?react'
import { useHoveredPin } from '../../contexts/useHoveredPinState'
import SearchErrors from '../searchfilter/SearchErrors'

const ListView = ({ filterTerms, parentTermSvg, filterLabel }) => {
  const {
    visiblePins,
    selectedPin,
    setSelectedPin,
    setIsModalOpen,
    listDataReady,
    searchValue,
  } = useGoogleMapsAPI()
  const [sortedPins, setSortedPins] = useState([])
  const { hoveredPin, setHoveredPin } = useHoveredPin()
  const [focusedPin, setFocusedPin] = useState(null)

  useEffect(() => {
    const sorted = [...visiblePins].sort((a, b) => a.name.localeCompare(b.name))
    setSortedPins(sorted)
  }, [visiblePins])

  const handleListItemClick = pin => {
    setSelectedPin(pin)
    setIsModalOpen(true)
  }

  const handleMouseEnter = pinId => {
    setHoveredPin(pinId)
  }

  const handleMouseLeave = () => {
    setHoveredPin(null)
  }

  const handleListItemFocus = pin => {
    setHoveredPin(pin.id)
    setFocusedPin(pin.id)
  }

  const handleListItemBlur = () => {
    setHoveredPin(null)
    setFocusedPin(null)
  }

  const getObjectPosition = alignment => {
    switch (alignment) {
      case 'left':
        return 'left'
      case 'centre':
        return 'center'
      case 'right':
        return 'right'
      default:
        return 'center'
    }
  }

  const noResultsMessage = `No results found in ${searchValue}.`

  return (
    <div className="list-view">
      {!listDataReady ? (
        <div>Loading List Data...</div>
      ) : sortedPins.length > 0 ? (
        sortedPins.map((pin, index) => {
          // Find the matching filter term based on the pin's pin_type
          const matchingTerm = filterTerms.find(
            term => term.id === pin.pin_type,
          )
          const typeIcon = matchingTerm ? matchingTerm.iconUrl : null
          const typeName = matchingTerm ? matchingTerm.name : 'Unknown Type'
          const typeColour = matchingTerm ? matchingTerm.colour : '#495054'
          const isActive = selectedPin && selectedPin.id === pin.id
          const isHovered = hoveredPin === pin.id
          const isFocused = focusedPin === pin.id

          return (
            <article
              key={index}
              className={`nsw-list-item nsw-list-item--reversed nsw-list-item--block ${
                isHovered || isFocused ? 'is-hover' : ''
              } ${isActive ? 'is-active' : ''}`}
              style={{
                borderRight: `5px solid ${typeColour}`,
                backgroundColor:
                  isHovered || isFocused || isActive
                    ? typeColour
                    : 'transparent',
                color: isHovered || isFocused || isActive ? 'white' : 'inherit',
              }}
              tabIndex="0"
              aria-label={`View details for ${pin.name}`}
              onClick={() => handleListItemClick(pin)}
              onKeyDown={event => {
                if (event.key === 'Enter' || event.key === ' ') {
                  handleListItemClick(pin)
                  event.preventDefault() // Prevent the page from scrolling down on space key press
                }
              }}
              onMouseEnter={() => handleMouseEnter(pin.id)}
              onMouseLeave={handleMouseLeave}
              onFocus={() => handleListItemFocus(pin)}
              onBlur={handleListItemBlur}
            >
              <div className="nsw-list-item__content">
                <div className="nsw-list-item__title">
                  <h1>{pin.name}</h1>
                </div>
                <div className="nsw-list-item__copy">
                  <p className="nsw-list-item__copy-item location">
                    <LocationIcon />
                    {pin.suburb}
                  </p>
                  <p className="nsw-list-item__copy-item architect">
                    {parentTermSvg && (
                      <img src={parentTermSvg} alt="Parent Term Icon" />
                    )}
                    {pin.field_1_value}
                  </p>
                  <p className="nsw-list-item__copy-item type">
                    {typeIcon && <img src={typeIcon} alt="Type Icon" />}
                    {typeName}
                  </p>
                </div>
              </div>
              <div className="nsw-list-item__image">
                <img
                  src={
                    pin.images && pin.images.length > 0
                      ? pin.images[0].thumbnail
                      : 'default-image-url'
                  }
                  alt={
                    pin.images && pin.images.length > 0
                      ? pin.images[0].alt
                      : 'Image of the dwelling'
                  }
                  className="list-thumbnail"
                  style={{
                    objectPosition: getObjectPosition(pin.thumbnail_alignment),
                  }}
                />
              </div>
            </article>
          )
        })
      ) : (
        <SearchErrors filterLabel={filterLabel} />
      )}
    </div>
  )
}

export default ListView
