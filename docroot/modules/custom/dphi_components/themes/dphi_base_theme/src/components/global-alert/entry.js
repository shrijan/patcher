import './global-alert.scss'

Drupal.behaviors.globalalert = {
  attach: () => {
    const getCookie = name => {
      const nameEQ = name + '='
      const ca = document.cookie.split(';')
      for (let i=0; i < ca.length; i++) {
          let c = ca[i]
          while (c.charAt(0) == ' ') {
            c = c.substring(1, c.length)
          }
          if (c.indexOf(nameEQ) == 0) {
            return c.substring(nameEQ.length, c.length)
          }
      }
    }
    once('global-alert', '#global-alert-container').forEach(container => {
      fetch('/global-alert-display').then(response => {
        response.json().then(json => {
          Object.entries(json).forEach(([i, val]) => {
            if (getCookie('alert-'+i)) {
              return
            }
            const alert = document.createElement('div')
            alert.classList.add('nsw-global-alert')
            if (val.alert_type_class) {
              alert.classList.add(val.alert_type_class)
            }
            alert.setAttribute('role', 'alert')
            container.appendChild(alert)

            const wrapper = document.createElement('div')
            wrapper.classList.add('nsw-global-alert__wrapper')
            alert.appendChild(wrapper)

            const content = document.createElement('div')
            content.classList.add('nsw-global-alert__content')
            wrapper.appendChild(content)

            const title = document.createElement('div')
            title.classList.add('nsw-global-alert__title')
            title.innerText = val.title
            content.appendChild(title)

            if (val.body) {
              const body = document.createElement('p')
              body.innerHTML = val.body
              content.appendChild(body)
            }

            if (val.cta_url) {
              const p = document.createElement('p')
              content.appendChild(p)

              const cta = document.createElement('a')
              cta.href = val.cta_url
              if (val.button_class) {
                cta.className = val.button_class
              }
              cta.classList.add('nsw-button')
              cta.innerText = val.cta_text
              p.appendChild(cta)
            }

            const button = document.createElement('button')
            button.classList.add('nsw-icon-button')
            button.type = 'button'
            button.ariaExpanded = true
            button.addEventListener('click', () => {
              alert.remove()
              document.cookie = 'alert-'+i+'=1;path=/'
            })
            content.appendChild(button)

            const icon = document.createElement('span')
            icon.classList.add('material-icons', 'nsw-material-icons')
            icon.setAttribute('focusable', 'false')
            icon.ariaHidden = true
            icon.innerText = 'close'
            button.appendChild(icon)

            const sr = document.createElement('span')
            sr.classList.add('sr-only')
            sr.innerText = 'Close message'
            button.appendChild(sr)
          })
        })
      })
    })
  }
}
