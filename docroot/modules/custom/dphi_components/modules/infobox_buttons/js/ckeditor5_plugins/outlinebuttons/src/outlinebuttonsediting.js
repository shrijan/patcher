import {Plugin} from 'ckeditor5/src/core';
import {Widget} from 'ckeditor5/src/widget';
import InsertMaterialIconsCommand from './insertoutlinebuttonscommand';

export default class OutlineButtonsEditing extends Plugin {
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
   * Registers Outline button as an element in the DOM converter.
   *
   * @private
   */
  _defineSchema() {
    const schema = this.editor.model.schema;
    schema.register('buttonsOutlineAnchor', {
      allowWhere: '$inlineObject',
      isInline: true,
      allowAttributes: ['class', 'href', 'target', 'icon', 'aria-label', 'data-entity-type', 'data-entity-uuid'],
      isContent: true
    });

  }

  /**
   * Defines handling of Outline button element in the content.
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
        model: 'buttonsOutlineAnchor',
        view: 'a'
      });
    conversion.for('upcast')
      .elementToElement({
        view: {
          name: 'a',
          classes: 'buttons-anchor',
        },
        model: 'buttonsOutlineAnchor'
      });

  }

  /**
   * Defines the outline button and material icon insert command.
   *
   * @private
   */
  _defineCommands() {
    const editor = this.editor;
    editor.commands.add(
      'insertOutlineButtons',
      new InsertMaterialIconsCommand(this.editor),
    );
  }

}
