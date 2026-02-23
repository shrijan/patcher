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
        arrows: false,
        classes: {
          pagination: 'splide__pagination nsw-pagination',
          page: 'splide__pagination__page',
        },
      })

      splide.on('mounted', () => {
        const arrows = element.querySelector('.splide__arrows')
        if (arrows) {
          arrows.style.display = 'flex';

          [['.splide__arrow--prev', '<'], ['.splide__arrow--next', '>']].forEach(([className, control]) => {
            const button = arrows.querySelector(className)
            button.addEventListener('click', event => {
              splide.go(control)
            })
            button.addEventListener('keydown', event => {
              if (event.key == 'Enter') {
                event.preventDefault()
              }
            })
          })
        }

        splide.Components.Elements.slides.forEach(li => {
          li.setAttribute('role', 'presentation')
        })
      })

      splide.mount()
    })
  },
}
