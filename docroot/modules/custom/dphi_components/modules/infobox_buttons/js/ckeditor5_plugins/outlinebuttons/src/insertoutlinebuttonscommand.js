/**
 * @file defines InsertMaterialIconsCommand, which is executed when the icon
 * toolbar button is pressed.
 */
// cSpell:ignore simpleboxediting

import { Command } from 'ckeditor5/src/core';

export default class InsertOutlineButtonsCommand extends Command {
  execute(settings) {
    this.editor.model.change((writer) => {

      let classes = 'nsw-button nsw-button--dark-outline ';
      let target = '_self';
      let position = 'before';

      if (settings.target !== '') {
        target = settings.target;
      }

      if(settings.button_type != 'before'){
        position = 'after';
        classes += 'nsw-btn-ck5-after'
       } else {
        classes += 'nsw-btn-ck5-before'
      }
      if(settings.entity_type === 'linky') {
        classes += ' ext'
      }
      const attributes = {
        class: classes,
        href: settings.button_link,
        'data-entity-type': settings.entity_type,
        'data-entity-uuid': settings.entity_uuid,
        target: target,
        icon: settings.icon,
        'aria-label': settings.button_text
      };

      const buttonsOutlineAnchor = writer.createElement('buttonsOutlineAnchor', attributes);

      writer.append(settings.button_text, buttonsOutlineAnchor);

      const docFrag = writer.createDocumentFragment();
      writer.append(buttonsOutlineAnchor, docFrag);
      this.editor.model.insertContent(docFrag);
    });
  }

  refresh() {
    const model = this.editor.model;
    const selection = model.document.selection;
    const allowedIn = model.schema.findAllowedParent(
      selection.getFirstPosition(),
      'buttonsOutlineAnchor',
    );
    this.isEnabled = allowedIn !== null;
  }
}
