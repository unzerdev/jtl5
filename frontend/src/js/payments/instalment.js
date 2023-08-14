/**
 * Heidelpay Installment Modal Window Handler
 *
 * @param {string} modalSelector
 * @param {HTMLElement} btn Submit Trigger
 * @param {JQuery<HTMLElement>} $form
 */
const Installment = (modalSelector, btn, $form) => {
    var modal = $(modalSelector);

    btn.addEventListener('click', function () {
        $form.trigger('submit');
    });

    $form.on('submit', function (e) {
        if (!modal.is(':visible')) {
            e.preventDefault();
            modal.modal('show');
            return false;
        }

        return true;
    });
};

export default Installment;