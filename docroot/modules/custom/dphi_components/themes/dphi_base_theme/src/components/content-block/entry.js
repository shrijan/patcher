import './content-block.scss'

Drupal.behaviors.contentListing = {
  attach: function (context, settings) {
    jQuery(context).bind('cbox_complete', function () {
      // Only run if there is a title.
      if (jQuery('#cboxTitle:empty', context).length == false) {
        jQuery('#cboxLoadedContent img', context).unbind()
        jQuery('#cboxOverlay', context).unbind()
      }
    })
  }
}
