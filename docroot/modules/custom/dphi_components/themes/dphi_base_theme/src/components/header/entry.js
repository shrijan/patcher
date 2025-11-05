import Navigation from 'nsw-design-system/src/components/main-nav/main-nav'

Drupal.behaviors.navigation = {
  attach: () => {
    once('navigation', '.nsw-main-nav').forEach(element => {
      const navigation = new Navigation(element)

      const buttonKeydownDesktop = navigation.buttonKeydownDesktop.bind(navigation)
      navigation.buttonKeydownDesktop = event => {
        if (event.target.closest('.nsw-main-nav__sub-list') !== null) {
          return
        }
        buttonKeydownDesktop(event)
      }

      navigation.init()
    })
  },
}

Drupal.behaviors.search = {
  attach: () => {
    const search = document.querySelector('#header-search')

    once('search-open', '.js-open-search').forEach(element => {
      element.addEventListener('click', () => {
        search.hidden = false
        search.querySelector('input').focus()
      })
    })

    once('search-close', '.js-close-search').forEach(element => {
      element.addEventListener('click', () => {
        search.hidden = true
        search.querySelector('.js-open-search').focus()
      })
    })
  },
}

import './header.scss'
