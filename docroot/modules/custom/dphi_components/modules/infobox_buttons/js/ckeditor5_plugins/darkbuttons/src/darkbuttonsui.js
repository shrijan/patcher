/**
 * @file registers the Dark buttons toolbar button and binds functionality to
 *   it.
 */

import { Plugin } from 'ckeditor5/src/core';
import { ButtonView } from 'ckeditor5/src/ui';
import icon from "../../../../icons/buttons2.svg";

export default class DarkButtonsUI extends Plugin {
  init() {
    const editor = this.editor;
    const options = this.editor.config.get('darkbuttons');
    if (!options) {
      return;
    }

    const {openDialog, dialogSettings = {}} = options;
    if (typeof openDialog !== 'function') {
      return;
    }

    // This will register the simpleBox toolbar button.
    editor.ui.componentFactory.add('darkbuttons', (locale) => {
      const command = editor.commands.get('insertDarkButtons');
      const buttonView = new ButtonView(locale);

      // Create the toolbar button.
      buttonView.set({
        label: Drupal.t('Button dark'),
        icon,
        tooltip: true,
      });

      // Bind the state of the button to the command.
      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');
      this.listenTo(buttonView, 'execute', () => {
        openDialog(
          Drupal.url('style_buttons/dialog'),
          ({ settings }) => {
            editor.execute('insertDarkButtons', settings);
          },
          dialogSettings,
        );
      });

      return buttonView;
    });
  }
}
