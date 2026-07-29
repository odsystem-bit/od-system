import { ref } from 'vue';

let toastContainer = null;

export const registerToastContainer = (container) => {
    toastContainer = container;
};

export const useNotification = () => {
    const notify = (message, type = 'info', duration = 4000) => {
        if (!toastContainer) {
            console.warn('Toast container not registered');
            return;
        }
        
        return toastContainer.addNotification(message, type, duration);
    };

    const success = (message, duration = 4000) => notify(message, 'success', duration);
    const danger = (message, duration = 5000) => notify(message, 'danger', duration);
    const warning = (message, duration = 4000) => notify(message, 'warning', duration);
    const info = (message, duration = 4000) => notify(message, 'info', duration);

    return {
        notify,
        success,
        danger,
        warning,
        info,
    };
};
