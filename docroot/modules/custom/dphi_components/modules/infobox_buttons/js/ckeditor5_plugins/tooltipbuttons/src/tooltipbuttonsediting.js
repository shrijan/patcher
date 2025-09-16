import {Plugin} from 'ckeditor5/src/core';
import {toWidget,Widget, viewToModelPositionOutsideModelElement} from 'ckeditor5/src/widget';
import { ClickObserver, Position } from 'ckeditor5/src/engine';
import { Notification, ContextualBalloon} from 'ckeditor5/src/ui';
import InsertMaterialIconsCommand from './inserttooltipbuttonscommand';
export default class TooltipButtonsEditing extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [Widget, Notification, ContextualBalloon];
  }
  /**
   * @inheritdoc
   */
  init() {

    const editor = this.editor;



    editor.editing.view.addObserver(ClickObserver);
    this.listenTo(editor.editing.view.document, 'click', (evt, data) => {

      const domTarget = data.domTarget;
      if (domTarget && (domTarget.classList.contains('nsw-toggletip') || domTarget.classList.contains('nsw-tooltip')  || domTarget.classList.contains('nsw-toggletip__element'))) {
          const viewElement=data.target;
          this._openEditDialog(viewElement, domTarget);
      }
    });
    this._defineSchema();
    this._defineConverters();
    this._defineCommands();
    const model = this.editor.model;

    // Function to check if an element has either 'js-toggletip' or 'nsw-toggletip__element' class
    function isTooltipElement(viewElement) {
        return viewElement.hasClass('js-toggletip') || viewElement.hasClass('nsw-toggletip__element');
    }

    this.editor.editing.mapper.on(
        'viewToModelPosition', viewToModelPositionOutsideModelElement(model, isTooltipElement)
    );
  }

  /**
   * Registers Outline button as an element in the DOM converter.
   *
   * @private
   */
  _defineSchema() {
    const schema = this.editor.model.schema;
    schema.register('buttonsTooltipSpan', {
      allowWhere: '$inlineObject',
      isInline: true,
      allowAttributes: ['class','tooltip-ref', 'title', 'data-theme', 'aria-controls', 'data-title', 'toggle'],
      isContent: true
    });
    schema.register('buttonsToogleTooltipSpan', {
      allowWhere: '$inlineObject',
      isInline: true,
      allowAttributes: ['class',  'data-theme', 'aria-controls',  'data-title', 'tooltip-ref', 'title', 'toggle'],
      isContent: true
    });
    schema.register('tooltipDiv', {
      allowWhere: '$block',
      isBlock: true,
      allowAttributes: ['class', 'id', 'tooltip-ref'],
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

    // Editing Downcast and Data Downcast for 'buttonsTooltipSpan'

    conversion.for('editingDowncast')
      .elementToElement({
          model: 'buttonsTooltipSpan',
          view: (modelElement, { writer:viewWriter }) => {
            const span = createSpanToolTipView(modelElement, viewWriter);
            return toWidget( span, viewWriter );
          }
      });


    conversion.for('dataDowncast')
    .elementToElement({
        model: 'buttonsTooltipSpan',
        view: (modelElement, { writer:viewWriter }) => createSpanToolTipView(modelElement, viewWriter)
    });

    function createSpanToolTipView(modelElement, writer) {
      const span = writer.createContainerElement('span', {
        class: modelElement.getAttribute('class'),
        'tooltip-ref': modelElement.getAttribute('tooltip-ref'),
        'data-theme': modelElement.getAttribute('data-theme'),
        'data-title': modelElement.getAttribute('data-title'),
        'aria-controls': modelElement.getAttribute('aria-controls'),
        'toggle': modelElement.getAttribute('toggle'),
         title: modelElement.getAttribute('title')
      });
      const tiptext = modelElement.getAttribute('tooltip-ref');
      const textNode = writer.createText(tiptext);
      writer.insert(writer.createPositionAt(span,0), textNode)
      return span;
    }
    conversion.for('upcast')
      .elementToElement({
        view: {
          name: 'span',
          classes: 'nsw-tooltip'
        },
        model: (viewElement, { writer:modelWriter }) => {
          const attributes = {
              class: viewElement.getAttribute('class'),
              'tooltip-ref': viewElement.getAttribute('tooltip-ref'),
              'data-theme': viewElement.getAttribute('data-theme'),
              'data-title': viewElement.getAttribute('data-title'),
              'toggle': viewElement.getAttribute('toggle'),
              'aria-controls': viewElement.getAttribute('aria-controls'),
              title: viewElement.getAttribute('title')
          };

          return modelWriter.createElement('buttonsTooltipSpan',attributes);
      }
    });

    //Toogle tip Conversion
    conversion.for('editingDowncast')
      .elementToElement({
          model: 'buttonsToogleTooltipSpan',
          view: (modelElement, { writer:viewWriter }) => {
            const span = createSpanToogleTipView(modelElement, viewWriter);
            return toWidget( span, viewWriter );
          }
      });


    conversion.for('dataDowncast')
    .elementToElement({
        model: 'buttonsToogleTooltipSpan',
        view: (modelElement, { writer:viewWriter }) => createSpanToogleTipView(modelElement, viewWriter)
    });

    function createSpanToogleTipView(modelElement, writer) {
      const attributes = {
        class: modelElement.getAttribute('class'),
        'tooltip-ref': modelElement.getAttribute('tooltip-ref'),
        'data-theme': modelElement.getAttribute('data-theme'),
        'data-title': modelElement.getAttribute('data-title'),
        'aria-controls': modelElement.getAttribute('aria-controls'),
        'toggle': modelElement.getAttribute('toggle')
      }
      const title = modelElement.getAttribute('title')
      if (title) {
        attributes.title = title
      }
      const toogle_span = writer.createContainerElement('span', attributes);

      const tiptext = modelElement.getAttribute('tooltip-ref');
      const textNode = writer.createText(tiptext);
      writer.insert(writer.createPositionAt(toogle_span,0), textNode)
      return toogle_span;
    }
    conversion.for('upcast')
      .elementToElement({
        view: {
          name: 'span',
          classes: 'nsw-toggletip'
        },
        model: (viewElement, { writer:modelWriter }) => {
          const attributes_toogle = {
              class: viewElement.getAttribute('class'),
              title: viewElement.getAttribute('title'),
              'tooltip-ref': viewElement.getAttribute('tooltip-ref'),
              'data-theme': viewElement.getAttribute('data-theme'),
              'data-title': viewElement.getAttribute('data-title'),
              'aria-controls': viewElement.getAttribute('aria-controls'),
              'toggle': viewElement.getAttribute('toggle'),
          };
          return modelWriter.createElement('buttonsToogleTooltipSpan',attributes_toogle);
      }
    });

    //Toogle tooltip div conversion
    conversion.for('upcast').elementToElement({
        view: {
            name: 'div',
            classes: 'tooltip-div',
        },
        model: (viewElement, { writer }) => {
          const attributes = {
              class: viewElement.getAttribute('class'),
              id: viewElement.getAttribute('id'),
              'tooltip-ref': viewElement.getAttribute('tooltip-ref'),
          };
          return writer.createElement('tooltipDiv', attributes);
        }
    });



    //Toogle tip Conversion
    conversion.for('downcast')
      .elementToElement({
          model: 'tooltipDiv',
          view: (modelElement, { writer:viewWriter }) => {
            const div = createDivToogleTipView(modelElement, viewWriter);
            return toWidget( div, viewWriter );
          }
      });

    /*
    conversion.for('dataDowncast')
    .elementToElement({
        model: 'tooltipDiv',
        view: (modelElement, { writer:viewWriter }) => createDivToogleTipView(modelElement, viewWriter)
    });*/

    function createDivToogleTipView(modelElement, writer) {
      const div  = writer.createContainerElement('div', {
        class: modelElement.getAttribute('class'),
        id: modelElement.getAttribute('id'),
        'tooltip-ref': modelElement.getAttribute('tooltip-ref')
      });


      const textNode = writer.createText(modelElement.getAttribute('tooltip-ref'));
      console.log('textNode',textNode, modelElement.getAttribute('tooltip-ref'))

      writer.insert(writer.createPositionAt(div,0), textNode)
      return div;
    }
  }


  //block view conversion


  /**
   * Defines the outline button and material icon insert command.
   *
   * @private
   */
  _defineCommands() {
    const editor = this.editor;
    editor.commands.add(
      'insertTooltipButtons',
      new InsertMaterialIconsCommand(this.editor),
    );
  }

  _openEditDialog(viewElement, domTarget) {
        const editor = this.editor;
        const model = editor.model;
        const options = this.editor.config.get('tooltipbuttons');
        const openDialog = options.openDialog;
        const  dialogSettings = options.dialogSettings;
        console.log('domTarget', domTarget)

        // Get the current tooltip text from the view element
        const tooltipText = domTarget.getAttribute('data-title');
        const tooltipTitle = domTarget.getAttribute('title');
        const tooltipTheme = domTarget.getAttribute('data-theme');
        const tooltipToggle = domTarget.getAttribute('toggle');
        const ariaControls = domTarget.getAttribute('aria-controls');
        const tooltipref = domTarget.getAttribute('tooltip-ref')
        console.log('tooltipTitle',tooltipTitle, tooltipToggle)

        let tooltipContent = tooltipText;

        /*
        if (ariaControls) {
            const rootElement = editor.editing.view.document.getRoot();

            for (const child of rootElement.getChildren()) {

                if (child.getAttribute('id') === ariaControls) {

                    tooltipContent = document.getElementById(ariaControls).innerHTML;

                    break;
                }
            }
        }*/
        console.log('tooltipContent',tooltipContent)

        openDialog(
            Drupal.url('tooltip_buttons/dialog')+'?tooltipTheme=' + encodeURIComponent(tooltipTheme) + '&tooltipToggle=' + encodeURIComponent(tooltipToggle) + '&tooltipContent=' + encodeURIComponent(tooltipContent),
            ({ settings }) => {

                model.change(writer => {

                  const modelElement = editor.editing.mapper.toModelElement(viewElement);
                  const viewRoot = editor.editing.view.document.getRoot();
                  const viewDivElement = this.findViewElementById(viewRoot, ariaControls);
                  const toggle_status = settings.tooltip_toggle?'true':'false'
                  const plain_tooltip = settings.tooltip_text

                  if (modelElement) {
                      writer.setAttribute('title', plain_tooltip, modelElement);
                      writer.setAttribute('data-title',plain_tooltip, modelElement);
                      writer.setAttribute('data-theme', settings.theme, modelElement);
                      writer.setAttribute('toggle', toggle_status, modelElement);
                      writer.setAttribute('aria-control', domTarget.getAttribute('aria-controls'), modelElement);
                      let currentClasses = modelElement.getAttribute('class') || '';
                      currentClasses = currentClasses.split(' ');

                      if (settings.tooltip_toggle) {
                        currentClasses = currentClasses.filter(cls => cls !== 'nsw-tooltip' && cls !== 'js-tooltip');
                        if (!currentClasses.includes('nsw-toggletip')) {
                            currentClasses.push('nsw-toggletip');
                        }
                        if (!currentClasses.includes('js-toggletip')) {
                            currentClasses.push('js-toggletip');
                        }
                      } else {
                        currentClasses = currentClasses.filter(cls => cls !== 'nsw-toggletip' && cls !== 'js-toggletip');
                        if (!currentClasses.includes('nsw-tooltip')) {
                            currentClasses.push('nsw-tooltip');
                        }
                        if (!currentClasses.includes('js-tooltip')) {
                            currentClasses.push('js-tooltip');
                        }
                      }
                      writer.setAttribute('class', currentClasses.join(' '), modelElement);

                      const root = model.document.getRoot();
                      for (const child of root.getChildren()) {
                          let data;
                          if (child.is('element', 'tooltipDiv')) {
                              data = {
                                  class: child.getAttribute('class'),
                                  id: child.getAttribute('id')
                              };
                          } else if (child.is('element', 'htmlDivParagraph')) {
                              const htmlDivAttributes = child.getAttribute('htmlDivAttributes');
                              data = {
                                  class: htmlDivAttributes.classes?.join(' '),
                                  id: htmlDivAttributes?.attributes.id
                              };
                          }
                          if (data?.id === ariaControls) {
                              writer.remove(child);
                              writer.appendElement('tooltipDiv', {
                                class: data.class,
                                id: ariaControls,
                                'tooltip-ref': plain_tooltip
                              }, root);
                              break;
                          }
                      }

                      editor.editing.view.change(viewWriter => {
                        viewWriter.setAttribute('tooltip-ref', tooltipref, viewElement);
                        viewWriter.setAttribute('data-title', plain_tooltip, viewElement);
                        viewWriter.setAttribute('data-theme', settings.theme, viewElement);
                        viewWriter.setAttribute('toggle', toggle_status, viewElement);
                        viewWriter.setAttribute('aria-control', domTarget.getAttribute('aria-controls'), viewElement);
                        const range = viewWriter.createRangeIn(viewElement);
                        for (const item of range.getItems()) {
                            viewWriter.remove(item);
                        }
                        const textNode = viewWriter.createText(tooltipref);
                        viewWriter.insert(viewWriter.createPositionAt(viewElement, 0), textNode);
                        if (toggle_status == 'true') {
                          const viewRoot = editor.editing.view.document.getRoot();
                          const viewDivElement = this.findViewElementById(viewRoot, ariaControls);

                          if (viewDivElement) {

                              const modelDivElement = editor.editing.mapper.toModelElement(viewDivElement);
                              //viewWriter.setAttribute('tooltip-ref', tooltipref, viewDivElement);
                              console.log('Div container', viewRoot,viewElement, viewDivElement, modelDivElement)
                              /*editor.editing.view.change(viewWriter => {
                                  // Remove all existing children

                                  viewWriter.setAttribute('tooltip-ref', plain_tooltip, viewDivElement);

                                  const range = viewWriter.createRangeIn(viewDivElement);
                                  for (const item of range.getItems()) {
                                      viewWriter.remove(item);
                                  }
                                  console.log('view change',viewDivElement)

                                  // Insert the new text node
                                  const textNode = viewWriter.createText(plain_tooltip);
                                  viewWriter.insert(viewWriter.createPositionAt(viewDivElement, 0), textNode);






                              });*/

                          }else{
                            console.log('no div container');
                          }

                        }
                      })


                  }
              });

                /*this.editor.editing.view.change(viewWriter => {
                  // Update view attributes
                  const plain_tooltip = settings.tooltip_text

                  const range = viewWriter.createRangeIn(viewElement);
                  for (const item of range.getItems()) {
                      viewWriter.remove(item);
                  }
                  const textNode = viewWriter.createText(domTarget.getAttribute('tooltip-ref'));
                  viewWriter.insert(viewWriter.createPositionAt(viewElement, 0), textNode);
                  if (ariaControls) {

                      const viewRoot = this.editor.editing.view.document.getRoot();
                      const viewDivElement = this.findViewElementById(viewRoot, ariaControls);
                      console.log('viewDivElement',settings.tooltip_text)
                      if (viewDivElement) {



                          editor.editing.view.change(vWriter => {
                                // Remove all existing children

                                vWriter.setAttribute('tooltip-ref', plain_tooltip, viewDivElement);

                                const range = vWriter.createRangeIn(viewDivElement);
                                for (const item of range.getItems()) {
                                    vWriter.remove(item);
                                }
                                //console.log('view change',viewDivElement)

                                // Insert the new text node
                                const textNode = vWriter.createText(plain_tooltip);
                                vWriter.insert(vWriter.createPositionAt(viewDivElement, 0), textNode);
                            });
                            editor.model.change(modelWriter => {
                                const modelDivElement = editor.editing.mapper.toModelElement(viewDivElement);
                                //console.log('Model change',modelDivElement)
                                if (modelDivElement) {
                                    const htmlDivAttributesArray = modelDivElement.getAttribute('htmlDivAttributes') || [];

                                    modelWriter.setAttribute('tooltip-ref', plain_tooltip, modelDivElement);
                                    //modelWriter.setAttribute('htmlDivAttributes', htmlDivAttributesArray, modelDivElement);
                                }
                            });
                      }

                    }
              });*/


            },
            dialogSettings,
        );

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
  _removeTooltip(viewElement) {
        const model = this.editor.model;
        const schema = model.schema;
        const view = this.editor.editing.view;

        model.change(writer => {
            // Get the model position of the clicked view element.
            const modelElement = this.editor.editing.mapper.toModelElement(viewElement);
            if ((modelElement && modelElement.is('element', 'buttonsTooltipSpan')) || (modelElement && modelElement.is('element', 'buttonsToogleTooltipSpan'))) {
               const ariaControls = modelElement.getAttribute('aria-controls');
               const root = model.document.getRoot();
                let divToRemove = null;
                for (const child of root.getChildren()) {
                    if (child.is('element', 'tooltipDiv') && child.getAttribute('id') === ariaControls) {
                        divToRemove = child;
                        break;
                    }
                }
                if (divToRemove) {
                  writer.remove(divToRemove);
                }
            }
            const range = writer.createRangeOn(modelElement);

            // Extract the text content from the model element.
            const text = Array.from(range.getItems())
                .map(item => item.data)
                .join('');

            // Remove the tooltip element and insert plain text.
            writer.insertText(text, range.start);

        });
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
  findViewElementById(viewRoot, id) {
     for (const child of viewRoot.getChildren()) {
      if (child.is('element') && child.getAttribute('id') === id) {
        return child;
      } else if (child.is('element')) {
        const foundChild = this.findViewElementById(child, id);
        if (foundChild) {
          return foundChild;
        }
      }
    }
    return null;
  }
}
