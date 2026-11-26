/**
 * PSB Panel service worker — served at /panel/sw.js (scope /panel/) with `self.__PANEL` prepended.
 * - Static panel assets (theme/pwa/*, theme/fonts/*): stale-while-revalidate.
 * - Navigations: always network (the shell carries a fresh nonce and private data); offline → small offline page.
 * - REST API, file downloads and everything else: untouched, never cached.
 */
/* global self, caches, fetch, Response, URL */
'use strict';

const CFG = self.__PANEL || { v: '0', scope: '/panel/', assets: [] };
const CACHE = 'psb-panel-' + CFG.v;

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (e) => {
	e.waitUntil(
		caches
			.keys()
			.then((keys) => Promise.all(keys.filter((k) => k.indexOf('psb-panel-') === 0 && k !== CACHE).map((k) => caches.delete(k))))
			.then(() => self.clients.claim()),
	);
});

const OFFLINE =
	'<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0B1220"><title>آفلاین</title>' +
	'<body style="margin:0;min-height:100vh;display:grid;place-items:center;background:#0B1220;color:#e2e8f0;font-family:Peyda,Tahoma,sans-serif;text-align:center;padding:24px">' +
	'<div><p style="font-size:18px;font-weight:700">اینترنت در دسترس نیست</p><p style="color:#94a3b8">پنل برای نمایش اطلاعات به‌روز به اتصال نیاز دارد.</p>' +
	'<button onclick="location.reload()" style="min-height:46px;padding:0 24px;border-radius:12px;border:0;background:#F59E0B;color:#0B1220;font:inherit;font-weight:700">تلاش دوباره</button></div></body></html>';

function isAsset(url) {
	return url.origin === self.location.origin && CFG.assets.some((p) => url.pathname.indexOf(p) === 0) && /\.(?:js|css|woff2|png|svg)$/i.test(url.pathname);
}

self.addEventListener('fetch', (e) => {
	const req = e.request;
	if (req.method !== 'GET') return;
	const url = new URL(req.url);

	if (req.mode === 'navigate') {
		if (url.pathname.indexOf(CFG.scope) !== 0 || /\/file\//.test(url.pathname)) return;
		e.respondWith(fetch(req).catch(() => new Response(OFFLINE, { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } })));
		return;
	}
	if (!isAsset(url)) return;
	e.respondWith(
		caches.open(CACHE).then(async (c) => {
			const hit = await c.match(req);
			const net = fetch(req)
				.then((res) => {
					if (res.ok && res.type === 'basic') c.put(req, res.clone());
					return res;
				})
				.catch(() => hit || Response.error());
			if (hit) {
				e.waitUntil(net.catch(() => null));
				return hit;
			}
			return net;
		}),
	);
});
