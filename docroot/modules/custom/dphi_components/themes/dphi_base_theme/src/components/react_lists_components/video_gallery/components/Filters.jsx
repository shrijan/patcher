import { useEffect, useMemo, useRef, useState } from 'react'
import CheckboxFilter from '../filters/CheckboxFilter'
import { eventHandler } from '../utils/eventHandler'
import Search from './Search'

export default function Filters({ filters, items, setFilteredItemIds }) {
  const itemIds = useMemo(() => items.map(item => item.id), [])
  const filterArrays = useMemo(
    () => Object.fromEntries(filters.map(filter => [filter.id, []])),
    [],
  )

  const ref = useRef()
  const [expanded, setExpanded] = useState()
  const [animatingFilters, setAnimatingFilters] = useState(false)
  const [height, setHeight] = useState(0)
  const [isSmallScreen, setIsSmallScreen] = useState(false)

  useEffect(() => {
    const mediaQuery = window.matchMedia('(max-width: 767px)') // Define your screen width here
    // Function to update the state based on screen width
    const handleScreenResize = e => {
      setIsSmallScreen(e.matches)
    }
    // Set initial screen size check
    handleScreenResize(mediaQuery)
    // Listen for screen width changes
    mediaQuery.addEventListener('change', handleScreenResize)
    // Clean up event listener on unmount
    return () => mediaQuery.removeEventListener('change', handleScreenResize)
  }, [])

  useEffect(() => {
    if (expanded === undefined) {
      return
    }
    setAnimatingFilters(false)
    setHeight(undefined)

    setTimeout(() => {
      const fullHeight = ref.current.clientHeight
      if (expanded) {
        setHeight(0)
        setAnimatingFilters(true)
        setTimeout(() => {
          setHeight(fullHeight)

          setTimeout(() => {
            setHeight(undefined)
          }, 500)
        }, 1)
      } else {
        setHeight(fullHeight)
        setAnimatingFilters(true)

        setTimeout(() => {
          setHeight(0)
        }, 1)
      }
    }, 0)
  }, [expanded])

  const [currentFiltersResults, setCurrentFiltersResults] =
    useState(filterArrays)

  useEffect(() => {
    setFilteredItemIds(() => {
      const allIds = Object.values(currentFiltersResults).reduce((acc, ids) => {
        return acc.filter(id => ids.includes(id))
      }, itemIds)

      return allIds
    })
  }, [currentFiltersResults])

  // Only set the eventHandler once.
  useEffect(() => {
    eventHandler.on('clearFilters', () => {
      setCurrentFiltersResults(filterArrays)
    })
  }, [])

  return (
    <div className="nsw-row">
      <div className="nsw-col">
        <div className="nsw-filters nsw-filters--instant">
          <div className="nsw-filters__wrapper">
            <div className="nsw-filters__title">
              <h2>Filters</h2>
            </div>
            <button
              className="nsw-button nsw-button--grey-04 nsw-width-100 nsw-display-md-none nsw-display-flex nsw-justify-content-between"
              aria-expanded={expanded}
              onClick={() => {
                setExpanded(!expanded)
              }}
            >
              After something specific?
              <span class="material-icons" focusable="false" aria-hidden="true">
                expand_more
              </span>
            </button>
            <div
              className={
                'toggled-filters nsw-overflow-hidden nsw-p-top-lg ' +
                (animatingFilters ? 'animated' : '')
              }
              inert={!expanded && isSmallScreen ? '' : undefined}
              ref={ref}
              style={{
                height,
              }}
            >
              <div className="nsw-filters__list">
                <Search
                  items={items}
                  setCurrentFiltersResults={setCurrentFiltersResults}
                />
                {filters.map(filter => (
                  <div key={filter.id} className="nsw-filters__item">
                    <div className="nsw-filters__item-content">
                      <CheckboxFilter
                        filter={filter}
                        items={items}
                        setCurrentFiltersResults={setCurrentFiltersResults}
                      />
                    </div>
                  </div>
                ))}
              </div>
              <div className="nsw-filters__cancel">
                <button
                  type="reset"
                  onClick={() => eventHandler.emit('clearFilters')}
                >
                  Clear all filters
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
