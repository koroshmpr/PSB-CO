/**
 * Loads the Swiper bundle (theme/js/swiper.min.js + swiper.min.css) only when a
 * slider is about to enter the viewport. Pages without sliders never download it.
 */
const SLIDER_SELECTOR = '.post-slider, .post_swiper, .hero_swiper, [data-perpage]';

// Base URL comes from wp_localize_script (functions.php); currentScript is a fallback.
const thisScript = document.currentScript;
const base =
	(window.psbTheme && window.psbTheme.jsBase) ||
	(thisScript ? thisScript.src.replace(/script\.min\.js.*$/, '') : '');
const version = `?ver=${(window.psbTheme && window.psbTheme.ver) || '1'}`;

let loading = null;

export function loadSwiper() {
	if (window.PSBSwiper) return Promise.resolve(window.PSBSwiper);
	if (loading) return loading;

	loading = new Promise((resolve, reject) => {
		const css = document.createElement('link');
		css.rel = 'stylesheet';
		css.href = `${base}swiper.min.css${version}`;
		document.head.appendChild(css);

		const js = document.createElement('script');
		js.src = `${base}swiper.min.js${version}`;
		js.async = true;
		js.onload = () => resolve(window.PSBSwiper);
		js.onerror = reject;
		document.head.appendChild(js);
	});
	return loading;
}

export function initSwiperLoader() {
	// Product gallery modal API (template-parts/product/single/product-gallery.php)
	window.initModalGallery = () => loadSwiper().then((api) => api && api.initModalGallery());

	// Pages with the gallery modal: warm the bundle up when the browser is idle
	// so the first click opens instantly.
	if (document.querySelector('.main-modal-slider')) {
		(window.requestIdleCallback || ((cb) => setTimeout(cb, 1500)))(() => loadSwiper());
	}

	const sliders = document.querySelectorAll(SLIDER_SELECTOR);
	if (!sliders.length) return;

	const boot = () => loadSwiper().then((api) => api && api.init(document));

	if (!('IntersectionObserver' in window)) {
		boot();
		return;
	}

	const io = new IntersectionObserver(
		(entries) => {
			if (entries.some((e) => e.isIntersecting)) {
				io.disconnect();
				boot();
			}
		},
		{ rootMargin: '400px 0px' }
	);
	sliders.forEach((el) => io.observe(el));
}
