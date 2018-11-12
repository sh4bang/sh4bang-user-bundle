import $ from 'jquery';

/**
 * this component handle the password element on a form
 */
export default class PasswordType {

    /**
     * input element
     *
     * @param {jQuery} input
     */
    constructor(input) {
        /**
         * input element
         * @type {jQuery}
         */
        this.input = $(input);

        /**
         * form
         * @type {jQuery}
         */
        this.form = input.closest('form');

        /**
         * related input name
         * @var {jQuery|false}
         */
        this.relatedInputs = this.getRelatedFields(input.data('target'));

        this.initialize();
    }

    /**
     * initialization runtime
     */
    initialize() {

        if (this.relatedInputs === false) {
            throw new Error('Unable to find the related password field');
        }

        this.initializeEvents();
    }

    /**
     * initialize events
     */
    initializeEvents()
    {
        this.input.on('change', () => {
            this.onInputChange();
        });
    }


    /**
     * when the slug is changed
     */
    onInputChange()
    {
        if (this.input.is(':checked')) {
            this.relatedInputs.each(function(index) {
                $(this).val('');
                $(this).closest('.form-group').hide();
            });
        } else {
            this.relatedInputs.each(function(index) {
                $(this).closest('.form-group').show();
            });
        }
    }

    /**
     * returns the related field element (or false if not found)
     *
     * @param {string} name
     *
     * @returns {jQuery|boolean}
     */
    getRelatedFields(name)
    {
        if (this.form.length === 0) {
            return false;
        }

        let fields = this.form.find(`[name*="${name}"][type=password]`);

        return fields.length === 0 ? false : fields;
    }
}
