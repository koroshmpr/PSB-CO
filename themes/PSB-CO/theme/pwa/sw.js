/**
 * PSB site service worker — served at /sw.js by inc/pwa/routes.php with `self.__PSB` prepended.
 *
 * - install: precache the app shell (offline page, main CSS/JS, fonts, icons); skipWaiting.
 * - pages (navigations): network-first; after `timeout` ms a cached copy is shown while the network keeps
 *   updating it in the background; offline → cached copy → branded offline page.
 * - static files under /wp-content/ and /wp-includes/: stale-while-revalidate, size-limited caches.
 * - never touched: non-GET, other origins, wp-admin/login/account/cart/checkout/REST/admin-ajax/panel,
 *   previews and any URL with nonce-like params; responses marked `X-PSB-SW: no-store` (logged-in users,
 *   DONOTCACHEPAGE pages) or Cache-Control private/no-store are never stored.
 */
/* global self, caches, fetch, Response, URL, Request */
'use strict';

const CFG = self.__PSB || { v: '0', home: '/', offline: '/pwa-offline/', precache: [], bypass: [], params: [], static: ['/wp-content/', '/wp-includes/'], timeout: 3500, limits: { pages: 40, static: 80, img: 120 } };
const V = CFG.v;
const PRE = 'psb-pre-' + V;
const PAGES = 'psb-pages-' + V;
const STATIC = 'psb-static-' + V;
const IMG = 'psb-img-' + V;
const KEEP = [PRE, PAGES, STATIC, IMG];
const TRACKING = /^(source|utm_[a-z]+|fbclid|gclid|yclid|msclkid|_ga|mc_cid|mc_eid|ref)$/i;

self.addEventListener('install', (e) => {
	e.waitUntil(
		caches
			.open(PRE)
			.then((c) => Promise.all(CFG.precache.map((u) => c.add(new Request(u, { cache: 'reload', credentials: 'omit' })).catch(() => null))))
			.then(() => self.skipWaiting()),
	);
});

self.addEventListener('activate', (e) => {
	e.waitUntil(
		(async () => {
			const keys = await caches.keys();
			// Only our own caches; the owner panel (psb-panel-*) manages its own.
			await Promise.all(keys.filter((k) => k.indexOf('psb-') === 0 && k.indexOf('psb-panel-') !== 0 && KEEP.indexOf(k) === -1).map((k) => caches.delete(k)));
			if (self.registration.navigationPreload) {
				try {
					await self.registration.navigationPreload.enable();
				} catch (err) {
					/* not supported */
				}
			}
			await self.clients.claim();
		})(),
	);
});

function bypassed(url) {
	let p = url.pathname;
	try {
		p = decodeURIComponent(p);
	} catch (err) {
		/* keep raw */
	}
	for (const b of CFG.bypass) if (p.indexOf(b) === 0) return true;
	for (const k of url.searchParams.keys()) if (CFG.params.indexOf(k) !== -1 || /nonce/i.test(k)) return true;
	return false;
}

function storable(res) {
	if (!res || !res.ok || res.type !== 'basic' || res.redirected) return false;
	if (res.headers.get('X-PSB-SW')) return false;
	const cc = (res.headers.get('Cache-Control') || '').toLowerCase();
	return cc.indexOf('no-store') === -1 && cc.indexOf('private') === -1;
}

/** Cache key for a page: same URL without tracking params (so /?source=pwa and / share a copy). */
function pageKey(url) {
	const u = new URL(url.href);
	u.hash = '';
	for (const k of [...u.searchParams.keys()]) if (TRACKING.test(k)) u.searchParams.delete(k);
	return u.href;
}

async function trim(name, max) {
	const c = await caches.open(name);
	const keys = await c.keys();
	if (keys.length > max) await Promise.all(keys.slice(0, keys.length - max).map((k) => c.delete(k)));
}

async function offlineFallback(key) {
	const hit = (await caches.match(key, { cacheName: PAGES })) || (await caches.match(key));
	if (hit) return hit;
	const off = await caches.match(CFG.offline, { cacheName: PRE });
	return off || new Response('<!doctype html><meta charset="utf-8"><title>آفلاین</title><p dir="rtl" style="font-family:Tahoma;padding:2rem">اتصال اینترنت برقرار نیست.</p>', { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } });
}

function page(e, url) {
	const key = pageKey(url);
	const network = (async () => {
		const res = (await e.preloadResponse) || (await fetch(e.request));
		if (storable(res)) {
			const copy = res.clone();
			e.waitUntil(
				caches
					.open(PAGES)
					.then((c) => c.put(key, copy))
					.then(() => trim(PAGES, CFG.limits.pages))
					.catch(() => null),
			);
		}
		return res;
	})();
	e.waitUntil(network.catch(() => null));

	return new Promise((resolve) => {
		let done = false;
		const finish = (r) => {
			if (!done && r) {
				done = true;
				resolve(r);
			}
		};
		const timer = setTimeout(async () => {
			const hit = await caches.match(key, { cacheName: PAGES });
			if (hit) finish(hit); // slow network: show the saved copy, the fetch above refreshes it.
		}, CFG.timeout);
		network.then(
			(res) => {
				clearTimeout(timer);
				finish(res);
			},
			async () => {
				clearTimeout(timer);
				finish(await offlineFallback(key));
			},
		);
	});
}

function staleWhileRevalidate(e, cacheName, max) {
	return caches.open(cacheName).then(async (c) => {
		const hit = (await c.match(e.request)) || (await caches.match(e.request, { cacheName: PRE }));
		const net = fetch(e.request)
			.then((res) => {
				if (storable(res)) c.put(e.request, res.clone()).then(() => trim(cacheName, max)).catch(() => null);
				return res;
			})
			.catch(() => hit || Response.error());
		if (hit) {
			e.waitUntil(net.catch(() => null));
			return hit;
		}
		return net;
	});
}

/** Navigation we do not handle: still use the preload response so the page is not requested twice. */
function passThrough(e) {
	return Promise.resolve(e.preloadResponse)
		.catch(() => null)
		.then((r) => r || fetch(e.request));
}

self.addEventListener('fetch', (e) => {
	const req = e.request;
	if (req.method !== 'GET') return;
	const url = new URL(req.url);
	const nav = req.mode === 'navigate';
	if (url.origin !== self.location.origin || bypassed(url) || url.pathname.indexOf(CFG.home) !== 0) {
		if (nav) e.respondWith(passThrough(e));
		return;
	}
	if (nav) {
		e.respondWith(page(e, url));
		return;
	}
	if (req.headers.has('range')) return;

	const isStatic = CFG.static.some((p) => url.pathname.indexOf(p) === 0);
	if (!isStatic) return;
	if (/\.(?:css|js|mjs|woff2?|ttf|otf)$/i.test(url.pathname)) {
		e.respondWith(staleWhileRevalidate(e, STATIC, CFG.limits.static));
	} else if (/\.(?:png|jpe?g|webp|avif|gif|svg|ico)$/i.test(url.pathname)) {
		e.respondWith(staleWhileRevalidate(e, IMG, CFG.limits.img));
	}
});

self.addEventListener('message', (e) => {
	if (e.data === 'psb:skip-waiting') self.skipWaiting();
});
