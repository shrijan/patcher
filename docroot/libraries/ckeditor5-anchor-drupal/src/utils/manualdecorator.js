/**
 * @license Copyright (c) 2003-2020, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

/**
 * @module anchor/utils
 */

import { ObservableMixin } from 'ckeditor5/src/utils';
import { mix } from 'ckeditor5/src/utils';

/**
 * Helper class that stores manual decorators with observable {@link module:anchor/utils~ManualDecorator#value}
 * to support integration with the UI state. An instance of this class is a model with the state of individual manual decorators.
 * These decorators are kept as collections in {@link module:anchor/anchorcommand~AnchorCommand#manualDecorators}.
 *
 * @mixes module:utils/observablemixin~ObservableMixin
 */
export default class ManualDecorator {
	/**
	 * Creates a new instance of {@link module:anchor/utils~ManualDecorator}.
	 *
	 * @param {Object} config
	 * @param {String} config.id The name of the attribute used in the model that represents a given manual decorator.
	 * For example: `'anchorIsExternal'`.
	 * @param {String} config.label The label used in the user interface to toggle the manual decorator.
	 * @param {Object} config.attributes A set of attributes added to output data when the decorator is active for a specific anchor.
	 * Attributes should keep the format of attributes defined in {@link module:engine/view/elementdefinition~ElementDefinition}.
	 * @param {Boolean} [config.defaultValue] Controls whether the decorator is "on" by default.
	 */
	constructor( { id, label, attributes, defaultValue } ) {
		/**
		 * An ID of a manual decorator which is the name of the attribute in the model, for example: 'anchorManualDecorator0'.
		 *
		 * @type {String}
		 */
		this.id = id;

		/**
		 * The value of the current manual decorator. It reflects its state from the UI.
		 *
		 * @observable
		 * @member {Boolean} module:anchor/utils~ManualDecorator#value
		 */
		this.set( 'value' );

		/**
		 * The default value of manual decorator.
		 *
		 * @type {Boolean}
		 */
		this.defaultValue = defaultValue;

		/**
		 * The label used in the user interface to toggle the manual decorator.
		 *
		 * @type {String}
		 */
		this.label = label;

		/**
		 * A set of attributes added to downcasted data when the decorator is activated for a specific anchor.
		 * Attributes should be added in a form of attributes defined in {@link module:engine/view/elementdefinition~ElementDefinition}.
		 *
		 * @type {Object}
		 */
		this.attributes = attributes;
	}
}

mix( ManualDecorator, ObservableMixin );
