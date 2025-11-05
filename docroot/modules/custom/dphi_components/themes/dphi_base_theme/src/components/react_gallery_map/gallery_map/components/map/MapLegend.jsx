import React, { useState } from 'react'
import LegendIcon from '../../assets/icons/LegendIcon.svg?react'

const MapLegend = ({ filterTerms }) => {
  const [isOpen, setIsOpen] = useState(false)

  const toggleLegend = () => {
    setIsOpen(!isOpen)
  }

  return (
    <div className={`map-legend ${isOpen ? 'expanded' : 'collapsed'}`}>
      <button
        className={`nsw-button legend-header ${isOpen ? 'expanded' : 'collapsed'}`}
        onClick={toggleLegend}
        aria-expanded={isOpen}
        tabIndex={0}
        aria-label="Toggle map legend"
        aria-controls="legend-content"
      >
        <LegendIcon role="img" className="legend-icon" />
        <span className="legend-title">Map Legend</span>
        {isOpen ? (
          <span className="close-icon material-icons">close</span>
        ) : null}
      </button>
      {isOpen && (
        <div
          id="legend-content"
          className="legend-contents"
          onClick={e => e.stopPropagation()}
        >
          {filterTerms.map((term, index) => (
            <div key={index} className="legend-item">
              <div
                className="icon-circle"
                style={{ backgroundColor: term.colour }}
              >
                <img
                  src={term.iconUrl}
                  alt={term.name}
                  className="legend-icon"
                />
              </div>
              <span className="legend-description">{term.name}</span>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}

export default MapLegend
