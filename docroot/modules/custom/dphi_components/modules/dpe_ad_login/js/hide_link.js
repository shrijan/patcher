(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.hidebehavior = {
    attach: function () {
      var usercheck = drupalSettings.authenticatedusercheck.authenticateduser;
      if(usercheck=="hide"){
        //$('#toolbar-item-user-tray ul.toolbar-menu li.account-edit').hide();
        $('#toolbar-item-user-tray a[title="Edit user account"]').hide();
      }
    }
  }
})(jQuery, Drupal, drupalSettings);
