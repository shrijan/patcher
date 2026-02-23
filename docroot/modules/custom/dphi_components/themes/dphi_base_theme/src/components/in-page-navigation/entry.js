import 'anchorific'

Drupal.behaviors.onThisPage = {
  attach: () => {
    once('onThisPage', '.nsw-section-start').forEach(element => {
      jQuery(element).anchorific({
        anchorText: '',
        headers: 'h1:visible, h2:visible',
      })

      document
        .querySelectorAll('h1 a.anchor, h2 a.anchor')
        .forEach(anchorLink => {
          anchorLink.tabIndex = -1
          anchorLink.classList.add('sr-only')
          anchorLink.innerText = anchorLink.parentElement.innerText
        })
    })
  },
}
