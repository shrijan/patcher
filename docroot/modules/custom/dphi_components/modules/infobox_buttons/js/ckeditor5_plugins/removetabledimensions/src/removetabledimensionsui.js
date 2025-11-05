/**
 * @file removes controls from CKEditor plugins.
 */

import { Plugin } from 'ckeditor5/src/core';

export default class RemoveTableDimensionsUI extends Plugin {
  init() {
    this.editor.plugins.get('ContextualBalloon').on('set:visibleView', () => {
      // A view has appeared
      ['TableCellPropertiesUI', 'TablePropertiesUI'].forEach(name => {
        if(!this.editor.plugins.has(name)) {
          return
        }

        const plugin = this.editor.plugins.get(name)
        if (!plugin.view) {
          return
        }

        // Remove 'Dimensions' from each plugin's view
        const children = plugin.view.children
        children.filter(child => Array.from(child.children).some(grandchild => grandchild.class == 'ck-table-form__dimensions-row')).forEach(child => {
          children.remove(child)
        })
      })
    });
  }
}
