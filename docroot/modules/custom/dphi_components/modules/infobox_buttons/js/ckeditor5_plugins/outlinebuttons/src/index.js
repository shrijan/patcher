import OutlineButtonsEditing from "./outlinebuttonsediting";
import OutlineButtonsUi from "./outlinebuttonsui";
import { Plugin } from 'ckeditor5/src/core';

class OutlineButtons extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [OutlineButtonsEditing, OutlineButtonsUi];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'OutlineButtons';
  }
}

export default {
  OutlineButtons,
};
