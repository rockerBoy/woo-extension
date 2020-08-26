/*global ajaxurl, ewoo_product_import_params */
;(function ( $, window ) {

    /**
     * productImportForm handles the import process.
     */
    let productImportForm = function( $form ) {
        this.$form           = $form;
        this.xhr             = false;
        this.mapping         = ewoo_product_import_params.mapping;
        this.position        = 0;
        this.file            = ewoo_product_import_params.file;
        this.update_existing = ewoo_product_import_params.update_existing;
        this.security        = ewoo_product_import_params.import_nonce;

        // Number of import successes/failures.
        this.imported = 0;
        this.failed   = 0;
        this.updated  = 0;
        this.skipped  = 0;

        // Initial state.
        this.$form.find('.woocommerce-importer-progress').val( 0 );

        this.run_import = this.run_import.bind( this );

        // Start importing.
        this.run_import();
    };

    /**
     * Run the import in batches until finished.
     */
    productImportForm.prototype.run_import = function() {
        let $this = this;

        $.ajax( {
            type: 'POST',
            url: ajaxurl,
            data: {
                action          : 'ext_do_ajax_product_import',
                position        : $this.position,
                mapping         : $this.mapping,
                file            : $this.file,
                update_existing : $this.update_existing,
                security        : $this.security
            },
            dataType: 'json',
            success: function( response ) {
                if ( response.success ) {
                    $this.position  = response.data.position;
                    $this.imported += response.data.imported;
                    $this.failed   += response.data.failed;
                    $this.updated  += response.data.updated;
                    $this.skipped  += response.data.skipped;
                    $this.$form.find('.woocommerce-importer-progress').val( response.data.percentage );

                    if ( 'done' === response.data.position ) {
                        var file_name = ewoo_product_import_params.file.split( '/' ).pop();
                        window.location = response.data.url +
                            '&products-imported=' +
                            parseInt( $this.imported, 10 ) +
                            '&products-failed=' +
                            parseInt( $this.failed, 10 ) +
                            '&products-updated=' +
                            parseInt( $this.updated, 10 ) +
                            '&products-skipped=' +
                            parseInt( $this.skipped, 10 ) +
                            '&file-name=' +
                            file_name;
                    } else {
                        $this.run_import();
                    }
                }
            }
        } ).fail( function( response ) {
            window.console.log( response );
        } );
    };

    /**
     * Function to call productImportForm on jQuery selector.
     */
    $.fn.wc_product_importer = function() {
        new productImportForm( this );
        return this;
    };

    $( '.woocommerce-importer' ).wc_product_importer();

})( jQuery, window );
