/**
 * @file
 * Defines the behavior of the microcontent entity browser view.
 */

(function ($) {
  /**
   * Update the class of a row based on the status of a checkbox.
   *
   * @param {object} $row
   * @param {object} $input
   */
  function updateClasses($row, $input) {
    $row[$input.prop('checked') ? 'addClass' : 'removeClass']('checked');
  }

  /**
   * Attaches the behavior of the microcontent entity browser view.
   */
  Drupal.behaviors.microContentEntityBrowserView = {
    attach(context, settings) {
      // Run through each row to add the default classes.
      $('.views-row', context).each(function () {
        const $row = $(this);
        const $input = $row.find('.views-field-entity-browser-select input');
        updateClasses($row, $input);
      });

      // Add a checked class when clicked.
      const $elements = $(
        once('microcontent-views-row', '.views-row', context),
      );
      $elements.click(function () {
        const $row = $(this);
        const $input = $row.find('.views-field-entity-browser-select input');
        $input.prop('checked', !$input.prop('checked'));
        updateClasses($row, $input);
      });
    },
  };
})(jQuery, Drupal);
