import Swiper from 'swiper/bundle';
import Alpine from 'alpinejs';
import intersect from '@alpinejs/intersect';
import AOS from 'aos';
import 'aos/dist/aos.css';

document.addEventListener('DOMContentLoaded', function () {
	window.Alpine = Alpine;
	window.AOS = AOS;

	Alpine.plugin(intersect);
	Alpine.start();

	// Initialize AOS (Animate On Scroll) with anti-flicker & zero CLS configuration
	AOS.init({
		duration: 700,
		easing: 'ease-out-cubic',
		once: true,
		mirror: false,
		offset: 80,
		disable: false,
		startEvent: 'DOMContentLoaded'
	});

	window.addEventListener('load', function() {
		AOS.refresh();
	});

	const initPageSliders = () => {
		document.querySelectorAll('.post-slider, .post_swiper, .hero_swiper, [data-perpage]').forEach((slider, i) => {
			if (slider.classList.contains('swiper-initialized')) return;

			let autoplaySpeed = slider.dataset.autoplay ? parseFloat(slider.dataset.autoplay) : 0;
			let perPage = slider.dataset.perpage ? parseFloat(slider.dataset.perpage) : 4.5;
			let perPageLapTop = slider.dataset.laptop ? parseFloat(slider.dataset.laptop) : 3.5;
			let perPageTablet = slider.dataset.tablet ? parseFloat(slider.dataset.tablet) : 1.7;
			let perPageMobile = slider.dataset.mobile ? parseFloat(slider.dataset.mobile) : 1.1;
			let perfix = slider.dataset.perfix ?? 'slider';
			let direction = slider.dataset.direction ?? 'horizontal';
			let index = slider.dataset.index ?? i;
			let space = slider.dataset.space ? parseFloat(slider.dataset.space) : 20;
			let speed = slider.dataset.speed ? parseFloat(slider.dataset.speed) : 800;
			let free = slider.dataset.free === '1';
			let loop = slider.dataset.loop === '1' || slider.dataset.loop === 'true';

			// Dynamic navigation and pagination resolution
			let nextEl = slider.querySelector('.swiper-button-next') ||
				slider.parentElement?.querySelector('.swiper-button-next') ||
				document.querySelector(`.${perfix}-next-${index}`);
			let prevEl = slider.querySelector('.swiper-button-prev') ||
				slider.parentElement?.querySelector('.swiper-button-prev') ||
				document.querySelector(`.${perfix}-prev-${index}`);
			let paginationEl = slider.querySelector('.swiper-pagination') ||
				slider.querySelector('.hero-pagination') ||
				slider.parentElement?.querySelector('.swiper-pagination') ||
				slider.parentElement?.querySelector('.hero-pagination');

			new Swiper(slider, {
				direction: direction,
				grabCursor: true,
				speed: speed,
				loop: loop,
				effect: 'slide',
				freeMode: free,
				spaceBetween: space,
				slidesPerView: perPageMobile,
				centeredSlides: false,
				breakpoints: {
					1440: {
						slidesPerView: perPage,
					},
					1224: {
						slidesPerView: perPageLapTop,
					},
					768: {
						slidesPerView: perPageTablet,
					}
				},
				pagination: paginationEl ? {
					el: paginationEl,
					clickable: true,
					dynamicBullets: true,
				} : false,
				navigation: (nextEl && prevEl) ? {
					nextEl: nextEl,
					prevEl: prevEl,
				} : false,
				autoplay: autoplaySpeed > 0 ? {
					delay: autoplaySpeed,
					disableOnInteraction: false,
					pauseOnMouseEnter: true,
				} : false,
				on: {
					init: function () {
						setTimeout(() => {
							AOS.refresh();
						}, 150);
					},
					slideChangeTransitionStart: function () {
						if (slider.classList.contains('hero_swiper')) {
							slider.querySelectorAll('[data-aos]').forEach(el => el.classList.remove('aos-animate'));
						}
					},
					slideChangeTransitionEnd: function () {
						if (slider.classList.contains('hero_swiper')) {
							slider.querySelectorAll('.swiper-slide-active [data-aos]').forEach(el => el.classList.add('aos-animate'));
						}
					}
				}
			});
		});
	};

	initPageSliders();

	window.initModalGallery = function () {
		setTimeout(() => {
			const thumbSlider = new Swiper(".thumb-modal-slider", {
				spaceBetween: 8,
				slidesPerView: 'auto',
				freeMode: true,
				watchSlidesProgress: true,
				slideToClickedSlide: true,
				centerInsufficientSlides: true,
			});

			const mainSlider = new Swiper(".main-modal-slider", {
				spaceBetween: 20,
				speed: 600,
				grabCursor: true,
				thumbs: {
					swiper: thumbSlider,
				},
				on: {
					slideChange: function () {
						const sliderEl = document.querySelector('.main-modal-slider');
						if (!sliderEl) return;

						const portfolioIndex = parseInt(sliderEl.dataset.portfolioStart);
						window.dispatchEvent(new CustomEvent('slide-changed', {
							detail: {
								index: this.activeIndex,
								portfolioStart: portfolioIndex
							}
						}));
					}
				}
			});

			window.modalSwiperInstance = mainSlider;
		}, 100);
	};
});
