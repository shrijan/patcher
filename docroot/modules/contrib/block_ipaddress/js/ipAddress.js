/**
 * @file
 * Provides JavaScript additions to the managed Ip Address field.
 */

(function (Drupal, $, once) {

  'use strict';

  Drupal.behaviors.blockSettingsSummaryIpAddress = {
    attach: function (context) {
      // Ensure drupalSetSummary exists.
      if (typeof $.fn.drupalSetSummary === 'undefined') {
        return;
      }

      $(once('block-settings-summary-ipaddress', '[data-drupal-selector="edit-visibility-ipaddress"]', context))
        .each(function () {
          $(this).drupalSetSummary(function (context) {
            var $pages = $(context).find('textarea[name="visibility[ipaddress][ipaddress]"]');

            if (!$pages.val()) {
              return Drupal.t('Not restricted');
            }

            return Drupal.t('Restricted to certain IP Address');
          });
        });
    }
  };

})(Drupal, jQuery, once);