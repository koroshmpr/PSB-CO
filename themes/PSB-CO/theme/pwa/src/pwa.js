/**
 * PSB PWA client — service-worker registration, «نسخه جدید» toast, custom install sheet.
 * Config: window.psbPWA (printed by inc/pwa/head.php; identical for every visitor → page-cache safe).
 * Any element with [data-install-hint] opens the install sheet on click (hidden in standalone by the theme CSS).
 */
(function () {
	'use strict';
	var C = window.psbPWA || {};
	var D = document;
	var nav = navigator;
	var store = {
		get: function (k) {
			try {
				return localStorage.getItem(k);
			} catch (e) {
				return null;
			}
		},
		set: function (k, v) {
			try {
				localStorage.setItem(k, v);
			} catch (e) {
				/* private mode */
			}
		},
	};
	var standalone = (window.matchMedia && matchMedia('(display-mode: standalone)').matches) || nav.standalone === true;
	var ua = nav.userAgent || '';
	var isIOS = /iphone|ipad|ipod/i.test(ua) || (nav.platform === 'MacIntel' && nav.maxTouchPoints > 1);
	var deferred = null;
	var sheet = null;
	var engaged = false;

	if (standalone) {
		D.documentElement.classList.add('is-standalone');
		if (!seenThisSession()) stat('standalone');
	}

	function seenThisSession() {
		try {
			if (sessionStorage.getItem('psb_pwa_s')) return true;
			sessionStorage.setItem('psb_pwa_s', '1');
		} catch (e) {
			return true;
		}
		return false;
	}

	function stat(ev) {
		if (!C.stat) return;
		try {
			var url = C.stat + (C.stat.indexOf('?') === -1 ? '?' : '&') + 'e=' + encodeURIComponent(ev);
			if (nav.sendBeacon) nav.sendBeacon(url);
			else fetch(url, { method: 'POST', credentials: 'omit', keepalive: true });
		} catch (e) {
			/* ignore */
		}
	}

	/* ---------------- styles (injected once, only when something is shown) ---------------- */
	var cssDone = false;
	function css() {
		if (cssDone) return;
		cssDone = true;
		var s = D.createElement('style');
		s.textContent =
			'.psb-sheet{position:fixed;z-index:60;inset-inline:12px;bottom:calc(var(--tabbar-offset,0px) + 12px + env(safe-area-inset-bottom,0px));max-width:30rem;margin-inline:auto;background:#fff;color:#0f172a;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 20px 50px -12px rgba(11,18,32,.35);padding:16px;font:inherit;direction:rtl;transform:translateY(130%);opacity:0;transition:transform .35s cubic-bezier(.22,1,.36,1),opacity .25s}' +
			'.psb-sheet.is-open{transform:none;opacity:1}' +
			'.psb-sheet__head{display:flex;gap:12px;align-items:center;padding-inline-end:30px}' +
			'.psb-sheet__head img{width:52px;height:52px;border-radius:13px;flex:none;box-shadow:0 0 0 1px #e2e8f0}' +
			'.psb-sheet h2{font-size:1rem;font-weight:700;margin:0 0 2px;color:#0B1220}' +
			'.psb-sheet p{font-size:.85rem;line-height:1.8;margin:0;color:#475569}' +
			'.psb-sheet ol{margin:12px 0 0;padding:0 1.2rem 0 0;list-style:persian;font-size:.875rem;line-height:2.1;color:#1e293b}' +
			'.psb-sheet ol svg{display:inline-block;vertical-align:-5px;margin:0 4px;color:#1A3DA3}' +
			'.psb-sheet__row{display:flex;gap:8px;margin-top:14px}' +
			'.psb-sheet__row button{flex:1;min-height:46px;border-radius:12px;font:inherit;font-weight:700;cursor:pointer;border:1px solid #cbd5e1;background:#fff;color:#334155}' +
			'.psb-sheet__row .is-primary{background:#1A3DA3;border-color:#1A3DA3;color:#fff}' +
			'.psb-sheet button:focus-visible{outline:3px solid #F59E0B;outline-offset:2px}' +
			'.psb-sheet__x{position:absolute;top:8px;inset-inline-end:8px;width:36px;height:36px;border:0;background:none;color:#94a3b8;font-size:22px;line-height:1;cursor:pointer;border-radius:10px}' +
			'.psb-sheet__ill{display:block;margin:10px auto 0;width:150px;height:auto}' +
			'.psb-toast{position:fixed;z-index:61;inset-inline:16px;bottom:calc(var(--tabbar-offset,0px) + 16px + env(safe-area-inset-bottom,0px));margin-inline:auto;max-width:24rem;display:flex;gap:10px;align-items:center;justify-content:space-between;background:#0B1220;color:#fff;border-radius:14px;padding:10px 12px 10px 10px;box-shadow:0 12px 30px -10px rgba(0,0,0,.45);font:inherit;font-size:.9rem;direction:rtl}' +
			'.psb-toast button{min-height:40px;padding:0 14px;border:0;border-radius:10px;background:#F59E0B;color:#0B1220;font:inherit;font-weight:700;cursor:pointer}' +
			'@media (prefers-reduced-motion:reduce){.psb-sheet{transition:none}}' +
			'@media (display-mode:standalone){.psb-sheet{display:none}}';
		D.head.appendChild(s);
	}

	function el(tag, attrs, html) {
		var n = D.createElement(tag);
		for (var k in attrs) if (Object.prototype.hasOwnProperty.call(attrs, k)) n.setAttribute(k, attrs[k]);
		if (html) n.innerHTML = html; // static strings only (no user data)
		return n;
	}

	/* ---------------- update toast ---------------- */
	function toast(text, action, onAction) {
		css();
		var old = D.querySelector('.psb-toast');
		if (old) old.remove();
		var t = el('div', { class: 'psb-toast', role: 'status', 'aria-live': 'polite' });
		var span = el('span');
		span.textContent = text;
		t.appendChild(span);
		if (action) {
			var b = el('button', { type: 'button' });
			b.textContent = action;
			b.addEventListener('click', onAction);
			t.appendChild(b);
		} else {
			setTimeout(function () {
				t.remove();
			}, 6000);
		}
		D.body.appendChild(t);
	}

	/* ---------------- service worker ---------------- */
	if ('serviceWorker' in nav && C.sw && (location.protocol === 'https:' || location.hostname === 'localhost')) {
		var hadController = !!nav.serviceWorker.controller;
		var reloading = false;
		nav.serviceWorker.addEventListener('controllerchange', function () {
			if (!hadController || reloading) {
				hadController = true;
				return;
			}
			toast('نسخه جدید آماده است', 'به‌روزرسانی', function () {
				reloading = true;
				location.reload();
			});
		});
		var reg = function () {
			nav.serviceWorker
				.register(C.sw, { scope: C.scope || '/' })
				.then(function (r) {
					// Long-lived installed app: look for a new version when it comes back to the foreground.
					D.addEventListener('visibilitychange', function () {
						if (D.visibilityState === 'visible') r.update().catch(function () {});
					});
				})
				.catch(function () {});
		};
		if (D.readyState === 'complete') reg();
		else addEventListener('load', reg);
	}

	/* ---------------- install sheet ---------------- */
	var DISMISS = 'psb_pwa_dismiss';
	var PV = 'psb_pwa_pv';
	var path = location.pathname;
	var quiet = (C.quiet || []).some(function (p) {
		return path.indexOf(p) === 0;
	});
	var dismissed = Number(store.get(DISMISS) || 0) > Date.now();
	var pv = Number(store.get(PV) || 0) + 1;
	store.set(PV, String(pv));

	var shareIcon = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M8 7l4-4 4 4"/><path d="M5 11v9h14v-9"/></svg>';
	var addIcon = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="4"/><path d="M12 8v8M8 12h8"/></svg>';
	var phoneIll =
		'<svg class="psb-sheet__ill" viewBox="0 0 150 96" aria-hidden="true"><rect x="45" y="2" width="60" height="92" rx="10" fill="#F1F5F9" stroke="#CBD5E1" stroke-width="2"/><rect x="51" y="12" width="48" height="58" rx="3" fill="#fff"/><rect x="51" y="76" width="48" height="12" rx="3" fill="#E2E8F0"/><g fill="none" stroke="#1A3DA3" stroke-width="1.8" stroke-linecap="round"><path d="M75 79v6M72 81.5l3-3 3 3"/></g><path d="M118 40c4 18-6 32-30 40" fill="none" stroke="#F59E0B" stroke-width="2" stroke-dasharray="3 4"/><path d="M92 76l-5 4 6 2" fill="none" stroke="#F59E0B" stroke-width="2" stroke-linecap="round"/><rect x="58" y="20" width="14" height="14" rx="4" fill="#FD0101"/><rect x="78" y="20" width="14" height="14" rx="4" fill="#E2E8F0"/><rect x="58" y="40" width="14" height="14" rx="4" fill="#E2E8F0"/></svg>';

	function closeSheet(remember) {
		if (!sheet) return;
		var s = sheet;
		sheet = null;
		s.classList.remove('is-open');
		setTimeout(function () {
			s.remove();
		}, 400);
		if (remember) {
			store.set(DISMISS, String(Date.now() + 30 * 864e5));
			dismissed = true;
			stat('dismissed');
		}
	}

	function openSheet(force) {
		if (standalone || sheet || (!force && (dismissed || quiet || !C.prompt))) return;
		var ios = !deferred && isIOS && (C.ios || force);
		if (!deferred && !ios) {
			if (force) toast('از منوی مرورگر گزینه «نصب برنامه» یا «Add to Home screen» را بزنید.');
			return;
		}
		css();
		sheet = el('div', { class: 'psb-sheet', role: 'dialog', 'aria-modal': 'false', 'aria-labelledby': 'psb-sheet-t' });
		var head = el('div', { class: 'psb-sheet__head' });
		var img = el('img', { src: C.icon || '', alt: '', width: '52', height: '52' });
		var txt = el('div', {}, '<h2 id="psb-sheet-t">نصب اپ پارس سوئیچ بورد</h2><p>دسترسی سریع از صفحه اصلی گوشی، تمام‌صفحه و سریع‌تر؛ بدون نیاز به فروشگاه برنامه.</p>');
		head.appendChild(img);
		head.appendChild(txt);
		sheet.appendChild(head);
		var x = el('button', { type: 'button', class: 'psb-sheet__x', 'aria-label': 'بستن' });
		x.textContent = '×';
		x.addEventListener('click', function () {
			closeSheet(true);
		});
		sheet.appendChild(x);
		var row = el('div', { class: 'psb-sheet__row' });
		if (ios) {
			sheet.appendChild(el('ol', {}, '<li>در نوار مرورگر روی دکمه «اشتراک‌گذاری»' + shareIcon + 'بزنید.</li><li>گزینه «Add to Home Screen» (افزودن به صفحه اصلی)' + addIcon + 'را انتخاب کنید.</li><li>در بالای صفحه روی «Add» بزنید.</li>'));
			sheet.appendChild(el('div', {}, phoneIll));
			var ok = el('button', { type: 'button', class: 'is-primary' });
			ok.textContent = 'متوجه شدم';
			ok.addEventListener('click', function () {
				closeSheet(true);
			});
			row.appendChild(ok);
			stat('ios');
		} else {
			var later = el('button', { type: 'button' });
			later.textContent = 'بعداً';
			later.addEventListener('click', function () {
				closeSheet(true);
			});
			var inst = el('button', { type: 'button', class: 'is-primary' });
			inst.textContent = 'نصب اپ';
			inst.addEventListener('click', function () {
				var p = deferred;
				deferred = null;
				closeSheet(false);
				if (!p) return;
				p.prompt();
				p.userChoice
					.then(function (c) {
						if (c && c.outcome === 'accepted') stat('accepted');
						else {
							store.set(DISMISS, String(Date.now() + 30 * 864e5));
							stat('dismissed');
						}
					})
					.catch(function () {});
			});
			row.appendChild(later);
			row.appendChild(inst);
		}
		sheet.appendChild(row);
		D.body.appendChild(sheet);
		requestAnimationFrame(function () {
			requestAnimationFrame(function () {
				if (sheet) sheet.classList.add('is-open');
			});
		});
	}

	function maybeShow() {
		if (engaged) openSheet(false);
	}

	addEventListener('beforeinstallprompt', function (e) {
		e.preventDefault();
		deferred = e;
		stat('prompt');
		maybeShow();
	});
	addEventListener('appinstalled', function () {
		deferred = null;
		closeSheet(false);
		store.set(DISMISS, String(Date.now() + 365 * 864e5));
		stat('installed');
	});
	D.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && sheet) closeSheet(true);
	});
	D.addEventListener('click', function (e) {
		var t = e.target && e.target.closest ? e.target.closest('[data-install-hint]') : null;
		if (!t) return;
		e.preventDefault();
		openSheet(true);
	});

	if (!standalone && !dismissed && !quiet && C.prompt) {
		if (pv >= 2) {
			engaged = true;
			setTimeout(maybeShow, 4000); // let the page settle first
		} else {
			setTimeout(function () {
				engaged = true;
				maybeShow();
			}, (C.delay || 30) * 1000);
		}
		if (isIOS && C.ios) {
			// iOS has no beforeinstallprompt: show instructions once engaged.
			var tick = setInterval(function () {
				if (engaged) {
					clearInterval(tick);
					openSheet(false);
				}
			}, 1000);
		}
	}

	window.psbInstall = function () {
		openSheet(true);
	};
})();
