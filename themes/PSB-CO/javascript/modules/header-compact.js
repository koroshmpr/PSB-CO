/**
 * Compact header on scroll — IntersectionObserver only (no scroll listeners), with hysteresis:
 * compact once scrolled past 64px, expand again only above 16px, so it never flickers at the edge.
 * CSS animates transforms only (layout.css); will-change is applied just while animating.
 */
export function initHeaderCompact() {
	if (!('IntersectionObserver' in window) || !document.getElementById('header')) return;

	const root = document.documentElement;
	const make = (top) => {
		const el = document.createElement('div');
		el.setAttribute('aria-hidden', 'true');
		el.style.cssText = `position:absolute;top:${top}px;inset-inline-start:0;width:1px;height:1px;pointer-events:none;`;
		document.body.prepend(el);
		return el;
	};
	const far = make(64); // leaves the viewport → compact
	const near = make(16); // re-enters the viewport → expand

	let timer = null;
	const set = (compact) => {
		if (root.classList.contains('is-compact') === compact) return;
		root.classList.add('is-header-animating');
		root.classList.toggle('is-compact', compact);
		clearTimeout(timer);
		timer = setTimeout(() => root.classList.remove('is-header-animating'), 350);
	};

	new IntersectionObserver((entries) => {
		entries.forEach((e) => {
			if (e.target === far && !e.isIntersecting && e.boundingClientRect.top < 0) set(true);
			if (e.target === near && e.isIntersecting) set(false);
		});
	}).observe(far);
	const io2 = new IntersectionObserver((entries) => {
		entries.forEach((e) => {
			if (e.isIntersecting) set(false);
		});
	});
	io2.observe(near);
}
