import './image-carousel-container.scss'

import '@splidejs/splide/css'
import Splide from '@splidejs/splide'

Drupal.behaviors.ImageCarouselContainer = {
  attach: () => {
    once(
      'image-carousel-container',
      '.component--image-carousel-container.splide',
    ).forEach(element => {
      const splide = new Splide(element, {
        type: 'loop',
        arrows: element.querySelector('.splide__arrows') !== null,
        classes: {
          pagination: 'splide__pagination nsw-pagination',
          page: 'splide__pagination__page',
        },
      })

      splide.on('mounted', () => {
        splide.Components.Elements.slides.forEach(li => {
          li.setAttribute('role', 'presentation')
        })
      })

      splide.mount()
    })
  },
}
