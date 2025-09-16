import LimitTablePropertiesUi from "./limittablepropertiesui";
import { Plugin } from 'ckeditor5/src/core';

class LimitTableProperties extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [LimitTablePropertiesUi];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'LimitTableProperties';
  }
}

export default {
  LimitTableProperties,
};
