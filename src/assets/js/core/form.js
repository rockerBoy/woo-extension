import Swal from "sweetalert2";

export class Form {
    constructor(form, controls) {
        this.form = form;
        this.controls = controls;
    }

    value() {
        const value = {};

        Object.keys(this.controls).forEach(control => {
            if(this.form[control].type !== 'file') {
                value[control] = this.form[control].value;
            } else {
                value[control] = this.form[control].files[0];
            }
        });

        try {
            value.nonce = ewoo_bundle_params.form_nonce;
            value.action = ewoo_bundle_params.form_action;
        } catch (e){ console.error(e) }

        return value;
    }

    isValid() {
        let isFormValid = true;

        Object.keys(this.controls).forEach(control => {
            const validators = this.controls[control];

            let isValid = true;

            validators.forEach(validator => {
                if (this.form[control].type !== 'file') {
                    isValid = validator(this.form[control].value) && isValid;
                } else {
                    isValid = validator(this.form[control].files[0]) && isValid;
                }
            });

            isValid ? clearError(this.form[control]) : setError(this.form[control]);

            isFormValid = isFormValid && isValid;
        });

        return isFormValid;
    }

    async send(data) {
        try {
            const request = new Request(window.ajaxurl, {
                method: 'post',
                body: data
            });
            return await fetch(request);
        } catch (error) {
            console.error(error);
        }
    }

    clear() {
        Object.keys(this.controls).forEach(control => {
           this.form[control].value = '';
        });
    }
}

function setError($control) {
    clearError($control);
    Swal.fire({
        title: 'Внимание!',
        text: 'Поле заполнено некорректно. Вам ПИЗДА!',
        icon: 'error',
        confirmButtonText: 'Принял'
    })
    $control.classList.add('invalid');
}

function clearError($control) {
    if ($control.classList) {
        $control.classList.remove('invalid');
    }
}