import './tooltip.scss'

import Tooltip from 'nsw-design-system/src/components/tooltip/tooltip'
import Toggletip from 'nsw-design-system/src/components/tooltip/toggletip'

// For some reason the standard browser behaviour of sending a click event to a
// button when the user types space or enter isn't working on this site. This is
// a workaround and should be removed if we manage to fix the default behaviour.
document.addEventListener('keyup', event => {
  if (event.key === ' ' || event.key === 'Enter') {
    const focusedElement = document.activeElement
    if (focusedElement.tagName.toLowerCase() === 'button') {
      const clickEvent = new MouseEvent('click', {
        bubbles: true,
        cancelable: true,
        view: window,
      })
      focusedElement.dispatchEvent(clickEvent)
    }
  }
})

Drupal.behaviors.tooltip = {
  attach: () => {
    const checkAriaControls = element => {
      const id = element.getAttribute('aria-controls')
      if (id && !document.querySelector('#' + id)) {
        element.removeAttribute('aria-controls')
        return false
      }

      return true
    }

    once('tooltip', '.nsw-tooltip.js-tooltip').forEach(element => {
      if (checkAriaControls(element)) {
        const tooltip = new Tooltip(element)
        tooltip.init()
      } else {
        element.classList.remove('nsw-tooltip')
      }
    })
    once('toggletip', '.nsw-toggletip.js-toggletip').forEach(element => {
      if (checkAriaControls(element)) {
        // This is only needed for existing content
        // New toggletips should not have a "title" attribute,
        // so this may no longer be necessary at some point in the future.
        element.removeAttribute('title')

        const toggletip = new Toggletip(element)
        toggletip.init()

        const observer = new MutationObserver(mutationList => {
          for (const mutation of mutationList) {
            if (mutation.attributeName == 'aria-expanded') {
              if (!toggletip.toggletipIsOpen) {
                element.focus()
              }
              break
            }
          }
        })
        observer.observe(toggletip.toggletipElement, {attributes: true})
      } else {
        element.classList.remove('nsw-toggletip')
      }
    })
  },
}
