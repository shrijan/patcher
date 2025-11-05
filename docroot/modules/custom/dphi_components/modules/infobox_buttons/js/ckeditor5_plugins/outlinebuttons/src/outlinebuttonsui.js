/**
 * @file registers the Outline button to toolbar and binds functionality to
 *   it.
 */

import { Plugin } from 'ckeditor5/src/core';
import { ButtonView } from 'ckeditor5/src/ui';
import icon from "../../../../icons/buttons1.svg";

export default class OutlineButtonsUI extends Plugin {
  init() {
    const editor = this.editor;
    const options = this.editor.config.get('outlinebuttons');
    if (!options) {
      return;
    }

    const {openDialog, dialogSettings = {}} = options;
    if (typeof openDialog !== 'function') {
      return;
    }

    // This will register the simpleBox toolbar button.
    editor.ui.componentFactory.add('outlinebuttons', (locale) => {
      const command = editor.commands.get('insertOutlineButtons');
      const buttonView = new ButtonView(locale);

      // Create the toolbar button.
      buttonView.set({
        label: Drupal.t('Button outline'),
        icon,
        tooltip: true,
      });

      // Bind the state of the button to the command.
      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');
      this.listenTo(buttonView, 'execute', () => {
        openDialog(
          Drupal.url('style_buttons/dialog'),
          ({ settings }) => {
            editor.execute('insertOutlineButtons', settings);
          },
          dialogSettings,
        );
      });

      return buttonView;
    });
  }
}
