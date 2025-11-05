import TooltipButtonsEditing from "./tooltipbuttonsediting";
import TooltipButtonsUi from "./tooltipbuttonsui";
import { Plugin } from 'ckeditor5/src/core';

class TooltipButtons extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [TooltipButtonsEditing, TooltipButtonsUi];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'TooltipButtons';
  }
}

export default {
  TooltipButtons,
};
