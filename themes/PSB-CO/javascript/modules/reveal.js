/**
 * Tiny scroll-reveal (AOS replacement).
 * Adds `.aos-animate` to [data-aos] and `.is-revealed` to [data-reveal]
 * when they enter the viewport. CSS lives in tailwind/custom/components/motion.css.
 */
const SELECTOR = '[data-aos]:not(.aos-animate), [data-reveal]:not(.is-revealed)';

const show = (el) => {
	const delay = el.dataset.aosDelay || el.dataset.revealDelay;
	if (delay) {
		el.style.setProperty('--reveal-delay', `${parseInt(delay, 10) || 0}ms`);
	}
	el.classList.add(el.hasAttribute('data-aos') ? 'aos-animate' : 'is-revealed');
};

let observer = null;

export function scanReveal(root = document) {
	const items = root.querySelectorAll(SELECTOR);
	if (!items.length) return;

	if (!observer) {
		if (!('IntersectionObserver' in window)) {
			items.forEach(show);
			return;
		}
		observer = new IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting) {
						show(entry.target);
						observer.unobserve(entry.target);
					}
				});
			},
			{ rootMargin: '0px 0px -8% 0px', threshold: 0.01 }
		);
	}
	items.forEach((el) => observer.observe(el));
}

export function initReveal() {
	scanReveal();
	// Legacy API used by older inline scripts (AOS.refresh / AOS.init).
	window.AOS = window.AOS || {
		init: () => scanReveal(),
		refresh: () => scanReveal(),
		refreshHard: () => scanReveal(),
	};
}
