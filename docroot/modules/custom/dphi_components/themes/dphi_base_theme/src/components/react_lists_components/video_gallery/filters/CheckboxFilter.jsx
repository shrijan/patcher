import { useEffect, useState } from 'react'
import { eventHandler } from '../utils/eventHandler'
import { getUrlSearchParam, setUrlSearchParams } from '../utils/searchParams'

export default function CheckboxFilter({
  filter,
  items,
  setCurrentFiltersResults,
}) {
  const [checkedIds, setCheckedIds] = useState([])

  const [showAllOptions, setShowAllOptions] = useState(false)

  const filterOptions = filter.children
  const displayLimit = 4

  // Function to load selected checkboxes from URL
  const loadSelectedCheckboxesFromURL = () => {
    const filterValues = getUrlSearchParam(filter.label)

    if (filterValues) {
      const valuesArray = filterValues.split('-')
      // Filter valuesArray to include only valid filter options
      const validValuesArray = valuesArray.filter(value =>
        filterOptions.some(option => option.id === value),
      )
      setCheckedIds(validValuesArray)
    }
  }

  useEffect(() => {
    // Load selected checkboxes from URL on initial load
    loadSelectedCheckboxesFromURL()

    // Only set the eventHandler once.
    eventHandler.on('clearFilters', () => {
      setCheckedIds([])
    })
  }, [])

  useEffect(() => {
    setUrlSearchParams(filter.label, checkedIds.join('-'))

    setCurrentFiltersResults(prevState => {
      if (checkedIds.length === 0) {
        return {
          ...prevState,
          [filter.id]: items.map(item => item.id),
        }
      }

      const filteredItems = items.filter(item => {
        return item.tags.some(itemFilterValue =>
          checkedIds.includes(itemFilterValue),
        )
      })

      return {
        ...prevState,
        [filter.id]: filteredItems.map(item => item.id),
      }
    })
  }, [checkedIds])

  const checkboxChangeHandler = id => () => {
    eventHandler.emit('changeFilters')
    setCheckedIds(prevState => {
      if (prevState.includes(id)) {
        return prevState.filter(x => x !== id)
      }
      return [...prevState, id]
    })
  }

  const toggleShowAllOptions = () => {
    setShowAllOptions(prevState => !prevState)
  }

  const displayedOptions = showAllOptions
    ? filterOptions
    : filterOptions.slice(0, displayLimit)
  const hiddenOptionsCount = filterOptions.length

  return (
    <fieldset className="nsw-form__fieldset">
      <legend className="nsw-form__legend">{filter.label}</legend>
      {displayedOptions.map((filterOption, index) => <>
        <div key={filterOption.id} className="checkbox-container">
          <input
            className="nsw-form__checkbox-input"
            type="checkbox"
            name={`filters-instant-${filter.label.toLowerCase().replace(' ', '-')}`}
            value={filterOption.label}
            id={`filters-instant-${filter.label.toLowerCase().replace(' ', '-')}-${index}-${filterOption.id}`}
            checked={checkedIds.includes(filterOption.id)}
            onChange={checkboxChangeHandler(filterOption.id)}
          />
          <label
            className="nsw-form__checkbox-label"
            htmlFor={`filters-instant-${filter.label.toLowerCase().replace(' ', '-')}-${index}-${filterOption.id}`}
          >
            {filterOption.label}
          </label>
        </div>
        {index == displayLimit - 1 && hiddenOptionsCount > displayLimit && !showAllOptions && <a href="javascript:void(0);" onClick={() => setShowAllOptions(true)} onKeyDown={e => {
          if (e.key == ' ') {
            setShowAllOptions(true)
            e.preventDefault()
          }
        }} className="show-all-options">
          Show more {filter.label} options ({hiddenOptionsCount})
        </a>}
      </>)}
      {hiddenOptionsCount > displayLimit && showAllOptions && (
        <a href="javascript:void(0);" onClick={() => setShowAllOptions(false)} onKeyDown={e => {
          if (e.key == ' ') {
            setShowAllOptions(false)
            e.preventDefault()
          }
        }} className="show-all-options">
          Show less
        </a>
      )}
    </fieldset>
  )
}
