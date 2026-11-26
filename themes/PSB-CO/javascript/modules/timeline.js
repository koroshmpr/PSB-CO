/**
 * Scroll-driven timeline (ol.timeline[data-timeline]) — no library.
 * - The vertical line "draws" with scroll: --tl-p (0..1) on the <ol> scales .timeline::after.
 * - Each item fades/slides in once when it enters the viewport (IntersectionObserver → .is-in).
 * - A dot fills once the progress line has passed it (.is-passed).
 * Progressive enhancement: nothing is hidden until .is-anim is added here, and it is never added
 * without IntersectionObserver or when the user prefers reduced motion.
 */
const ANCHOR = 0.62; // progress follows the point 62% down the viewport

function setup(list) {
	const items = Array.from(list.querySelectorAll(':scope > .timeline-item'));
	if (!items.length) return;

	let offsets = [];
	let height = 0;
	let ticking = false;
	let active = false;

	const measure = () => {
		height = list.offsetHeight;
		offsets = items.map((li) => li.offsetTop + 10); // dot centre ≈ top + .45rem + 4.5px
	};

	const update = () => {
		ticking = false;
		const rect = list.getBoundingClientRect();
		const line = window.innerHeight * ANCHOR - rect.top;
		const p = Math.min(1, Math.max(0, line / (height || 1)));
		list.style.setProperty('--tl-p', p.toFixed(4));
		const reached = p * height;
		items.forEach((li, i) => li.classList.toggle('is-passed', offsets[i] <= reached));
	};

	const onScroll = () => {
		if (!ticking) {
			ticking = true;
			requestAnimationFrame(update);
		}
	};

	// Items already on screen at init are shown at once (no flash), the rest reveal on entry.
	const vh = window.innerHeight;
	items.forEach((li) => {
		if (li.getBoundingClientRect().top < vh) li.classList.add('is-in');
	});
	list.classList.add('is-anim');
	measure();
	update();

	const reveal = new IntersectionObserver(
		(entries) => {
			entries.forEach((e) => {
				if (e.isIntersecting) {
					e.target.classList.add('is-in');
					reveal.unobserve(e.target);
				}
			});
		},
		{ rootMargin: '0px 0px -8% 0px', threshold: 0.01 }
	);
	items.forEach((li) => li.classList.contains('is-in') || reveal.observe(li));

	// Only listen to scroll while the timeline is near the viewport.
	new IntersectionObserver(
		([e]) => {
			if (e.isIntersecting && !active) {
				active = true;
				measure();
				window.addEventListener('scroll', onScroll, { passive: true });
				onScroll();
			} else if (!e.isIntersecting && active) {
				active = false;
				window.removeEventListener('scroll', onScroll);
				update(); // settle at 0 or 1
			}
		},
		{ rootMargin: '25% 0px 25% 0px' }
	).observe(list);

	window.addEventListener('resize', () => {
		measure();
		onScroll();
	}, { passive: true });
}

export function initTimeline() {
	if (!('IntersectionObserver' in window)) return;
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
	document.querySelectorAll('ol.timeline[data-timeline]').forEach(setup);
}
