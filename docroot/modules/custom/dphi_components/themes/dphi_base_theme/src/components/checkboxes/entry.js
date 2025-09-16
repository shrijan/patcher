import './checkboxes.scss'

Drupal.behaviors.checkboxes = {
  attach: function (context, settings) {
    once(
      'checkboxes',
      '.check-limit',
      context,
    ).forEach(element => {
      const allOptions = element.querySelector('.all-options')
      const lessOptions = element.querySelector('.less-options')
      allOptions.addEventListener('click', e => {
        e.preventDefault()

        element.classList.add('active')
        lessOptions.hidden = false
        allOptions.hidden = true
      })
      lessOptions.addEventListener('click', e => {
        e.preventDefault()

        element.classList.remove('active')
        allOptions.hidden = false
        lessOptions.hidden = true
      })
    })
  },
}
