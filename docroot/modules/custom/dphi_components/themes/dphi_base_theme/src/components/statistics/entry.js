import './statistics.scss'

Drupal.behaviors.statistics = {
  attach: () => {
    once('statistics', '.statistics .dynamicCounter .number').forEach(element => {
      let current = 0
      const end = parseInt(element.innerText.replace(/[^0-9]/g, ''))

      const increase = () => {
        current += 1
        element.innerText = current
        if (current < end) {
          setTimeout(increase, 50)
        }
      }
      increase()
    })
  }
}
