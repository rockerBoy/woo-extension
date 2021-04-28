import { ImportFormComponent } from "./importForm.component";
import {Form} from "../core/form";
import {Validators} from "../core/validators";

export class PrimaryImportFormComponent extends ImportFormComponent {
    constructor(id) {
        super(id);

        this.action = 'ext_do_ajax_product_import';
        this.type = 'primary_import';
    }

    init() {
        this.$el.addEventListener('submit', submitHandler.bind(this));
        this.form = new Form(this.$el, {
            import: [
                Validators.required,
                Validators.maxFileSize(2048),
                Validators.allowedFileType([
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                ])
            ],
            starting_row: [Validators.required],
        });
    }
}

function submitHandler(event) {
    event.preventDefault();

    if (this.form.isValid()) {
        const rawData = { ...this.form.value() };

        rawData.action = this.action;
        rawData.security = rawData.nonce;
        rawData.import_type = this.type;

        const data = convertFormData(rawData);
        const result = this.form.send(data);

        result.then(response => {
            this.onSuccess();
        });
    }
}

function convertFormData(rawData) {
    const formDataObj = new FormData();

    Object.keys(rawData).forEach(data => {
        formDataObj.append(data, rawData[data]);
    })

    return formDataObj;
}