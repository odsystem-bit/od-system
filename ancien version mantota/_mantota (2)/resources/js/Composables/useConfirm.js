import { ref } from 'vue';

const visible = ref(false);
const title = ref('Confirmation');
const message = ref('');
const variant = ref('danger');
const confirmLabel = ref('Confirmer');
const cancelLabel = ref('Annuler');
let resolveCallback = null;

export function useConfirm() {
    function ask(opts) {
        return new Promise((resolve) => {
            title.value = opts.title ?? 'Confirmation';
            message.value = opts.message ?? '';
            variant.value = opts.variant ?? 'danger';
            confirmLabel.value = opts.confirmLabel ?? 'Confirmer';
            cancelLabel.value = opts.cancelLabel ?? 'Annuler';
            resolveCallback = resolve;
            visible.value = true;
        });
    }

    function onConfirm() {
        visible.value = false;
        resolveCallback?.(true);
        resolveCallback = null;
    }

    function onCancel() {
        visible.value = false;
        resolveCallback?.(false);
        resolveCallback = null;
    }

    return {
        visible,
        title,
        message,
        variant,
        confirmLabel,
        cancelLabel,
        ask,
        onConfirm,
        onCancel,
    };
}
