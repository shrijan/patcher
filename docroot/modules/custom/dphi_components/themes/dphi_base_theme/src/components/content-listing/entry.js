import './content-listing.scss'

Drupal.behaviors.contentListing = {
  attach: function (context, settings) {
    once(
      'content_card_sort',
      '#paragraph-content-listing-sort #sort-by',
      context,
    ).forEach(element => {
      const sortContentList = () => {
        const selectedValue = element.value
        const closestContentListing = element.closest(
          '.component--content-listing'
        )
        const list = closestContentListing.querySelector(
          '.nsw-content-listing-items'
        )
        const items = Array.from(list.children)

        items.sort((a, b) => {
          const titleA = a.firstElementChild
            .getAttribute('data-title')
            .toLowerCase()
          const titleB = b.firstElementChild
            .getAttribute('data-title')
            .toLowerCase()
          const dateA = new Date(a.firstElementChild.getAttribute('data-date'))
          const dateB = new Date(b.firstElementChild.getAttribute('data-date'))

          if (selectedValue === 'title_asc') {
            return titleA.localeCompare(titleB)
          } else if (selectedValue === 'title_desc') {
            return titleB.localeCompare(titleA)
          } else if (selectedValue === 'date_asc') {
            return dateA - dateB
          } else if (selectedValue === 'date_desc') {
            return dateB - dateA
          }
        })

        // Remove all items from the list and append them in sorted order
        list.innerHTML = ''
        items.forEach(item => list.appendChild(item))
      }
      element.addEventListener('change', sortContentList)
      sortContentList()
    })
  },
}
