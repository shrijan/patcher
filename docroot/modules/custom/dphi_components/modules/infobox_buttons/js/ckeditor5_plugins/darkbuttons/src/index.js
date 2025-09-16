import DarkButtonsEditing from "./darkbuttonsediting";
import DarkButtonsUi from "./darkbuttonsui";
import { Plugin } from 'ckeditor5/src/core';

class DarkButtons extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [DarkButtonsEditing, DarkButtonsUi];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DarkButtons';
  }
}

export default {
  DarkButtons,
};
