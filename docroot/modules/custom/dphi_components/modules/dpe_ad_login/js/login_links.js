(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.dpeadloginloginlinks = {
    attach: () => {
      once('dpe_ad_login_external', '.dpe_ad_login_external').forEach(card => {
        const loginForm = document.querySelector('.user-login-form')
        const message = document.querySelector('.dpe_ad_login_external_message')
        const cardLi = card.closest('li')
        card.addEventListener('click', event => {
          if (cardLi.classList.contains('active')) {
            return
          }
          event.preventDefault()

          card.closest('ul').querySelectorAll('li').forEach(li => {
            li.classList.remove('active')
          })
          cardLi.classList.add('active')

          if (message) {
            message.hidden = false
          }
          if (loginForm) {
            loginForm.style.visibility = 'visible'
          }
        })
      })
    }
  }
})(Drupal, once)
