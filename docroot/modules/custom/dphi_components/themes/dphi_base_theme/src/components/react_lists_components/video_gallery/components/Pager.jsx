import ReactPaginate from 'react-paginate'
import { useEffect, useState } from 'react'
import { setUrlSearchParams } from '../utils/searchParams'
import { eventHandler } from '../utils/eventHandler'

const urlParams = new URLSearchParams(window.location.search)
const initialPage = urlParams.get('page') ?? 1

export default function Pager({
  filteredItems,
  itemsPerPage,
  setCurrentPage
}) {
  const [forcePage, setForcePage] = useState(initialPage - 1)

  const setPage = pageNumber => {
    setForcePage(pageNumber)
    setCurrentPage(pageNumber)
  }

  // Only set the eventHandler once.
  useEffect(() => {
    eventHandler.on('changeFilters', () => {
      setUrlSearchParams('page', 1)
      setPage(0)
    })
  }, [])

  useEffect(() => {
    setPage(forcePage)
  }, [filteredItems])

  const pageCount = Math.ceil(filteredItems.length / itemsPerPage)
  if (pageCount == 1) {
    return
  }

  const handlePageChange = event => {
    setUrlSearchParams('page', event.selected + 1)
    setPage(event.selected)
  }

  // Scroll up when the user interacts with the pager.
  const handlePagerClick = e => {
    if (e.nextSelectedPage !== undefined) {
      const rootElement = document.getElementById('react-list')
      if (rootElement) {
        rootElement.scrollIntoView({ behavior: 'smooth' })
      }
    }
  }

  return (
    <div className="nsw-pagination">
      <ReactPaginate
        activeLinkClassName="active"
        forcePage={forcePage}
        marginPagesDisplayed={1}
        nextLabel={
          <span
            className="material-icons nsw-material-icons"
            focusable="false"
            aria-hidden="true"
          >
            keyboard_arrow_right
          </span>
        }
        onClick={handlePagerClick}
        onPageChange={handlePageChange}
        pageCount={pageCount}
        pageRangeDisplayed={forcePage < 2 ? 4 - forcePage : (forcePage > pageCount - 2 ? 4 - (pageCount - forcePage) : 2)}
        previousLabel={
          <span
            className="material-icons nsw-material-icons"
            focusable="false"
            aria-hidden="true"
          >
            keyboard_arrow_left
          </span>
        }
        renderOnZeroPageCount={null}
      />
    </div>
  )
}
