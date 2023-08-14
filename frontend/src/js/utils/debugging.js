export default class Debugging {
    constructor($container) {
        this.$container = $container;
        this.$log = null;

        if (!window.UNZER_DEBUG) {
            return;
        }

        this.createLogTemplate();
    }

    log(context, data = {}) {
        if (!window.UNZER_DEBUG) {
            return;
        }

        if (!this.$log) {
            this.createLogTemplate();
        }

        this.$log.append(
            '<li><strong>' + context + '</strong><pre>' + JSON.stringify(data,  null, '  ') +  '</pre></li>'
        );
    }

    createLogTemplate() {
        this.$container.append(
            $('<div class="debug-log card card-body bg-light"><ul class="list-unstyled"></ul></div>')
        );
        this.$log = this.$container.find('.debug-log > ul');
    }
}
