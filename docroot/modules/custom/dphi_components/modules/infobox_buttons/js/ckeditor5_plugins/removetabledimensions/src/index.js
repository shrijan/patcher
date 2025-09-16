import RemoveTableDimensionsUi from "./removetabledimensionsui";
import { Plugin } from 'ckeditor5/src/core';

class RemoveTableDimensions extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [RemoveTableDimensionsUi];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'RemoveTableDimensions';
  }
}

export default {
  RemoveTableDimensions,
};
