import './accordion.scss'

import Accordion from 'nsw-design-system/src/components/accordion/accordion';

Drupal.behaviors.accordion = {
  attach: () => {
    once('accordion', '.nsw-accordion.js-accordion').forEach(element => {
      const accordion = new Accordion(element)
      accordion.init()

      accordion.buttons.forEach(element => {
        element.removeEventListener('click', accordion.toggleEvent)

        element.addEventListener('mousedown', event => {
          accordion.toggle(event)
        })
        element.addEventListener('keydown', event => {
          if (event.repeat) {
            return
          }
          if ([' ', 'Enter'].includes(event.key)) {
            accordion.toggle(event)
          }
        })
      })
    })
  }
}
