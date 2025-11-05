import React, { useState, useEffect, useRef } from 'react'
import { useGoogleMapsAPI } from '../../contexts/GoogleMapsContext'
import SearchIcon from '../../assets/icons/search.svg?react'
import Select from 'nsw-design-system/src/components/select/select.js'
import '../../assets/scss/components/select.css'

const SearchAndFilter = ({ pins, filterTerms, filterLabel }) => {
  const [searchText, setSearchText] = useState('')
  const [selectedFilters, setSelectedFilters] = useState([])
  const [noResults, setNoResults] = useState(false)
  const [isDrawerVisible, setIsDrawerVisible] = useState(false)
  const [autocompleteSelection, setAutocompleteSelection] = useState(false)
  const {
    updateVisiblePins,
    areFiltersApplied,
    mapInstance,
    isLoaded,
    setSearchAction,
    setNoResultsAction,
    setSearchValue,
    setSearchFilterPins,
    setInitialBoundsSet,
  } = useGoogleMapsAPI()
  const autocompleteRef = useRef(null) // Ref for the autocomplete input
  const selectRef = useRef(null) // Ref for the multi-select filter element from DDS package
  const customSelectRef = useRef(null)

  const defaultMapView = { lat: -33.8688, lng: 151.2093 }

  useEffect(() => {
    if (isLoaded && autocompleteRef.current) {
      const autocomplete = new window.google.maps.places.Autocomplete(
        autocompleteRef.current,
        {
          types: ['(regions)'],
          componentRestrictions: { country: 'AU' },
        },
      )

      autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace()
        if (!place.geometry) {
          console.log('Returned place contains no geometry')
          return
        }
        setSearchText(place.name)
        setAutocompleteSelection(true)
      })
    }

    // Initialize the custom Select component
    // Only run if the map is loaded and the select element has not been processed
    if (isLoaded && selectRef.current && !selectRef.current.dataset.processed) {
      const customSelect = new Select(selectRef.current)
      customSelect.init()
      selectRef.current.dataset.processed = 'true' // Mark as processed
      customSelectRef.current = customSelect // Store the instance for later use

      // After initializing the custom select, append the new span to the dropdown
      const dropdown = document.getElementById(
        'filter-by-property-type-dropdown',
      )
      if (dropdown) {
        const newButton = document.createElement('button')
        newButton.textContent = 'Clear all selections'
        newButton.className = 'clear-all-selections'
        newButton.addEventListener('click', clearAllFilters)
        dropdown.appendChild(newButton)
      }
    }
  }, [isLoaded, filterTerms])

  const handleSearch = () => {
    const searchLower = searchText.toLowerCase()
    // console.log('Selected Filters:', selectedFilters) // Debugging: Log selected filters

    const filteredPins = pins.filter(pin => {
      const suburbLower = pin.suburb.toLowerCase()
      const postcode = pin.postcode.toString()
      const pinTypeString = pin.pin_type.toString() // Ensure pin_type is treated as a string

      // Debugging: Log pin details
      // console.log(`Checking pin: ${pin.name}, Type: ${pinTypeString}`)

      const matchesSearch = searchText
        ? suburbLower.includes(searchLower) || postcode.includes(searchLower)
        : true

      // Ensure selectedFilters are compared correctly with pin_type
      const matchesFilter =
        selectedFilters.length === 0 || selectedFilters.includes(pinTypeString)

      return matchesSearch && matchesFilter
    })

    setNoResults(filteredPins.length === 0)
    updateVisiblePins(filteredPins)
    setSearchFilterPins(filteredPins)
    areFiltersApplied(true)
    // console.log('Filtered pins:', filteredPins)

    // Determine the type of action
    const actionType = searchText
      ? selectedFilters.length
        ? 'both'
        : 'search'
      : 'filter'
    setSearchAction(actionType)

    // Update no results state based on filteredPins
    setNoResultsAction(filteredPins.length === 0)

    if (mapInstance && filteredPins.length > 0) {
      // console.log('Setting bounds in Search and Filter')
      const bounds = new window.google.maps.LatLngBounds()
      filteredPins.forEach(pin => {
        bounds.extend(
          new window.google.maps.LatLng(pin.lat_long.lat, pin.lat_long.long),
        )
      })
      mapInstance.fitBounds(bounds)
      // console.log(`Bounds:`, bounds.toString())
      // mapInstance.getCenter() &&
      //   console.log(`Map Center:`, mapInstance.getCenter().toString())
    } else {
      // console.log("Didn't set new bounds")
      mapInstance.setCenter(defaultMapView)
      mapInstance.setZoom(10)
    }

    setSearchValue(searchText)
    setIsDrawerVisible(false)
  }

  // Update searchText state as user types for controlled input
  const handleInputChange = e => {
    setSearchText(e.target.value)
    setAutocompleteSelection(false)
  }

  const handleFilterChange = e => {
    const options = e.target.options
    const value = []
    for (let i = 0, l = options.length; i < l; i++) {
      if (options[i].selected) {
        value.push(options[i].value)
      }
    }
    setSelectedFilters(value)
  }

  const clearAllFilters = () => {
    const nativeSelect = selectRef.current?.querySelector('select')
    if (nativeSelect) {
      Array.from(nativeSelect.options).forEach(
        option => (option.selected = false),
      )
      const event = new Event('change', { bubbles: true })
      nativeSelect.dispatchEvent(event)
    }

    // Uncheck all checkboxes
    const checkboxes = document.querySelectorAll('.js-multi-select__checkbox')
    checkboxes.forEach(checkbox => {
      checkbox.checked = false
    })

    // Update aria-selected for all options
    const options = document.querySelectorAll('.js-multi-select__option')
    options.forEach(option => {
      option.setAttribute('aria-selected', 'false')
    })

    // Reset the button label to its default state
    const buttonLabel = document.querySelector('.js-multi-select__label')
    if (buttonLabel) {
      buttonLabel.innerHTML =
        '<span class="nsw-multi-select__term">Select</span>'
    }

    // Remove the 'nsw-multi-select__button--active' class from the button
    const selectButton = document.querySelector('.js-multi-select__button')
    if (selectButton) {
      selectButton.classList.remove('nsw-multi-select__button--active')
    }
  }

  const handleClearAll = () => {
    setSearchText('')
    setSelectedFilters([])
    setNoResults(false)
    setSearchAction(null)
    setNoResultsAction(false)
    clearAllFilters()
    setSearchValue(searchText)
    setSearchFilterPins([])

    // Check if pins data is available
    if (pins && pins.length > 0) {
      // const bounds = new window.google.maps.LatLngBounds()
      // Extend the bounds to include each pin's position
      // pins.forEach(pin => {
      //   const latLng = new window.google.maps.LatLng(
      //     parseFloat(pin.lat_long.lat),
      //     parseFloat(pin.lat_long.long),
      //   )
      //   bounds.extend(latLng)
      // })
      // If the mapInstance is available, fit the map to these bounds
      // if (mapInstance) {
      //   mapInstance.fitBounds(bounds)
      // }

      setInitialBoundsSet(false) //Pass bounds setting back to the MapComponent
      updateVisiblePins(pins) // Reset visible pins to include all pins
      areFiltersApplied(false)
    } else {
      updateVisiblePins([]) // If no pins data, reset to an empty array
    }
  }

  const toggleDrawer = () => {
    setIsDrawerVisible(prevState => !prevState)
  }

  const handleKeyDown = event => {
    const checkboxes = document.querySelectorAll('.js-multi-select__checkbox')
    const clearAllLink = document.querySelector('.clear-all-selections')

    if (
      event.key === 'Tab' &&
      !event.shiftKey &&
      document.activeElement === checkboxes[checkboxes.length - 1]
    ) {
      event.preventDefault()
      clearAllLink.focus()
    } else if (
      event.key === 'ArrowUp' &&
      document.activeElement === clearAllLink
    ) {
      event.preventDefault()
      checkboxes[checkboxes.length - 1].focus()
    }
  }

  return (
    <div className="search-filter-container">
      <div className="nsw-row">
        <div className="nsw-col nsw-col-md-12">
          <div className="nsw-filters nsw-filters--instant nsw-filters--down js-filters">
            <div
              className={`nsw-filters__controls ${isDrawerVisible ? 'has-border-bottom' : ''}`}
              onClick={toggleDrawer}
            >
              <button>
                <span
                  className="material-icons nsw-material-icons"
                  focusable="false"
                  aria-hidden="true"
                >
                  tune
                </span>
                <span>
                  {isDrawerVisible ? 'Close Filters' : 'View Filters'}
                </span>
                <span
                  className="material-icons nsw-material-icons filter-drawer-arrow"
                  focusable="false"
                  aria-hidden="true"
                >
                  {isDrawerVisible
                    ? 'keyboard_arrow_up'
                    : 'keyboard_arrow_down'}
                </span>
              </button>
            </div>
            <div
              className={`nsw-filters__wrapper ${isDrawerVisible ? 'is-visible' : ''}`}
            >
              <div className="nsw-filters__list">
                <div className="nsw-filters__item">
                  <div className="nsw-filters__item-content">
                    <label
                      className="nsw-form__label"
                      htmlFor="filters-instant-regions-3"
                    >
                      Search by suburb or postcode
                    </label>
                    <div className="search-container">
                      <input
                        className="nsw-form__input"
                        ref={autocompleteRef}
                        type="text"
                        id="search-input"
                        name="search-input"
                        value={searchText}
                        onChange={handleInputChange}
                        aria-describedby="search-input-helper"
                        aria-label="Search by suburb or postcode"
                      />
                      <SearchIcon
                        className="search-icon"
                        role="img"
                        aria-hidden="true"
                      />
                    </div>
                  </div>
                </div>
                <div className="nsw-filters__item">
                  <div className="nsw-filters__item-content">
                    <fieldset className="nsw-form__fieldset">
                      <legend className="nsw-form__legend">
                        Filter by {filterLabel}
                      </legend>
                      <div
                        className="nsw-multi-select js-multi-select"
                        data-select-text="Select"
                        data-multi-select-text="{n} property types selected"
                        data-n-multi-select="2"
                        ref={selectRef}
                        tabIndex="-1"
                        onKeyDown={handleKeyDown}
                      >
                        <select
                          name="filter-by-property-type"
                          id="filter-by-property-type"
                          multiple
                          onChange={handleFilterChange}
                        >
                          <option value="" disabled>
                            Please select
                          </option>
                          {filterTerms?.map(term => (
                            <option key={term.id} value={term.id}>
                              {term.name}
                            </option>
                          ))}
                        </select>
                      </div>
                    </fieldset>
                  </div>
                </div>
                <div className="nsw-form__group nsw-filters__item">
                  <div className="action-buttons nsw-filters__item-content">
                    <button
                      type="button"
                      className="nsw-button nsw-button--dark"
                      onClick={handleSearch}
                    >
                      Apply
                    </button>
                    <button
                      type="reset"
                      className="nsw-button button-as-link"
                      onClick={handleClearAll}
                    >
                      Clear all filters
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default SearchAndFilter
