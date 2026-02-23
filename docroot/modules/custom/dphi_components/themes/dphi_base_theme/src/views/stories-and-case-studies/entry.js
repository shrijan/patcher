import './stories-and-case-studies.scss'

Drupal.behaviors.storiesAndCaseStudies = {
  attach: () => {
    once('stories-and-case-studies-view', '.stories-and-case-studies .tabbify-view-by .option').forEach(element => {
      element.addEventListener('click', () => {
        const grid = element.closest('.nsw-container').querySelector('.content-row')
        const optionClasses = element.parentElement.classList
        grid.querySelectorAll('.social-and-cases-grid').forEach(item => {
          item.hidden = !optionClasses.contains('option--cards')
        })
        grid.querySelectorAll('.news-events-list').forEach(item => {
          item.hidden = !optionClasses.contains('option--list')
        })
      })
    })
  }
}
