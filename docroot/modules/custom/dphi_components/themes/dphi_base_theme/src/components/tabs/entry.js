import './tabs.scss'

import Tabs from 'nsw-design-system/src/components/tabs/tabs'

Drupal.behaviors.tabs = {
  attach: () => {
    once('tabs', '.nsw-tabs.js-tabs').forEach(element => {
      const tabs = new Tabs(element)
      tabs.init()
    })
  },
}
