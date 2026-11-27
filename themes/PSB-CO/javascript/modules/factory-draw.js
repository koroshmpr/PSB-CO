/**
 * Scroll-drawn line illustration ([data-draw-scroll] around an inline SVG whose stroked shapes carry pathLength="1").
 * Starts when the figure enters the viewport; with a sticky figure beside long text it completes as the
 * reader reaches the end of the section, otherwise over ~70% of a screen of scrolling.
 * Stages from progress p: --ff-draw (outlines), --ff-fill (tints), --ff-sign (sign text + red bar).
 * Progressive enhancement: nothing is hidden until .is-anim is added; never with reduced motion.
 */
const clamp = (v) => Math.min(1, Math.max(0, v));
const stage = (p, a, b) => clamp((p - a) / (b - a));

function setup(fig) {
	const section = fig.closest('section') || fig;
	let ticking = false;
	let s0 = 0;
	let s1 = 1;

	// Scroll range: starts when the figure's top enters the viewport; ends when a sticky figure is about to be
	// released at the section's end (else when the section's bottom reaches the viewport bottom), never in
	// less than 70% of a screen.
	// The figure's own (non-sticky) position is measured with position:static for a moment.
	const measure = () => {
		const vh = window.innerHeight;
		// The pinned element may be the figure itself or a wrapper (desktop column / About intro <figure>).
		let pin = fig;
		while (pin && pin !== section && 'sticky' !== getComputedStyle(pin).position) pin = pin.parentElement;
		const sticky = Boolean(pin && pin !== section);
		if (!sticky) pin = fig;
		const prev = pin.style.position;
		pin.style.position = 'static';
		const top = fig.getBoundingClientRect().top + window.scrollY;
		pin.style.position = prev;
		const bottom = section.getBoundingClientRect().bottom + window.scrollY;
		s0 = top - vh;
		// Sticky: finish just before the pinned block is pushed out (the last years / lines are passing it).
		const end = sticky
			? bottom - (parseFloat(getComputedStyle(pin).top) || 0) - pin.offsetHeight - vh * 0.15
			: bottom - vh;
		s1 = Math.max(s0 + vh * 0.7, end);
	};

	const update = () => {
		ticking = false;
		const p = clamp((window.scrollY - s0) / Math.max(1, s1 - s0));
		fig.style.setProperty('--ff-draw', stage(p, 0, 0.55).toFixed(4));
		fig.style.setProperty('--ff-fill', stage(p, 0.4, 0.75).toFixed(4));
		fig.style.setProperty('--ff-sign', stage(p, 0.68, 0.92).toFixed(4));
	};
	const onScroll = () => {
		if (!ticking) {
			ticking = true;
			requestAnimationFrame(update);
		}
	};
	// Lines draw one after another in document order (back to front): --k = 0..1 per shape.
	const shapes = fig.querySelectorAll('[pathLength]');
	shapes.forEach((el, i) => el.style.setProperty('--k', (i / Math.max(1, shapes.length - 1)).toFixed(3)));
	fig.classList.add('is-anim');
	measure();
	update();
	window.addEventListener('scroll', onScroll, { passive: true });
	window.addEventListener('resize', () => {
		measure();
		onScroll();
	}, { passive: true });
	// Lazy images / fonts above change the page height after init: measure again whenever it changes.
	if ('ResizeObserver' in window) {
		new ResizeObserver(() => {
			measure();
			onScroll();
		}).observe(document.body);
	}
}

export function initFactoryDraw() {
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
	document.querySelectorAll('[data-draw-scroll]').forEach(setup);
}
