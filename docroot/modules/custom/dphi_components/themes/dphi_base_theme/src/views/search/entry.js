import './search.scss'

Drupal.behaviors.searchView = {
  attach: () => {
    once('search-category', '.js-form-item-field-content-category-value').forEach(element => {
      const div = document.createElement('div')
      div.classList.add('tabbify')

      const select = element.querySelector('select')
      Array.from(select.children).forEach(option => {
        const button = document.createElement('button')

        button.innerText = option.value == 'All' ? 'All' : option.innerText
        if (option.selected) {
          button.classList.add('selected')
        }
        button.addEventListener('click', event => {
          event.preventDefault()

          select.value = option.value
          div.querySelector('.selected').classList.remove('selected')
          button.classList.add('selected')
        })

        div.appendChild(button)
      })
      element.insertBefore(div, select)
    })
    once('search-date', '.js-form-item-daterange input').forEach(element => {
      const toggle = () => {
        const parent = element.parentNode
        parent.classList.toggle('active', element.checked)
        if (element.checked) {
          setTimeout(() => {
            parent.classList.add('open')
          }, 500)
        } else {
          parent.classList.remove('open')
        }
        element.closest('form').querySelectorAll('.js-date-input__text, .js-date-input__trigger').forEach(input => {
          if (element.checked) {
            input.removeAttribute('tabIndex')
          } else {
            input.value = ''
            input.tabIndex = -1
          }
        })
      }
      element.addEventListener('change', toggle)
      toggle()
    })

    once('search-sort', '.form-item-sort-by select').forEach(element => {
      element.addEventListener('change', () => {
        const params = new URLSearchParams(window.location.search)
        params.set(element.name, element.value)
        window.location = window.location.pathname + '?' + params.toString()
      })
    })

    once('search-mobile', '#toggle-filters').forEach(element => {
      element.addEventListener('click', () => {
        const ariaExpanded = element.ariaExpanded == 'false'
        element.ariaExpanded = ariaExpanded

        const filters = element.nextElementSibling
        if (ariaExpanded) {
          filters.style.height = 'auto'
          const fullHeight = filters.clientHeight

          filters.style.height = 0
          setTimeout(() => {
            filters.style.height = fullHeight.toString()+'px'
          }, 0)
          setTimeout(() => {
            filters.style.height = 'auto'
            filters.classList.add('nsw-overflow-visible')
          }, 500)
        } else {
          const fullHeight = filters.clientHeight
          filters.style.height = fullHeight.toString()+'px'
          filters.classList.remove('nsw-overflow-visible')

          setTimeout(() => {
            filters.style.height = 0
          }, 0)
        }
      })
    })

    once('search-apply-filter-top', '.apply-filter-btn-top').forEach(element => {
      element.addEventListener('click', () => {
        element.closest('.filters').querySelector('form').submit()
      })
    })
  }
}
