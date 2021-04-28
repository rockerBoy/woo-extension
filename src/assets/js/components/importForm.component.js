import { Component } from "../core/component";
import { Form } from '../core/form';
import { Validators } from "../core/validators";

export class ImportFormComponent extends Component {
    constructor(id) {
        super(id);

        this.maxFileSize = 2048; //2MB
        this.startPos = 1;
        this.fileName = null;
        this.$steps = document.querySelector('.wc-progress-steps');
        this.step = 0;

        this.allowedFileTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
    }

    init() {
        this.$el.addEventListener('submit', submitHandler.bind(this));
        this.form = new Form(this.$el, {
            import: [
                Validators.required,
                Validators.maxFileSize(this.maxFileSize),
                Validators.allowedFileType([
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                ])
            ],
            starting_row: [Validators.required],
        });
    }

    onSuccess() {
        this.nextStep();
    }

    nextStep() {
        const step = this.$steps.children;
        // this.step++;
        // step[this.step].classList.add('active');
    }
}

function submitHandler(event) {
    event.preventDefault();

    if (this.form.isValid()) {
        const formData = new FormData();
        const rawData = { ...this.form.value() };

        Object.keys(rawData).forEach(data => {
            formData.append(data, rawData[data]);
        })

        this.form.send(formData);
    }
}