import './hero-banner-search.scss'

Drupal.behaviors.heroBannerSearch = {
  attach: () => {
    once('heroSearch', '.hero-search').forEach(element => {
      element.style.display = 'none'
    })
    once('heroBannerSearch', '.hero-banner-search .filter-results .keyboard-arrow-right').forEach(element => {
      element.addEventListener('click', event => {
        event.preventDefault()
        element.classList.toggle('active')

        const filtersList = element.closest('form').querySelector('.nsw-filters__list')
        filtersList.classList.toggle('active')

        filtersList.querySelectorAll('.all').forEach(checkbox => {
          const allCheckboxes = checkbox.closest('.js-form-type-checkboxes').querySelectorAll('input')
          checkbox.addEventListener('change', () => {
            const checked = checkbox.checked
            allCheckboxes.forEach(input => {
              input.checked = checked
            })
          })
        })
      })
    })
    once('heroBannerSearchText', '.hero-banner-search .form-text').forEach(element => {
      const desktopText = element.placeholder
      if (desktopText?.length > 9) {
        const mediaQuery = window.matchMedia('(min-width: 530px)')
        mediaQuery.addListener(e => {
          element.placeholder = e.matches ? desktopText : 'Search...'
        })
      }
    })
  }
}
