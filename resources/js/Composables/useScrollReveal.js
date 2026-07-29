import { onMounted, onUnmounted } from 'vue';

/**
 * Scroll-reveal animation using IntersectionObserver.
 * Elements with [data-reveal] get class 'revealed' when visible.
 *
 * CSS handles the actual animation via:
 *   [data-reveal] { opacity: 0; transform: translateY(30px); transition: ... }
 *   [data-reveal].revealed { opacity: 1; transform: translateY(0); }
 *
 * Options on elements:
 *   data-reveal="fade-up" | "fade-left" | "fade-right" | "zoom" | "fade"
 *   data-delay="100" (ms delay via transition-delay)
 */
export function useScrollReveal() {
    let observer = null;

    onMounted(() => {
        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
        );

        document.querySelectorAll('[data-reveal]').forEach((el) => {
            const delay = el.dataset.delay;
            if (delay) {
                el.style.transitionDelay = `${delay}ms`;
            }
            observer.observe(el);
        });
    });

    onUnmounted(() => {
        observer?.disconnect();
    });
}
