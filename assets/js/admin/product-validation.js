/*global ajaxurl, ewoo_product_import_params */
;(function ($, window) {
    let mappingForm = function ( $form ) {
        this.form = $form;
        this.requiredFields = ewoo_product_import_params.mapping.required
        this.selectedFields = [];
        this.markers = {
            'danger': 'rgba(255, 10, 10, .6)',
            'success': 'rgba(3, 198, 47, .6)',
        };
        this.setFields = this.setFields.bind(this);
        this.setFields();
        let validationFields = {};
        this.requiredFields.forEach((name, key) => {
            validationFields[name] = false;
        });
        this.requiredFields = validationFields;
    };

    mappingForm.prototype.setFields = function () {
        let $this = this;

        $this.form.find('button[name=save_step]').on('click', function (e) {
            $this.selectedFields = [];
            let isMappingValid = false;
            let mappingData = new FormData(document.querySelector('form'));
            let mappedValues = mappingData.getAll('map_to[]');

            $('body').find('.extended-errors').removeClass('hidden');

            $('.extended-errors').each(function () {
                $(this).find('div').addClass('hidden');
            });

            let required = Object.assign({}, $this.requiredFields);
            let validFields = 0;
            let minSize = Object.getOwnPropertyNames($this.requiredFields).length;

            mappedValues.forEach((name, key) => {
                let row = $('.mapping-field:eq('+key+')');
                let selectCell = row.find('td:eq(1)');
                selectCell.removeAttr('style');
                selectCell.removeClass('item-danger');

                // if (mappedValues.length >= minSize && minSize > validFields) {
                    if ((typeof name == 'undefined' || name === '') && validFields < minSize) {
                        selectCell.css({background: $this.markers.danger});
                        selectCell.addClass('item-danger');
                        return false
                    }

                    if (typeof name != 'undefined' && required[name] === false) {
                        required[name] = true;
                        selectCell.css({background: $this.markers.success});
                        validFields++;
                    } else if (required[name]) {
                        selectCell.css({background: $this.markers.danger});
                        selectCell.addClass('item-danger');
                        return false;
                    }
                // }
            });

            let reqProps = Object.getOwnPropertyNames(required);

            reqProps.forEach((item, key) => {
                $('#required_'+item).addClass('hidden');

                if (required[item] == false) {
                    $('#required_'+item).removeClass('hidden');
                }
            });

            if (validFields >= minSize) {
                $('.item-danger').each(function (){
                    $(this).removeAttr('style');
                    $(this).removeClass('item-danger');
                });

                isMappingValid = true;
            }

            return isMappingValid;
        });
    }
    let excelResolverForm = function ( $form ) {
        this.form = $form;
        this.errorsCounter = $('.extended-validation-msg')
            .find('div:eq(2)')
            .find('strong');
        this.success = $('.extended-validation-msg')
            .find('div:eq(1)')
            .find('strong');
        this.total = $('.extended-validation-msg')
            .find('div:eq(0)')
            .find('strong');
        this.showInfo = this.showInfo.bind(this);
        this.removeRow = this.removeRow.bind(this);
        this.checkForm = this.checkForm.bind(this);
        this.showInfo();
        this.removeRow();
        this.checkForm();
    }

    excelResolverForm.prototype.showInfo = function () {
        let $this = this;
        $this.form.find('.remove-item').on('click', function (e) {
            e.preventDefault();

            let $this = $(this);
            let ID = $this.attr('data-rel');
            let row = $this.parents('tr');
            let errorsCounter = $('.extended-validation-msg')
                .find('div:eq(2)')
                .find('strong');
            row.hide();

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action           : 'ext_do_ajax_product_remove',
                    prod_id          : ID,
                    security         : ewoo_product_import_params.import_nonce,
                },
                dataType: 'json',
                success: function ( response ) {
                    if ( response.success ) {
                        if ( 'done' === response.data.result ) {
                            let errors = errorsCounter.html();
                            errorsCounter.html(--errors);
                            row.remove();
                        }
                    }


                }
            }).fail(function ( response ) {
                window.console.log(response);
            });
        });
    };

    excelResolverForm.prototype.removeRow = function () {
        let $this = this;
        $this.form.find('#show_all').on('change', function () {
            $this.form.find('.item-success').each(function () {
                $(this).toggleClass('hidden');
            });
        });
    };

    excelResolverForm.prototype.checkForm = function () {
        let $this = this;
        $this.form.find('.btn-check-form').on('click', function (e) {
            e.preventDefault();
            let formData = [];

            $this.form.find('.edit-product-sku').each(function () {
                let relID = $(this).attr('data-rel');
                let row = $('#row-'+relID);
                let productID = row.find('.remove-item').val();
                let val = $(this).val();

                let data = {
                    item: 'sku',
                    relation_id: relID,
                    product_id: productID,
                    value: val.trim()
                }

                if (typeof data.value != 'undefined') {
                    if (data.value != '') {
                        let regex = new RegExp('^\\d+\\.\\d+\\.\\d+', 'i');
                        if (regex.test(data.value)) {
                            formData.push(data);
                        }
                    }
                }
            });

            $this.form.find('.select_valid_category').each(function () {
                let relID = $(this).attr('data-rel');
                let row = $('#row-'+relID);
                let productID = row.find('.remove-item').val();
                let data = {
                    item: 'category',
                    relation_id: relID,
                    product_id: productID,
                    value: $(this).val()
                }
                if (typeof data.value != 'undefined' && data.value != '') {
                    formData.push(data);
                }
            });

            $.ajax({
                type: 'POST',
                enctype: 'multipart/form-data',
                url: ajaxurl,
                data: {
                    action           : 'ext_do_ajax_check_resolver_form',
                    form_data        : formData,
                    security         : ewoo_product_import_params.import_nonce,
                },
                dataType: 'json',
                success: function ( response ) {
                    let successClass = 'item-success';
                    let errorClass = 'item-danger';
                    if ( response.success ) {
                        formData.forEach((value, key) => {
                            let id = value.relation_id;
                            let row = $('#row-'+id);
                            let dataItem = response.data.products[id];

                            switch (value.item) {
                                case "sku":
                                    if (typeof dataItem != "undefined" && dataItem.status == "ok") {
                                        row.removeClass(errorClass).addClass(successClass);
                                        row.find('td:eq(1)').html(dataItem.sku);
                                        row.find('td:eq(4)').html('&nbsp;');
                                    } else {
                                        row.find('td:eq(1)').find('input').focus();
                                        alert(dataItem.msg)
                                        return false;
                                    }
                                    break;
                                case "category":
                                    if (typeof dataItem != "undefined" && dataItem.status == "ok") {
                                        row.removeClass(errorClass).addClass(successClass);
                                        row.find('td:eq(3)').html(dataItem.category);
                                        row.find('td:eq(4)').html('&nbsp;');
                                    } else {
                                        row.find('td:eq(3)').find('select').focus();
                                        alert(dataItem.msg)
                                        return false;
                                    }
                                    break;
                            }
                        });

                        $this.errorsCounter.html(response.data.errors);
                        $this.success.html(response.data.success);

                        if (response.data.status === 'ok' && response.data.errors === 0) {
                            $('.btn-check-form').remove();
                            $('.button-next').removeClass('hidden');
                        }
                    }
                }
            }).fail(function ( response ) {
                window.console.log(response);
            });
        });
    };

    $.fn.extended_product_mapping = function () {
        new mappingForm(this);
        return this;
    };
    $.fn.extended_validation = function () {
        new excelResolverForm(this);
        return this;
    };

    $('.extended-mapping').extended_product_mapping();
    $('.excel-resolver').extended_validation();
})(jQuery, window);