// Search.jsx
import { useEffect, useState } from 'react'
import { eventHandler } from '../utils/eventHandler'

function Search({ items, setCurrentFiltersResults }) {
  const [searchText, setSearchText] = useState('')

  const searchHandler = event => {
    event.preventDefault()
    const searchString = event.target.value
    const searchLower = searchString.toLowerCase()
    setSearchText(searchLower)
  }

  useEffect(() => {
    eventHandler.on('clearFilters', () => {
      setSearchText('')
    })
  }, [])

  useEffect(() => {
    setCurrentFiltersResults(prevState => {
      if (searchText.length === 0) {
        return {
          ...prevState,
          search: items.map(item => item.id),
        }
      }

      const filteredItems = items.filter(item => {
        const title =
          item.title && item.title.toLowerCase().includes(searchText)
        const caption =
          item.caption && item.caption.toLowerCase().includes(searchText)
        return title || caption
      })

      return {
        ...prevState,
        search: filteredItems.map(item => item.id),
      }
    })
  }, [searchText])

  return (
    <div className="nsw-form__group">
      <label className="nsw-form__label" htmlFor="edit-combine">
        Search by keyword
      </label>
      <input
        className="nsw-form__input"
        type="text"
        id="edit-combine"
        name="combine"
        placeholder="Search by keyword"
        value={searchText}
        onChange={searchHandler}
      />
    </div>
  )
}

export default Search
