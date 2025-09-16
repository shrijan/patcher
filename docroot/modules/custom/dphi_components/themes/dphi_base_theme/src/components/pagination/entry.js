import './pagination.scss'

Drupal.behaviors.pagination = {
  attach: () => {
    once('pagination', '.pager__items').forEach(element => {
      const getPageNumber = li => parseInt(li.querySelector('a').href.split('page=')[1]) + 1
      const numberOfPages = getPageNumber(element.querySelector('.pager__item--last'))
      if (numberOfPages <= 5) {
        return
      }

      let pages
      const active = element.querySelector('.pager__item.is-active')
      const currentPage = getPageNumber(active)
      if (currentPage <= 3) {
        pages = [1, 2, 3, 4, '...', numberOfPages]
      } else if (currentPage >= numberOfPages - 2) {
        pages = [1, '...', numberOfPages - 3, numberOfPages - 2, numberOfPages - 1, numberOfPages]
      } else {
        pages = [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', numberOfPages]
      }

      const items = Array.from(element.querySelectorAll('.pager__item:not(.pager__item--first):not(.pager__item--previous):not(.pager__item--next):not(.pager__item--last)'))
      const numbers = []
      const ellipses = []
      items.forEach(item => {
        if (item == active) {
          return
        } else if (item.classList.contains('pager__item--ellipsis')) {
          ellipses.push(item)
        } else {
          numbers.push(item)
        }
      })

      let current = element.querySelector('.pager__item--next')
      pages.reverse().forEach(page => {
        let item
        if (page == '...') {
          item = ellipses.shift()
          if (!item) {
            item = document.createElement('li')
            item.innerHTML = '&hellip;'
          }
        } else if (page == currentPage) {
          item = active
        } else {
          item = numbers.shift()

          const a = item.querySelector('a')
          a.href = a.href.replace(/page=[0-9]+/, 'page='+(page - 1).toString())
          a.title = 'Go to page '+page.toString()
          a.childNodes[a.childNodes.length - 1].nodeValue = page.toString()
        }
        if (current) {
          element.insertBefore(item, current)

          current = item
        } else {
          element.prepend(item)
        }
      })
      numbers.forEach(item => item.remove())
      ellipses.forEach(item => item.remove())
    })
  }
}
