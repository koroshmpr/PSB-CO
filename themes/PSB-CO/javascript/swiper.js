/**
 * Swiper bundle — loaded on demand by modules/swiper-loader.js.
 * Only the modules the theme actually uses are included.
 */
import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay, FreeMode, Thumbs } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/free-mode';

Swiper.use([Navigation, Pagination, Autoplay, FreeMode, Thumbs]);

const SLIDER_SELECTOR = '.post-slider, .post_swiper, .hero_swiper, [data-perpage]';

const num = (v, d) => (v !== undefined && v !== '' && !Number.isNaN(parseFloat(v)) ? parseFloat(v) : d);

function initSlider(slider, i) {
	if (slider.classList.contains('swiper-initialized') || !slider.querySelector('.swiper-wrapper')) return;

	const d = slider.dataset;
	const perfix = d.perfix ?? 'slider';
	const index = d.index ?? i;
	const parent = slider.parentElement;

	const nextEl =
		slider.querySelector('.swiper-button-next') ||
		parent?.querySelector('.swiper-button-next') ||
		document.querySelector(`.${perfix}-next-${index}`);
	const prevEl =
		slider.querySelector('.swiper-button-prev') ||
		parent?.querySelector('.swiper-button-prev') ||
		document.querySelector(`.${perfix}-prev-${index}`);
	const paginationEl =
		slider.querySelector('.swiper-pagination') ||
		slider.querySelector('.hero-pagination') ||
		parent?.querySelector('.swiper-pagination') ||
		parent?.querySelector('.hero-pagination');

	const autoplay = num(d.autoplay, 0);
	const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	// eslint-disable-next-line no-new
	new Swiper(slider, {
		direction: d.direction ?? 'horizontal',
		grabCursor: true,
		speed: reduceMotion ? 0 : num(d.speed, 600),
		loop: d.loop === '1' || d.loop === 'true',
		freeMode: d.free === '1',
		spaceBetween: num(d.space, 20),
		slidesPerView: num(d.mobile, 1.1),
		breakpoints: {
			768: { slidesPerView: num(d.tablet, 1.7) },
			1224: { slidesPerView: num(d.laptop, 3.5) },
			1440: { slidesPerView: num(d.perpage, 4.5) },
		},
		pagination: paginationEl ? { el: paginationEl, clickable: true, dynamicBullets: true } : false,
		navigation: nextEl && prevEl ? { nextEl, prevEl } : false,
		autoplay:
			autoplay > 0 && !reduceMotion
				? { delay: autoplay, disableOnInteraction: false, pauseOnMouseEnter: true }
				: false,
		on: {
			init() {
				window.AOS?.refresh();
			},
		},
	});
}

function init(root = document) {
	root.querySelectorAll(SLIDER_SELECTOR).forEach(initSlider);
}

function initModalGallery() {
	if (window.modalSwiperInstance) return window.modalSwiperInstance;
	const thumbEl = document.querySelector('.thumb-modal-slider');
	const mainEl = document.querySelector('.main-modal-slider');
	if (!mainEl) return null;

	const thumbSlider = thumbEl
		? new Swiper(thumbEl, {
				spaceBetween: 8,
				slidesPerView: 'auto',
				freeMode: true,
				watchSlidesProgress: true,
				slideToClickedSlide: true,
				centerInsufficientSlides: true,
			})
		: null;

	const mainSlider = new Swiper(mainEl, {
		spaceBetween: 20,
		speed: 600,
		grabCursor: true,
		thumbs: thumbSlider ? { swiper: thumbSlider } : undefined,
		on: {
			slideChange() {
				window.dispatchEvent(
					new CustomEvent('slide-changed', {
						detail: {
							index: this.activeIndex,
							portfolioStart: parseInt(mainEl.dataset.portfolioStart, 10),
						},
					})
				);
			},
		},
	});

	window.modalSwiperInstance = mainSlider;
	return mainSlider;
}

window.PSBSwiper = { Swiper, init, initModalGallery };
