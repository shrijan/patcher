import './card-carousel.scss'

import Carousel from 'nsw-design-system/src/components/card-carousel/carousel';

Drupal.behaviors.cardCarousel = {
  attach: () => {
    once('cardCarousel', '.nsw-carousel.js-carousel').forEach(element => {
      const carousel = new Carousel(element)
      carousel.init()
    })
  }
}
