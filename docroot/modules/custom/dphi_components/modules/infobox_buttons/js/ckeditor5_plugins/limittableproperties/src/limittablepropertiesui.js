/**
 * @file removes controls from CKEditor plugins.
 */

import { Plugin } from 'ckeditor5/src/core';

export default class LimitTablePropertiesUI extends Plugin {
  init() {
    if(!this.editor.plugins.has('ContextualBalloon')) {
      return
    }

    const contentToolbar = this.editor.config.get('table.contentToolbar')
    if (contentToolbar) {
      this.editor.config.set('table.contentToolbar', this.editor.config.get('table.contentToolbar').filter(x => x != 'tableProperties'))
    }

    this.editor.plugins.get('ContextualBalloon').on('set:visibleView', e => {
      // A view has appeared
      if (!this.editor.plugins.has('TableCellPropertiesUI')) {
        return
      }
      const plugin = this.editor.plugins.get('TableCellPropertiesUI')
      if (!plugin.view) {
        return
      }

      // Remove 'Border' and 'Background' from the plugin's view
      const children = plugin.view.children
      children.filter(child => ['ck-table-form__border-row', 'ck-table-form__background-row'].includes(child.class)).forEach(child => {
        children.remove(child)
      })
    })
  }
}
