import React from 'react'
import { useGoogleMapsAPI } from '../../contexts/GoogleMapsContext'

const SearchErrors = ({ filterLabel }) => {
  const { searchAction, noResultsAction, searchValue, userDraggedMapContext } =
    useGoogleMapsAPI()

  const noResultsMessage = `No results found in ${searchValue}.`

  const renderSearchFilterErrorMessage = () => {
    return (
      <div>
        {searchAction === 'search' && (
          <>
            <p className="no-results">{noResultsMessage}</p>
            <p>Try adjusting your suburb/postcode search criteria.</p>
            <p>
              Alternatively, clear your search results and use the {filterLabel}{' '}
              dropdown list or browse the map and list view.
            </p>
          </>
        )}
        {searchAction === 'filter' && (
          <>
            <p className="no-results">
              No results found with the selected filters.
            </p>
            <p>Try selecting different filters to find pins.</p>
          </>
        )}
        {searchAction === 'both' && (
          <>
            <p className="no-results">
              No results found for your search and filter combination.
            </p>
            <p>Try adjusting your search location and/or filters.</p>
          </>
        )}
      </div>
    )
  }

  const renderMapDraggedErrorMessage = () => {
    return (
      <div>
        <p className="no-results">There are no results in this area.</p>
        <p>Try adjusting your search location and/or filters.</p>
      </div>
    )
  }

  return (
    <>
      {noResultsAction && (
        <div
          className="list-view-alert"
          style={{ borderLeft: `5px solid #C95000` }}
        >
          <span
            className="material-icons nsw-material-icons"
            focusable="false"
            aria-hidden="true"
          >
            error
          </span>
          {renderSearchFilterErrorMessage()}
        </div>
      )}
      {userDraggedMapContext && (
        <div
          className="list-view-alert"
          style={{ borderLeft: `5px solid #C95000` }}
        >
          <span
            className="material-icons nsw-material-icons"
            focusable="false"
            aria-hidden="true"
          >
            error
          </span>
          {renderMapDraggedErrorMessage()}
        </div>
      )}
    </>
  )
}

export default SearchErrors
