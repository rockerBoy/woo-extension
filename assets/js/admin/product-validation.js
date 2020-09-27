/*global ajaxurl, ewoo_product_import_params */
;(function ($, window) {
    if (Array.prototype.equals) {
        console.warn("Overriding existing Array.prototype.equals. Possible causes: New API defines the method, there's a framework conflict or you've got double inclusions in your code.");
    }
// attach the .equals method to Array's prototype to call it on any array
    Array.prototype.equals = function (array) {
        // if the other array is a falsy value, return
        if (!array) {
            return false;
        }

        // compare lengths - can save a lot of time
        if (this.length != array.length) {
            return false;
        }

        for (let i = 0, l=this.length; i < l; i++) {
            // Check if we have nested arrays
            if (this[i] instanceof Array && array[i] instanceof Array) {
                // recurse into the nested arrays
                if (!this[i].equals(array[i])) {
                    return false;
                }
            } else if (this[i] != array[i]) {
                // Warning - two different object instances will never be equal: {x:20} != {x:20}
                return false;
            }
        }
        return true;
    }
// Hide method from for-in loops
    Object.defineProperty(Array.prototype, "equals", {enumerable: false});

    let mappingForm = function ( $form ) {
        this.form = $form;
        this.requiredFields = ewoo_product_import_params.mapping.required
        this.selectedFields = [];
        this.setFields = this.setFields.bind(this);
        this.setFields();
    };


    mappingForm.prototype.setFields = function () {
        let $this = this;
        $this.selectedFields = [];
        $this.form.find('button[name=save_step]').on('click', function (e) {
            let data = {};
            let formData = new FormData($this.form[0]);
            formData.forEach((value, key) => {data[key] = value});

            $this.selectedFields = [];

            $('body').find('.extended-errors').removeClass('hidden');
            $('.extended-errors').each(function () {
                $(this).find('div').addClass('hidden');
            });

            $this.requiredFields.forEach((name, key) => {
                let mappingSelect = $this.form.find('select').eq(key);
                let selected = mappingSelect.val();
                mappingSelect.parents('td').removeAttr('class');

                if (
                   typeof selected != 'undefined' &&
                   $this.requiredFields.includes(selected) &&
                   ! $this.selectedFields.includes(selected)
               ) {
                   $this.selectedFields[key] = selected;
                   mappingSelect.parents('td').addClass('item-success');
                } else {
                    $('body').find('.extended-errors').removeClass('hidden');
                    $('#required_'+name).removeClass('hidden');
                    mappingSelect.parents('td').addClass('item-danger');
                }
            });

            if (!$this.selectedFields.equals($this.requiredFields)) {
                return false;
            }
        });

    };

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