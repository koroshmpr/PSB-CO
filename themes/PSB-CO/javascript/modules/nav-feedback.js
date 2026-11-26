/**
 * Instant navigation feedback:
 * - 2px top progress bar on same-origin link activation (pointerdown/click/Enter)
 * - the mobile tab bar marks the tapped tab active immediately
 * - resets on bfcache restore (pageshow.persisted)
 */
let bar = null;
let timer = null;

const getBar = () => {
	if (!bar) {
		bar = document.createElement('div');
		bar.className = 'nav-progress';
		bar.setAttribute('aria-hidden', 'true');
		document.body.appendChild(bar);
	}
	return bar;
};

const reset = () => {
	clearTimeout(timer);
	if (bar) bar.className = 'nav-progress';
};

const isNavLink = (a, e) => {
	if (!a || !a.href || a.target === '_blank' || a.hasAttribute('download')) return false;
	if (e && (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button > 0)) return false;
	let url;
	try {
		url = new URL(a.href, location.href);
	} catch (err) {
		return false;
	}
	if (url.origin !== location.origin || !/^https?:$/.test(url.protocol)) return false;
	if (url.pathname === location.pathname && url.search === location.search && url.hash) return false; // in-page anchor
	if (/\/wp-admin\/|\/wp-login\.php|\.(pdf|zip|jpe?g|png|webp)$/i.test(url.pathname)) return false;
	return true;
};

const start = (a) => {
	const b = getBar();
	b.className = 'nav-progress is-active';
	// Safety: hide after 12s if nothing happened (e.g. download).
	clearTimeout(timer);
	timer = setTimeout(reset, 12000);

	const tab = a.closest('.app-tabbar') && a.classList.contains('app-tab') ? a : null;
	if (tab) {
		tab.parentElement.querySelectorAll('.app-tab[aria-current]').forEach((t) => t.removeAttribute('aria-current'));
		tab.setAttribute('aria-current', 'page');
	}
};

export function initNavFeedback() {
	document.addEventListener(
		'click',
		(e) => {
			const a = e.target.closest && e.target.closest('a');
			if (!e.defaultPrevented && isNavLink(a, e)) start(a);
		},
		{ capture: false }
	);
	window.addEventListener('pageshow', (e) => {
		if (e.persisted) reset();
	});
	window.addEventListener('pagehide', () => clearTimeout(timer));
}
