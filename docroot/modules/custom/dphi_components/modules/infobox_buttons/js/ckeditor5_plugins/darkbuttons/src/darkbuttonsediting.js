import {Plugin} from 'ckeditor5/src/core';
import {Widget} from 'ckeditor5/src/widget';
import InsertMaterialIconsCommand from './insertdarkbuttonscommand';

export default class DarkButtonsEditing extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [Widget];
  }
  /**
   * @inheritdoc
   */
  init() {
    this._defineSchema();
    this._defineConverters();
    this._defineCommands();
  }

  /**
   * Registers darkbutton and materialIcon as an element in the DOM converter.
   *
   * @private
   */
  _defineSchema() {
    const schema = this.editor.model.schema;
    schema.register('buttonsDarkAnchor', {
      allowWhere: '$inlineObject',
      isInline: true,
      allowAttributes: ['class', 'href', 'target', 'icon', 'aria-label', 'data-entity-type', 'data-entity-uuid'],
      isContent: true
    });

  }

  /**
   * Defines handling of dark button element in the content.
   *
   * @private
   */
  _defineConverters() {
    const conversion = this.editor.conversion;

    // Allow class attribute.
    conversion.attributeToAttribute({model: 'class', view: 'class'});
    conversion.attributeToAttribute({model: 'href', view: 'href'});
    conversion.attributeToAttribute({model: 'target', view: 'target'});
    conversion.attributeToAttribute({model: 'icon', view: 'icon'});
    conversion.attributeToAttribute({model: 'aria-label', view: 'aria-label'});
    conversion.attributeToAttribute({model: 'data-entity-type', view: 'data-entity-type'});
    conversion.attributeToAttribute({model: 'data-entity-uuid', view: 'data-entity-uuid'});
    conversion.for('downcast')
      .elementToElement({
        model: 'buttonsDarkAnchor',
        view: 'a'
      });
    conversion.for('upcast')
      .elementToElement({
        view: {
          name: 'a',
          classes: 'buttons-anchor',
        },
        model: 'buttonsDarkAnchor'
      });
  }

  /**
   * Defines the dark button and material icon insert command.
   *
   * @private
   */
  _defineCommands() {
    const editor = this.editor;
    editor.commands.add(
      'insertDarkButtons',
      new InsertMaterialIconsCommand(this.editor),
    );
  }

}
