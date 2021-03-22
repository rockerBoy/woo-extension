/*global ajaxurl, ewoo-product-export */
;(function ( $, window ) {
    /**
     * productExportForm handles the export process.
     */
    let productExportForm = function ( $form ) {
        this.$form = $form;
        this.xhr   = false;

        // Initial state.
        this.$form.find('.woocommerce-exporter-progress').val(0);

        // Methods.
        this.processStep = this.processStep.bind(this);

        // Events.
        $form.on('submit', { productExportForm: this }, this.onSubmit);
        $form.find('.woocommerce-exporter-types').on('change', { productExportForm: this }, this.exportTypeFields);
    };

    /**
     * Handle export form submission.
     */
    productExportForm.prototype.onSubmit = function ( event ) {
        event.preventDefault();

        event.data.productExportForm.$form.addClass('woocommerce-exporter__exporting');
        event.data.productExportForm.$form.find('.woocommerce-exporter-progress').val(0);
        event.data.productExportForm.$form.find('.woocommerce-exporter-button').prop('disabled', true);
        event.data.productExportForm.processStep(1, $(this).serialize(), '');
    };

    /**
     * Process the current export step.
     */
    productExportForm.prototype.processStep = function ( step, data, columns, filename ) {
        var $this         = this,
            selected_columns = $('.woocommerce-exporter-columns').val(),
            export_all       = $('#woocommerce-exporter-all-products:checked').length ? 1: 0,
            export_without_images       = $('#woocommerce_exporter_products_without_image:checked').length ? 1: 0,
            export_types     = $('.woocommerce-exporter-types').val(),
            export_category  = $('.woocommerce-exporter-category').val();

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                form             : data,
                action           : 'ext_do_ajax_product_export',
                step             : step,
                columns          : columns,
                selected_columns : selected_columns,
                export_all       : export_all,
                export_without_images: export_without_images,
                export_types     : export_types,
                export_category  : export_category,
                filename         : filename,
                security         : ewoo_product_export_params.export_nonce
            },
            dataType: 'json',
            success: function ( response ) {
                if ( response.success ) {
                    if ( 'done' === response.data.step ) {
                        // console.dir(response.data);
                        // return false;
                        $this.$form.find('.woocommerce-exporter-progress').val(response.data.percentage);
                        window.location.href = response.data.url;
                        setTimeout(function () {
                            $this.$form.removeClass('woocommerce-exporter__exporting');
                            $this.$form.find('.woocommerce-exporter-button').prop('disabled', false);
                        }, 2000);
                    } else {
                        $this.$form.find('.woocommerce-exporter-progress').val(response.data.percentage);
                        $this.processStep(parseInt(response.data.step, 10), data, response.data.columns, filename);
                    }
                }


            }
        }).fail(function ( response ) {
            window.console.log(response);
        });
    };

    /**
     * Handle fields per export type.
     */
    productExportForm.prototype.exportTypeFields = function () {
        var exportCategory = $('.woocommerce-exporter-category');

        if ( -1 !== $.inArray('variation', $(this).val()) ) {
            exportCategory.closest('tr').hide();
            exportCategory.val('').change(); // Reset WooSelect selected value.
        } else {
            exportCategory.closest('tr').show();
        }
    };

    /**
     * Function to call productExportForm on jquery selector.
     */
    $.fn.ewoo_product_export_form = function () {
        new productExportForm(this);
        return this;
    };

    $('.woocommerce-exporter').ewoo_product_export_form();

})(jQuery, window);
