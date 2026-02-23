import { useEffect, useState, useMemo } from 'react'
import Items from './components/Items.jsx'
import Pager from './components/Pager.jsx'
import Filters from './components/Filters.jsx'
import SortBy from './components/SortBy.jsx'

export default function App({ items, filters, displayLayout, showFilters, theme }) {
  const itemsPerPage = displayLayout === 'grid' ? 9 : displayLayout
  const [sortKey, setSortKey] = useState('date_newest')

  const itemIds = useMemo(() => items.map(item => item.id), [])
  const [filteredItemIds, setFilteredItemIds] = useState(itemIds)
  const [filteredItems, setFilteredItems] = useState(items)
  const [currentPage, setCurrentPage] = useState(0)

  useEffect(() => {
    setFilteredItems(() => {
      const filtered = items.filter(item => filteredItemIds.includes(item.id))

      // Sort the filtered items based on the sortKey
      const sortedItems = [...filtered].sort((a, b) => {
        if (sortKey === 'asc_title') {
          return a.title.localeCompare(b.title)
        } else if (sortKey === 'desc_title') {
          return b.title.localeCompare(a.title)
        } else if (sortKey === 'date_newest') {
          return b.created - a.created
        } else if (sortKey === 'date_oldest') {
          return a.created - b.created
        }
        return 0
      })

      return sortedItems
    })
  }, [filteredItemIds, sortKey])

  const currentItems = filteredItems.slice(currentPage * itemsPerPage, currentPage * itemsPerPage + itemsPerPage)
  return (
    <div className="nsw-row">
      {showFilters && (
        <div className="nsw-col nsw-col-md-4">
          <Filters
            filters={filters}
            items={items}
            setFilteredItemIds={setFilteredItemIds}
          />
        </div>
      )}
      <div className={`gallery-main nsw-col nsw-col-md-${showFilters ? '8' : '12'}`}>
        <div className="results-and-sort">
          <div>
            {currentItems.length != 0 && <p>
              Showing results {currentPage * itemsPerPage + 1}-{currentPage * itemsPerPage + currentItems.length} of{' '}
              {filteredItems.length}
            </p>}
          </div>
          <SortBy setSortKey={setSortKey} />
        </div>
        <Items {...{currentItems, displayLayout, theme}} />
        {filteredItems.length != 0 && <div className="nsw-pagination-container">
          <Pager {...{filteredItems, itemsPerPage, setCurrentPage}} />
        </div>}
      </div>
    </div>
  )
}
