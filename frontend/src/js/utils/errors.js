export default class ErrorHandler {
    /**
     * @param {JQuery<HTMLElement>|null} $wrapper Wrapper for Container to display error messages in
     * @param {JQuery<HTMLElement>|null} $holder Container to display error messages in
     */
    constructor($wrapper, $holder) {
        this.$wrapper = $wrapper || $('#error-container');
        this.$holder = $holder || this.$wrapper.find('.alert');
    }

    /**
     * Show Error message
     * @param {String} message
     */
    show(message) {
        this.$wrapper.show();
        this.$holder.html(message);
    }

    /**
     * Hide error message
     */
    hide() {
        this.$wrapper.hide();
        this.$holder.html();
    }
}
