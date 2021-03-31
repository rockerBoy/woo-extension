export class Validators {
    static required(value) {
        return (typeof value === 'object')? value : value && value.trim();
    }

    static minLength(length) {
        return value => (value.length >= length)
    }

    static maxFileSize(fileSize) {
        return file => {
            if (! file) {
                return false;
            }

            return ((file.size/1024).toFixed(2) <= fileSize);
        }
    }

    static allowedFileType(types) {
        return file => {
            if (! file) {
                return false;
            }

            return types.includes(file.type);
        }
    }
}