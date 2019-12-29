/* global wc_fedapay_params jQuery FedaPay */

jQuery( function( $ ) {
  'use strict';

  var wc_fedapay_checkout = {
    init: function() {
      window.addEventListener( 'hashchange', this.onHashChange );
    },

    onHashChange: function() {
      var partials = window.location.hash.match( /^#?fedapay-confirm-(\d+):(.+)$/ );

      if ( ! partials || 3 > partials.length ) {
        return;
      }

      var id          = partials[1];
      var redirectURL = decodeURIComponent( partials[2] );

      // Cleanup the URL
      window.location.hash = '';

      wc_fedapay_checkout.openDialog( id, redirectURL );
    },

    openDialog: function(id, redirectURL) {
      wc_fedapay_params['transaction'] = { id: id };
      wc_fedapay_params['onComplete'] = function() {
        window.location = redirectURL;
      };

      FedaPay.init(wc_fedapay_params).open();
    }
  };

  wc_fedapay_checkout.init();
});
