import React, { useEffect } from 'react'
import Modal from 'react-modal'
import { disableBodyScroll, enableBodyScroll } from 'body-scroll-lock-upgrade'
import CloseIcon from '../../assets/icons/Close.svg?react'
import { Splide, SplideSlide } from '@splidejs/react-splide'
import '@splidejs/react-splide/css'

const PropertyModal = ({
  isOpen,
  closeModal,
  content,
  filterTerms,
  modalCtaLabel,
}) => {
  // useEffect(() => {
  //   const targetElement = document.querySelector(
  //     '.dialog-off-canvas-main-canvas',
  //   )

  //   if (isOpen && targetElement) {
  //     disableBodyScroll(targetElement)
  //   } else if (targetElement) {
  //     enableBodyScroll(targetElement)
  //   }

  //   return () => {
  //     if (targetElement) {
  //       enableBodyScroll(targetElement)
  //     }
  //   }
  // }, [isOpen])

  const getMatchingFilterTerm = () => {
    return filterTerms.find(term => term.id === content.pin_type)
  }

  const matchingFilterTerm = getMatchingFilterTerm()

  return (
    <Modal
      isOpen={isOpen}
      onRequestClose={closeModal}
      contentLabel="Pin Details"
      className="Modal"
      overlayClassName="Overlay"
    >
      {content && (
        <div className="modal-content">
          <button onClick={closeModal} className="modal-close-button">
            <CloseIcon role="img" aria-label="Close" />
          </button>
          {content.images && content.images.length > 0 && (
            <div className="modal-hero-image">
              <Splide
                options={{
                  perPage: 1,
                  arrows: content.images.length > 1, // Only show arrows if more than one image
                  pagination: true,
                  drag: content.images.length > 1, // Allow dragging only if more than one image
                }}
              >
                {content.images.map((image, index) => (
                  <SplideSlide key={index}>
                    <img src={image.modal} alt={image.alt} />
                  </SplideSlide>
                ))}
              </Splide>
            </div>
          )}
          <div className="modal-details">
            <div className="modal-details-container">
              <p className="modal-title">{content.name}</p>
              <p className="modal-location">
                {`${content.suburb}${content.indigenous_location_name ? `, ${content.indigenous_location_name}` : ''}`}
              </p>
              {matchingFilterTerm && (
                <p className="modal-filter-term">
                  <img
                    src={matchingFilterTerm.iconUrl}
                    alt={matchingFilterTerm.name}
                  />
                  <span>{matchingFilterTerm.name}</span>
                </p>
              )}
              <div className="modal-field-container">
                {content.field_2 && (
                  <p className="modal-field">
                    {content.field_2.label}: {content.field_2.value}
                  </p>
                )}
                {content.field_3 && (
                  <p className="modal-field">
                    {content.field_3.label}: {content.field_3.value}
                  </p>
                )}
                {content.field_4 && (
                  <p className="modal-field">
                    {content.field_4.label}: {content.field_4.value}
                  </p>
                )}
                {content.field_5 && (
                  <p className="modal-field">
                    {content.field_5.label}: {content.field_5.value}
                  </p>
                )}
                {content.field_6 && (
                  <p className="modal-field">
                    {content.field_6.label}: {content.field_6.value}
                  </p>
                )}
                {content.field_7 && (
                  <p className="modal-field">
                    {content.field_7.label}: {content.field_7.value}
                  </p>
                )}
              </div>
            </div>
            <div className="modal-description-container">
              <p
                className="modal-description"
                dangerouslySetInnerHTML={{ __html: content.short_description }}
              ></p>
              {content.cta && (
                <button
                  onClick={() => {
                    window.location.href = `/node/${content.cta.target_id}`
                  }}
                  className="modal-cta"
                >
                  {modalCtaLabel}
                </button>
              )}
            </div>
          </div>
        </div>
      )}
    </Modal>
  )
}

export default PropertyModal
