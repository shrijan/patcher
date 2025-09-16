/**
 * @file registers the Outline button to toolbar and binds functionality to
 *   it.
 */

import { Plugin } from 'ckeditor5/src/core';
import { ButtonView } from 'ckeditor5/src/ui';
import icon from "../../../../icons/tooltip.svg";

export default class TooltipButtonsUI extends Plugin {
  init() {
    const editor = this.editor;
    const options = this.editor.config.get('tooltipbuttons');
    if (!options) {
      return;
    }

    const {openDialog, dialogSettings = {}} = options;
    if (typeof openDialog !== 'function') {
      return;
    }

    // This will register the simpleBox toolbar button.
    editor.ui.componentFactory.add('tooltipbuttons', (locale) => {
      const command = editor.commands.get('insertTooltipButtons');
      const buttonView = new ButtonView(locale);

      // Create the toolbar button.
      buttonView.set({
        label: Drupal.t('Button tooltip'),
        icon,
        tooltip: true,
      }); 

      // Bind the state of the button to the command.
      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');
      this.listenTo(buttonView, 'execute', () => {
        openDialog(
          Drupal.url('tooltip_buttons/dialog'),
          ({ settings }) => {
            editor.execute('insertTooltipButtons', settings);
          },
          dialogSettings,
        );
      });

      return buttonView;
    });
  }
}