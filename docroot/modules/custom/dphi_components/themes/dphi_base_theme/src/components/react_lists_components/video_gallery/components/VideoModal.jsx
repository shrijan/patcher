import { useEffect, useRef } from 'react'

export default function VideoModal({ item, onClose, theme }) {
  const modalRef = useRef(null)
  const previousActiveElement = useRef(null)

  const getFocusableElements = () => {
    if (!modalRef.current) return [];
    return Array.from(
      modalRef.current.querySelectorAll(
        'iframe, button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      )
    );
  };

  useEffect(() => {
    previousActiveElement.current = document.activeElement;
    const focusableElements = getFocusableElements();
    if (focusableElements.length > 0) {
      focusableElements[0].focus();
    }

    const handleKeyDown = (event) => {
      if (event.key === 'Escape') {
        onClose();
        return;
      }

      const focusableElements = getFocusableElements();
      if (focusableElements.length === 0) return;

      const firstElement = focusableElements[0];
      const lastElement = focusableElements[focusableElements.length - 1];

      if (event.key === 'Tab') {
        if (event.shiftKey) {
          // Shift + Tab
          if (!modalRef.current.contains(document.activeElement) || document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
          }
        } else {
          // Tab
          if (document.activeElement === lastElement || !modalRef.current.contains(document.activeElement)) {
            event.preventDefault();
            firstElement.focus();
          }
        }
      }
    };

    document.addEventListener('keydown', handleKeyDown);

    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      if (previousActiveElement.current) {
        previousActiveElement.current.focus();
      }
    }
  }, [onClose]);

  const getThemeClasses = (theme) => {
    switch (theme) {
      case 'light':
        return 'nsw-bg--brand-light';
      case 'dark':
        return 'nsw-bg--brand-dark nsw-text--light';
      default:
        return '';
    }
  }

  const onClick = (event) => {
    if (['video-modal', 'close-button'].includes(event.target.className)) {
      onClose()
    }
  }

  return (
    <div className="video-modal" onClick={onClick}>
      <div className="video-modal-content" ref={modalRef}>
        <div className="nsw-media__video">
          <iframe
            title={item.title}
            src={item.iframe}
            frameBorder="0"
            allowFullScreen
            tabIndex="0"
          />
        </div>
        <figcaption
          className={`video-caption ${getThemeClasses(theme)}`}
          dangerouslySetInnerHTML={{ __html: item.caption }}
        ></figcaption>
        <button
          className="close-button"
          aria-label="Close video modal"
        >
          X
        </button>
      </div>
    </div>
  )
}
