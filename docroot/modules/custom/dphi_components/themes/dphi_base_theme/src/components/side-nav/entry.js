import './side-nav.scss'
import { toggle, up, down } from 'slide-element'

Drupal.behaviors.sidenav = {
  attach: () => {
    // JS For Side Navigation Expand/Collapse
    const sideNavTriggers = document.querySelectorAll(
      '.nsw-side-nav .sidenav-trigger',
    )

    const popUpEventTypes = ['keypress', 'click']

    function managePopupEventHandlers(element, events, handler) {
      events.forEach(evt => {
        element.removeEventListener(evt, handler)
        element.addEventListener(evt, handler)
      })
    }

    function getTarget(el, type) {
      if (el.nodeName !== type.toUpperCase()) {
        return el.closest(type)
      }
      return el
    }

    function sideBarMenu(evt) {
      evt.stopImmediatePropagation()
      evt.preventDefault()
      var trigger = getTarget(evt.target, 'button') // make sure we're dealing with the button.

      const subMenu = document.querySelector(
        `[id="${trigger.getAttribute('aria-controls')}"]`,
      )

      if (trigger.getAttribute('aria-expanded').toString() === 'false') {
        trigger.setAttribute('aria-expanded', 'true')
        subMenu.setAttribute('aria-hidden', 'false')
        down(subMenu, { duration: 200 })
        sideNavTriggerArray.push(trigger)
      } else {
        trigger.setAttribute('aria-expanded', 'false')
        subMenu.setAttribute('aria-hidden', 'true')
        up(subMenu, { duration: 200 })
      }
    }

    function forceCloseMenuLevel(menu) {
      const trigger = sideNavTriggerArray.pop()
      const subMenu = document.querySelector(
        `[id="${trigger.getAttribute('aria-controls')}"]`,
      )
      trigger.setAttribute('aria-expanded', 'false')
      subMenu.setAttribute('aria-hidden', 'true')
      up(subMenu, { duration: 200 })
    }

    function handleEscape(evt) {
      if (evt.key.toLowerCase() === 'escape') {
        evt.stopImmediatePropagation()
        evt.preventDefault()
        forceCloseMenuLevel(menu)
      }
    }

    const sideNavTriggerArray = []

    if (sideNavTriggers.length) {
      Array.from(sideNavTriggers).forEach(sideNavTrigger => {
        if (!sideNavTrigger.classList.contains('processed')) {
          sideNavTriggerArray.push({})
          managePopupEventHandlers(sideNavTrigger, popUpEventTypes, sideBarMenu)
        }
        sideNavTrigger.classList.add('processed')
      })
      document.addEventListener('keypress', handleEscape)
    }

    // Display Side Navigation for Mobile
    const navButtons = document.querySelectorAll(
      '.nsw-side-nav-mobile-trigger button',
    )
    navButtons.forEach(navButton => {
      navButton.addEventListener('click', e => {
        const sideNavs = document.querySelectorAll('.nsw-side-nav')
        sideNavs.forEach(sideNav => {
          toggle(sideNav, { duration: 200 })
        })
        navButton.classList.toggle('active')
      })
    })

    if (!drupalSettings.left_nav_expand) {
      const sideNaveAccordions = document.querySelectorAll(
        '.nsw-side-nav li button.sidenav-trigger',
      )
      sideNaveAccordions.forEach(button => {
        button.style.display = 'none'
      })
    }
  },
}
