import './date-picker.scss'

import DatePicker from 'nsw-design-system/src/components/date-picker/date-picker';

Drupal.behaviors.datepicker = {
  attach: () => {
    once('datepicker', '.js-date-input__text').forEach(element => {
      const parent = element.parentNode
      parent.classList.add('nsw-date-input', 'js-date-input')
      parent.dataset.dateSeparator = '-';

      const div = document.createElement('div')
      div.classList.add('nsw-date-input__wrapper', 'nsw-form__input-group', 'nsw-form__input-group--icon')
      parent.insertBefore(div, element)

      div.appendChild(element)

      const button = document.createElement('button')
      button.classList.add('nsw-button', 'nsw-button--dark', 'nsw-button--flex', 'js-date-input__trigger')
      button.type = 'button'

      const icon = document.createElement('span')
      icon.classList.add('material-icons', 'nsw-material-icons')
      icon.focusable = false
      icon.ariaHidden = true
      icon.innerText = 'calendar_today'
      button.appendChild(icon)
      div.appendChild(button)

      const datepicker = new DatePicker(parent)
      datepicker.init()
      parent.datepicker = datepicker

      // Allow arrow key navigation when NVDA is enabled
      datepicker.datePicker.role = 'application'

      const originalShowCalendar = datepicker.showCalendar
      datepicker.showCalendar = bool => {
        // Close other datepickers when one is opened
        document.querySelectorAll('.js-date-input').forEach(otherParent => {
          if (!otherParent.isEqualNode(parent)) {
            otherParent.datepicker.hideCalendar()
          }
        })

        originalShowCalendar.apply(datepicker, [bool])
      }
    })
  }
}
