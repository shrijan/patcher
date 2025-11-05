(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.hidebehavior = {
    attach: function () {
      var queryString = window.location.search;

      // Parse the query string to extract query parameters.
      var queryParams = new URLSearchParams(queryString);

      // Get the value of the 'destination' query parameter.
      var destination = queryParams.get('destination');
      console.log(destination);
       if (destination) {
        // Update the anchor links with the 'destination' parameter value.
        $('a[data-drupal-link-system-path]').each(function () {
          var currentHref = $(this).attr('href');
          var updatedHref = currentHref + '?destination=' + encodeURIComponent(destination);
          $(this).attr('href', updatedHref);
        });
       }

    }
  }
})(jQuery, Drupal, drupalSettings);
