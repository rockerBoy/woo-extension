/*global ajaxurl, ewoo_product_import_params */
;(function ($, window) {
    if(Array.prototype.equals)
        console.warn("Overriding existing Array.prototype.equals. Possible causes: New API defines the method, there's a framework conflict or you've got double inclusions in your code.");
// attach the .equals method to Array's prototype to call it on any array
    Array.prototype.equals = function (array) {
        // if the other array is a falsy value, return
        if (!array)
            return false;

        // compare lengths - can save a lot of time
        if (this.length != array.length)
            return false;

        for (var i = 0, l=this.length; i < l; i++) {
            // Check if we have nested arrays
            if (this[i] instanceof Array && array[i] instanceof Array) {
                // recurse into the nested arrays
                if (!this[i].equals(array[i]))
                    return false;
            }
            else if (this[i] != array[i]) {
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
        $this.form.on('submit', function (e) {
            let data = {};
            let formData = new FormData($this.form[0]);
            let isValid = true;
            formData.forEach((value, key) => {data[key] = value});

            $this.form.find('select').each(function (){
               let val = $(this).val();
               if (typeof val != 'undefined' && $this.requiredFields.includes(val)) {
                   let pos = $this.requiredFields.indexOf(val);
                   $this.selectedFields[pos] = val;
                   // $(this).parents('td').addClass('item-success');
               }
            });

            for (let i = 0; i <= $this.selectedFields.length; i++) {
                if (typeof $this.selectedFields[i] != 'undefined') {
                    let row = 'field-'+$this.selectedFields[i];
                    $('.'+row).find('td').each(function () {
                        $(this).addClass('item-success');
                    });
                } else {
                    let row = 'field-'+$this.requiredFields[i];
                    $('.'+row).find('td').each(function () {
                        $(this).removeClass('item-success').addClass('item-danger');
                    });
                }
            }

            if (!$this.selectedFields.equals($this.requiredFields)) {
                return false;
            }
        });

    };

    $.fn.extended_product_mapping = function () {
        new mappingForm(this);
        return this;
    };

    $('.extended-mapping').extended_product_mapping();
})(jQuery, window);