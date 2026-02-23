// SortBy.jsx
import { useEffect, useState } from 'react'
import { eventHandler } from '../utils/eventHandler'

function SortBy({ setSortKey }) {
  const [selectedValue, setSelectedValue] = useState('date_newest')

  const sortChangeHandler = event => {
    const sortOption = event.target.value
    setSortKey(sortOption)
    setSelectedValue(sortOption)
  }

  useEffect(() => {
    eventHandler.on('clearFilters', () => {
      setSortKey('date_newest')
      setSelectedValue('date_newest')
    })
  }, [])

  return (
    <div className="nsw-form sort-by-container">
      <div className="nsw-form__group">
        <label className="nsw-form__label" htmlFor="edit-sort-combine">
          Sort by
        </label>
        <select
          className="nsw-form__select"
          value={selectedValue}
          data-drupal-selector="edit-sort-combine"
          id="edit-sort-combine"
          name="sort_combine"
          onChange={sortChangeHandler}
        >
          <option value="">Please select</option>
          <option value="asc_title">Title (A-Z)</option>
          <option value="desc_title">Title (Z-A)</option>
          <option value="date_newest">Date (newest)</option>
          <option value="date_oldest">Date (oldest)</option>
        </select>
      </div>
    </div>
  )
}

export default SortBy
