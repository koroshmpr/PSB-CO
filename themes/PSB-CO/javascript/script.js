/**
 * PSB-CO front-end entry (deferred).
 * - Alpine.js (+ intersect) for UI state (menu, search modal, tabs …)
 * - IntersectionObserver reveal instead of AOS
 * - Swiper is lazy-loaded from a separate bundle only where sliders exist
 */
import Alpine from 'alpinejs';
import intersect from '@alpinejs/intersect';
import { initReveal } from './modules/reveal';
import { initSwiperLoader } from './modules/swiper-loader';
import psbSearch from './modules/search';
import { initNavFeedback } from './modules/nav-feedback';
import { initHeaderCompact } from './modules/header-compact';
import { initTimeline } from './modules/timeline';
import { initFactoryDraw } from './modules/factory-draw';

window.Alpine = Alpine;
Alpine.plugin(intersect);
Alpine.data('psbSearch', psbSearch);

const boot = () => {
	Alpine.start();
	initReveal();
	initSwiperLoader();
	initNavFeedback();
	initHeaderCompact();
	initTimeline();
	initFactoryDraw();
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
	boot();
}
