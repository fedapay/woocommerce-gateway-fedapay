/* global jQuery, wp */

jQuery( function( $ ) {
  'use strict';

  var wc_fedapay_admin = {
    frames: [],
    init: function() {
      $( 'button.wc_fedapay_gateway_image_upload' )
        .on( 'click', this.onClickUploadButton );

      $( 'button.wc_fedapay_gateway_image_remove' )
        .on( 'click', this.removeProductImage );

      $( document.body ).on( 'change', '#woocommerce_woo_gateway_fedapay_testmode', function() {
        var test_secret_key = $( '#woocommerce_woo_gateway_fedapay_fedapay_testsecretkey' ).parents( 'tr' ).eq( 0 ),
          test_public_key = $( '#woocommerce_woo_gateway_fedapay_fedapay_testpublickey' ).parents( 'tr' ).eq( 0 ),
          live_secret_key = $( '#woocommerce_woo_gateway_fedapay_fedapay_livesecretkey' ).parents( 'tr' ).eq( 0 ),
          live_public_key = $( '#woocommerce_woo_gateway_fedapay_fedapay_livepublickey' ).parents( 'tr' ).eq( 0 );

        if ( $( this ).is( ':checked' ) ) {
          test_secret_key.show();
          test_public_key.show();
          live_secret_key.hide();
          live_public_key.hide();
        } else {
          test_secret_key.hide();
          test_public_key.hide();
          live_secret_key.show();
          live_public_key.show();
        }
      } );

      $( '#woocommerce_woo_gateway_fedapay_testmode' ).change();
    },

    onClickUploadButton: function( event ) {
      event.preventDefault();

      var data = $( event.target ).data();

      // If the media frame already exists, reopen it.
      if ( 'undefined' !== typeof wc_fedapay_admin.frames[ data.fieldId ] ) {
        // Open frame.
        wc_fedapay_admin.frames[ data.fieldId ].open();
        return false;
      }

      // Create the media frame.
      wc_fedapay_admin.frames[ data.fieldId ] = wp.media( {
        title: data.mediaFrameTitle,
        button: {
          text: data.mediaFrameButton
        },
        multiple: false // Set to true to allow multiple files to be selected
      } );

      // When an image is selected, run a callback.
      var context = {
        fieldId: data.fieldId,
      };

      wc_fedapay_admin.frames[ data.fieldId ]
        .on( 'select', wc_fedapay_admin.onSelectAttachment, context );

      // Finally, open the modal.
      wc_fedapay_admin.frames[ data.fieldId ].open();
    },

    onSelectAttachment: function() {
      // We set multiple to false so only get one image from the uploader.
      var attachment = wc_fedapay_admin.frames[ this.fieldId ]
        .state()
        .get( 'selection' )
        .first()
        .toJSON();

      var $field = $( '#' + this.fieldId );
      var $img = $( '<img />' )
        .attr( 'src', getAttachmentUrl( attachment ) );

      $field.siblings( '.image-preview-wrapper' )
        .html( $img );

      $field.val( attachment.id );
      $field.siblings( 'button.wc_fedapay_gateway_image_remove' ).show();
      $field.siblings( 'button.wc_fedapay_gateway_image_upload' ).hide();
    },

    removeProductImage: function( event ) {
      event.preventDefault();
      var $button = $( event.target );
      var data = $button.data();
      var $field = $( '#' + data.fieldId );

      //update fields
      $field.val( '' );
      $field.siblings( '.image-preview-wrapper' ).html( ' ' );
      $button.hide();
      $field.siblings( 'button.wc_fedapay_gateway_image_upload' ).show();
    },
  };

  function getAttachmentUrl( attachment ) {
    if ( attachment.sizes && attachment.sizes.medium ) {
      return attachment.sizes.medium.url;
    }
    if ( attachment.sizes && attachment.sizes.thumbnail ) {
      return attachment.sizes.thumbnail.url;
    }
    return attachment.url;
  }

  wc_fedapay_admin.init();
});
