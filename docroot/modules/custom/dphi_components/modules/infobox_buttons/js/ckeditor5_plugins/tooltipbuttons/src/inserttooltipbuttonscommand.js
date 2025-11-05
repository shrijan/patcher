/**
 * @file defines InsertMaterialIconsCommand, which is executed when the icon
 * toolbar button is pressed.
 */
// cSpell:ignore simpleboxediting

import { Command } from 'ckeditor5/src/core';

export default class InsertTooltipButtonsCommand extends Command {
  execute(settings) {
    const model = this.editor.model;
    const selection = model.document.selection;
    this.editor.model.change((writer) => {

      const range = selection.getFirstRange();
      const tooltip = this.extractTooltipFromSelection(selection);
      const title = settings.tooltip_text;
      console.log('settings tooltip',settings.tooltip_toggle)
      const classes = settings.tooltip_toggle?'nsw-toggletip js-toggletip':'nsw-tooltip js-tooltip';
      const theme = settings.theme;
      const toogle_status = settings.tooltip_toggle?'true':'false';
      const  attributes = {
          class: classes,
          'data-theme': theme,
          'aria-controls': settings.id,
          'data-title': title,
          'tooltip-ref': tooltip,
          'title': settings.tooltip_toggle ? undefined : title,
          'toggle': toogle_status
        };


      const attributes_div = {
        'id': settings.id,
        'class': 'nsw-toggletip__element nsw-toggletip__element--'+theme,
        'tooltip-ref': title
      }


      const docFrag = writer.createDocumentFragment();



      const textnode = writer.createText(tooltip);
      let buttonsTooltipSpan;
      if(settings.tooltip_toggle){
        buttonsTooltipSpan = writer.createElement('buttonsToogleTooltipSpan', attributes);
      }else{
        buttonsTooltipSpan = writer.createElement('buttonsTooltipSpan', attributes);
      }

      writer.append(buttonsTooltipSpan, docFrag);
      model.insertContent(docFrag, range)



        const toogle_tip = writer.createElement('tooltipDiv', attributes_div);

        // Parse HTML content and insert as children of the tooltipDiv
        //const htmlContent = settings.tooltip_text.value;
        //const viewFragment = this.editor.data.processor.toView(htmlContent);
        //const modelFragment = this.editor.data.toModel(viewFragment);

        // Append the model fragment to the tooltipDiv
        //for (const item of modelFragment.getChildren()) {
         //writer.append(item, toogle_tip);
        //}

        const root = model.document.getRoot();
        const endPosition = model.createPositionAt(root, 'end');
        //writer.append(toogle_tip,docFragdiv)
        model.insertContent(toogle_tip, endPosition);



    });


  }

  refresh() {
    const model = this.editor.model;
    const selection = model.document.selection;
    const firstPosition = selection.getFirstPosition();
    //this.isEnabled =model.schema.checkChild(parentElement, 'buttonsTooltipSpan')

    const allowedInButtonsTooltipSpan = model.schema.findAllowedParent(firstPosition, 'buttonsTooltipSpan');
    const allowedInButtonsToggleTooltipSpan = model.schema.findAllowedParent(firstPosition, 'buttonsToogleTooltipSpan');
    const allowedInDiv = model.schema.findAllowedParent(firstPosition, 'tooltipDiv');
    if (allowedInButtonsTooltipSpan || allowedInButtonsToggleTooltipSpan || allowedInDiv) {
      // Handle your logic here for either allowed element
      if (allowedInButtonsTooltipSpan) {
        // Logic for buttonsTooltipSpan
        this.isEnabled = allowedInButtonsTooltipSpan !== null;
      }

      if (allowedInButtonsToggleTooltipSpan) {
        // Logic for buttonsToogleTooltipSpan
        this.isEnabled = allowedInButtonsToggleTooltipSpan !== null;
      }
      if (allowedInDiv) {
        // Logic for buttonsToogleTooltipSpan
        this.isEnabled = allowedInDiv !== null;
      }
    } else {
      console.error('Neither buttonsTooltipSpan nor buttonsToogleTooltipSpan are allowed at this position.');
    }

  }

  extractPlainText(html) {
    const tempElement = document.createElement('div');
    tempElement.innerHTML = html;
    return tempElement.textContent || tempElement.innerText || '';
  }
  _updateTooltipContent(model, writer, range, tooltip) {
    const text = writer.createText(tooltip, { 'data-tooltip': tooltip });
    return model.insertContent(text, range);
  }

  extractTooltipFromSelection(selection) {
    if (selection.isCollapsed) {
      const firstPosition = selection.getFirstPosition();

      if (!firstPosition) {
        return null;
      }

      return firstPosition.textNode && firstPosition.textNode.data;
    }

    const firstRange = selection.getFirstRange();

    if (!firstRange) {
      return null;
    }

    const rangeItems = Array.from(selection.getFirstRange().getItems());

    if (rangeItems.length > 1) {
      return null;
    }

    const firstNode = rangeItems[0];

    if (firstNode.is('$text') || firstNode.is('$textProxy')) {
      return firstNode.data;
    }

    return null;
  }
}
