/**
 * PSB Panel — vanilla JS app (no build step). Boot data: #op-boot (router.php). API: psb-panel/v1 (rest.php).
 * All dynamic text is inserted with textContent; URLs come from the server (esc_url_raw) or are built from digits.
 */
(function () {
	'use strict';
	var D = document;
	var B = JSON.parse((D.getElementById('op-boot') || {}).textContent || '{}');
	var nonce = B.nonce;
	var app = D.getElementById('op-app');
	var main, title, tabsEl, badges = {};

	/* ---------------- helpers ---------------- */
	function h(tag, attrs) {
		var el = D.createElement(tag);
		if (attrs) {
			for (var k in attrs) {
				var v = attrs[k];
				if (v === null || v === undefined || v === false) continue;
				if (k.slice(0, 2) === 'on' && typeof v === 'function') el.addEventListener(k.slice(2), v);
				else if (k === 'class') el.className = v;
				else if (k === 'text') el.textContent = v;
				else if (k === 'value') el.value = v;
				else el.setAttribute(k, v === true ? '' : String(v));
			}
		}
		for (var i = 2; i < arguments.length; i++) add(el, arguments[i]);
		return el;
	}
	function add(el, kid) {
		if (kid === null || kid === undefined || kid === false || kid === '') return;
		if (Array.isArray(kid)) kid.forEach(function (k) { add(el, k); });
		else el.appendChild(kid instanceof Node ? kid : D.createTextNode(String(kid)));
	}
	function fill(el) {
		while (el.firstChild) el.removeChild(el.firstChild);
		for (var i = 1; i < arguments.length; i++) add(el, arguments[i]);
		return el;
	}
	var ICONS = {
		home: 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z',
		req: 'M9 4h6l1 2h3v15H5V6h3zM9 11h6M9 15h4',
		content: 'M4 4h16v16H4zM8 8h8M8 12h8M8 16h5',
		forms: 'M4 4h16v12H5.2L4 17.2zM8 9h8M8 12h5',
		settings: 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19 12a7 7 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7 7 0 0 0-2-1.2L14 3h-4l-.5 2.6a7 7 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.6a7 7 0 0 0 0 2.4l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 2 1.2L10 21h4l.5-2.6a7 7 0 0 0 2-1.2l2.4 1 2-3.4-2-1.6c.1-.4.1-.8.1-1.2z',
		refresh: 'M21 12a9 9 0 1 1-2.6-6.4M21 3v6h-6',
		back: 'M9 6l6 6-6 6',
		phone: 'M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z',
		chat: 'M21 11.5a8.4 8.4 0 0 1-12.4 7.4L3 21l2.1-5.6A8.4 8.4 0 1 1 21 11.5z',
		send: 'M22 2 11 13M22 2l-7 20-4-9-9-4z',
		mail: 'M4 5h16v14H4zM4 7l8 6 8-6',
		sms: 'M4 4h16v12H8l-4 4zM8 9h8M8 12h5',
		file: 'M14 3H6v18h12V7zM14 3v4h4',
		ext: 'M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5',
	};
	function icon(name, size) {
		var s = D.createElementNS('http://www.w3.org/2000/svg', 'svg');
		s.setAttribute('width', size || 22);
		s.setAttribute('height', size || 22);
		s.setAttribute('viewBox', '0 0 24 24');
		s.setAttribute('fill', 'none');
		s.setAttribute('stroke', 'currentColor');
		s.setAttribute('stroke-width', '2');
		s.setAttribute('stroke-linecap', 'round');
		s.setAttribute('stroke-linejoin', 'round');
		s.setAttribute('aria-hidden', 'true');
		var p = D.createElementNS('http://www.w3.org/2000/svg', 'path');
		p.setAttribute('d', ICONS[name] || ICONS.home);
		s.appendChild(p);
		return s;
	}
	var NF = new Intl.NumberFormat('fa-IR');
	function num(n) { return NF.format(Number(n) || 0); }
	function fa(s) { return String(s == null ? '' : s).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }
	var DF;
	try { DF = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }); } catch (e) { DF = new Intl.DateTimeFormat('fa-IR'); }
	function date(ts) { return ts ? DF.format(new Date(ts * 1000)) : '—'; }
	var RF = Intl.RelativeTimeFormat ? new Intl.RelativeTimeFormat('fa', { numeric: 'auto' }) : null;
	function ago(ts) {
		if (!ts) return '—';
		var d = ts - Date.now() / 1000, a = Math.abs(d);
		if (!RF) return date(ts);
		if (a < 60) return 'همین حالا';
		if (a < 3600) return RF.format(Math.round(d / 60), 'minute');
		if (a < 86400) return RF.format(Math.round(d / 3600), 'hour');
		if (a < 86400 * 30) return RF.format(Math.round(d / 86400), 'day');
		return date(ts);
	}
	function size(b) { return b >= 1048576 ? fa((b / 1048576).toFixed(1)) + ' مگابایت' : num(Math.max(1, Math.round(b / 1024))) + ' کیلوبایت'; }
	var toastTimer;
	function toast(msg, err) {
		var old = D.querySelector('.op-toast');
		if (old) old.remove();
		var t = h('div', { class: 'op-toast' + (err ? ' is-error' : ''), role: err ? 'alert' : 'status', text: msg });
		D.body.appendChild(t);
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { t.remove(); }, 3500);
	}
	function pill(st, map) {
		var s = (map && map[st]) || { label: st, color: '#64748b' };
		return h('span', { class: 'op-pill', style: 'background:' + (/^#[0-9a-f]{3,8}$/i.test(s.color) ? s.color : '#64748b'), text: s.label });
	}
	function card(head, body, extra) { return h('section', { class: 'op-card' }, head ? h('h2', null, head, extra || null) : null, body); }
	function stat(n, label) { return h('div', { class: 'op-stat' }, h('b', { text: num(n) }), h('span', { text: label })); }
	function empty(msg) { return h('p', { class: 'op-empty', text: msg }); }
	function skel(n) { var a = []; for (var i = 0; i < (n || 3); i++) a.push(h('div', { class: 'op-skel' })); return a; }
	function ext(href, label) { return h('a', { class: 'op-btn op-btn--sm', href: href, target: '_blank', rel: 'noopener' }, label, icon('ext', 16)); }

	/* ---------------- API ---------------- */
	function refreshNonce() {
		return fetch(B.nonceUrl, { credentials: 'same-origin', cache: 'no-store' }).then(function (r) {
			if (r.status === 401) { location.href = B.base; throw new Error('نشست شما منقضی شده است.'); }
			return r.json();
		}).then(function (j) { nonce = j.nonce; });
	}
	function api(path, opt, retried) {
		opt = opt || {};
		var url = B.rest + path, q = [];
		if (opt.query) for (var k in opt.query) if (opt.query[k] !== '' && opt.query[k] != null) q.push(encodeURIComponent(k) + '=' + encodeURIComponent(opt.query[k]));
		if (q.length) url += (url.indexOf('?') > -1 ? '&' : '?') + q.join('&');
		var init = { method: opt.method || 'GET', credentials: 'same-origin', cache: 'no-store', headers: { 'X-WP-Nonce': nonce, Accept: 'application/json' } };
		if (opt.body) { init.headers['Content-Type'] = 'application/json'; init.body = JSON.stringify(opt.body); }
		return fetch(url, init).then(function (res) {
			return res.json().catch(function () { return null; }).then(function (data) {
				if (res.ok) return data;
				if (!retried && (res.status === 401 || res.status === 403 || (data && data.code === 'rest_cookie_invalid_nonce'))) {
					return refreshNonce().then(function () { return api(path, opt, true); });
				}
				throw new Error((data && data.message) || 'خطای سرور (' + res.status + ')');
			});
		}, function () { throw new Error(navigator.onLine ? 'ارتباط با سرور برقرار نشد.' : 'اینترنت قطع است.'); });
	}
	function act(btn, promise, okMsg) {
		btn.disabled = true;
		return promise.then(function (r) { if (okMsg) toast(okMsg); return r; }, function (e) { toast(e.message, true); throw e; }).finally(function () { btn.disabled = false; });
	}

	/* ---------------- shell ---------------- */
	var TABS = [
		{ id: '', label: 'داشبورد', icon: 'home' },
		{ id: 'requests', label: 'درخواست‌ها', icon: 'req', cap: 'rfq', badge: 'rfq' },
		{ id: 'content', label: 'محتوا', icon: 'content', badge: 'comments' },
		{ id: 'forms', label: 'فرم‌ها', icon: 'forms', cap: 'forms', badge: 'forms' },
		{ id: 'settings', label: 'تنظیمات', icon: 'settings' },
	].filter(function (t) { return !t.cap || (B.caps && B.caps[t.cap]); });

	function frame() {
		var refresh = h('button', { class: 'op-icon-btn', type: 'button', 'aria-label': 'تازه‌سازی', onclick: function () { render(); } }, icon('refresh'));
		title = h('h1', { text: 'پنل مدیریت' });
		var top = h('header', { class: 'op-top' }, h('span', { class: 'op-avatar', 'aria-hidden': 'true', text: String(B.user || 'P').trim().charAt(0) }), title, refresh);
		main = h('main', { class: 'op-main', id: 'op-main', tabindex: '-1' });
		tabsEl = h('nav', { class: 'op-tabs', 'aria-label': 'بخش‌های پنل' });
		fill(app, top, h('div', { class: 'op-ptr', 'aria-hidden': 'true' }, h('span', null, icon('refresh', 20))), main, tabsEl);
		app.removeAttribute('aria-busy');
		pullToRefresh();
	}
	function drawTabs(cur) {
		fill(tabsEl, TABS.map(function (t) {
			var n = t.badge ? badges[t.badge] : 0;
			return h('a', { class: 'op-tab', href: B.base + (t.id ? t.id + '/' : ''), 'aria-current': cur === t.id ? 'page' : null, 'data-nav': '' },
				icon(t.icon), h('span', { text: t.label }), n ? h('span', { class: 'op-dot', text: n > 99 ? '+۹۹' : num(n) }) : null);
		}));
	}
	function route() {
		var p = location.pathname.indexOf(B.base) === 0 ? location.pathname.slice(B.base.length) : '';
		return p.replace(/\/+$/, '').split('/').filter(Boolean);
	}
	function go(path) { history.pushState(null, '', B.base + path); render(); window.scrollTo(0, 0); }
	D.addEventListener('click', function (e) {
		var a = e.target.closest && e.target.closest('a[data-nav]');
		if (!a || e.metaKey || e.ctrlKey) return;
		e.preventDefault();
		history.pushState(null, '', a.getAttribute('href'));
		render();
		window.scrollTo(0, 0);
	});
	window.addEventListener('popstate', function () { render(); });

	function render() {
		var r = route(), s = r[0] || '';
		drawTabs(s === 'requests' || s === 'content' || s === 'forms' || s === 'settings' ? s : '');
		var screens = { '': home, requests: r[1] ? requestDetail : requests, content: content, forms: forms, settings: settings };
		(screens[s] || home)(r[1]);
		main.focus({ preventScroll: true });
	}
	function setTitle(t) { title.textContent = t; D.title = t + ' — پنل PSB'; }
	function fail(e) { fill(main, card(null, [h('p', { class: 'op-alert', text: e.message }), h('button', { class: 'op-btn', type: 'button', onclick: render, text: 'تلاش دوباره' })])); }

	function pullToRefresh() {
		var y0 = null, dy = 0, ind = D.querySelector('.op-ptr');
		window.addEventListener('touchstart', function (e) { y0 = window.scrollY <= 0 ? e.touches[0].clientY : null; dy = 0; }, { passive: true });
		window.addEventListener('touchmove', function (e) {
			if (y0 === null) return;
			dy = Math.min(120, e.touches[0].clientY - y0);
			if (dy > 0) ind.style.transform = 'translateY(' + (dy / 2 - 40) + 'px) rotate(' + dy * 3 + 'deg)';
		}, { passive: true });
		window.addEventListener('touchend', function () {
			if (y0 !== null && dy > 80) render();
			y0 = null;
			ind.style.transform = '';
		});
	}

	/* ---------------- screens ---------------- */
	function home() {
		setTitle('داشبورد');
		fill(main, skel(4));
		api('dashboard').then(function (d) {
			badges = d.badges || {};
			drawTabs('');
			var out = [];
			if (d.rfq) {
				var chips = Object.keys(d.rfq.labels || {}).map(function (k) {
					return h('a', { class: 'op-chip', href: B.base + 'requests/?status=' + k, 'data-nav': '', style: 'display:inline-flex;align-items:center;text-decoration:none;color:inherit' }, d.rfq.labels[k].label + ' ' + num((d.rfq.status || {})[k]));
				});
				var latest = (d.rfq.latest || []).map(rfqItem.bind(null, d.rfq.labels));
				out.push(card(['درخواست‌های استعلام'], [
					h('div', { class: 'op-grid' }, stat(d.rfq.today, 'امروز'), stat(d.rfq.week, '۷ روز'), stat(d.rfq.month, '۳۰ روز')),
					h('div', { class: 'op-chips', style: 'margin-top:10px' }, chips),
					latest.length ? h('ul', { class: 'op-list' }, latest) : empty('هنوز درخواستی ثبت نشده است.'),
				], d.rfq.new ? h('small', { text: num(d.rfq.new) + ' جدید' }) : null));
			}
			if (d.forms) out.push(card('فرم‌های تماس', h('div', { class: 'op-grid' }, stat(d.forms.unread, 'خوانده‌نشده'), stat(d.forms.today, 'امروز'), stat(d.forms.week, '۷ روز'))));
			var c = d.content || {};
			out.push(card('محتوا', [
				h('div', { class: 'op-grid' }, stat(c.drafts, 'پیش‌نویس'), stat(c.pending, 'در انتظار بررسی'), stat(c.comments, 'دیدگاه در انتظار')),
				(c.scheduled || []).length ? h('ul', { class: 'op-list', style: 'margin-top:8px' }, c.scheduled.map(function (p) {
					return h('li', { class: 'op-row' }, h('span', { class: 'op-grow op-title', text: p.title }), h('span', { class: 'op-small op-muted', text: date(p.ts) }));
				})) : h('p', { class: 'op-small op-muted', text: 'نوشته زمان‌بندی‌شده‌ای در صف نیست.' }),
			]));
			var x = d.health || {};
			var rows = [
				['PHP', fa(x.php)], ['وردپرس', fa(x.wp)],
				['کش صفحه', x.cache && x.cache.plugin ? (x.cache.wpc ? 'فعال' : 'افزونه فعال؛ WP_CACHE خاموش') : 'غیرفعال', x.cache && x.cache.plugin && x.cache.wpc],
				['کران واقعی سرور', x.cron ? ago(x.cron) : 'ثبت نشده', x.cronOk],
				['کش اشیا', x.objectCache ? 'فعال' : 'ندارد'],
			];
			if (x.fatal !== undefined) rows.push(['آخرین خطای مرگبار', x.fatal ? (x.fatal.ts ? ago(x.fatal.ts) : 'در انتهای لاگ نیست') : 'لاگ در دسترس نیست', x.fatal ? !x.fatal.ts || Date.now() / 1000 - x.fatal.ts > 86400 : null]);
			out.push(card('سلامت سایت', [
				h('dl', { class: 'op-dl' }, rows.map(function (r) {
					return [h('dt', { text: r[0] }), h('dd', { class: r[2] === true ? 'op-ok' : r[2] === false ? 'op-warn' : '', text: r[1] })];
				})),
				x.fatal && x.fatal.msg ? h('p', { class: 'op-small op-muted ltr', style: 'margin:8px 0 0', text: x.fatal.msg }) : null,
			]));
			if (d.ai) {
				out.push(card(['خزنده‌های هوش مصنوعی'], [
					h('div', { class: 'op-grid' }, stat(d.ai.today, 'امروز'), stat(d.ai.week, '۷ روز'), stat(d.ai.month, '۳۰ روز')),
					h('ul', { class: 'op-list', style: 'margin-top:8px' }, (d.ai.bots || []).map(function (b) {
						return h('li', { class: 'op-row' }, h('span', { class: 'op-grow ltr', style: 'text-align:start', text: b.name }), h('span', { class: 'op-small op-muted', text: ago(b.last) }));
					})),
				], d.ai.sampled ? h('small', { text: 'نمونه‌برداری‌شده' }) : null));
			}
			if (d.pwa) out.push(card('نصب اپ سایت', h('div', { class: 'op-grid' }, stat(d.pwa.installed + d.pwa.accepted, 'نصب'), stat(d.pwa.prompt, 'پیشنهاد'), stat(d.pwa.standalone, 'باز شدن اپ'))));
			fill(main, out);
		}, fail);
	}

	function rfqItem(labels, r) {
		return h('li', null, h('a', { href: B.base + 'requests/' + r.id + '/', 'data-nav': '', style: 'display:block;color:inherit;text-decoration:none' },
			h('div', { class: 'op-row' }, h('span', { class: 'op-grow op-title', text: (r.name || '—') + (r.company ? ' — ' + r.company : '') }), pill(r.status, labels)),
			h('div', { class: 'op-small op-muted', text: [r.summary, r.city].filter(Boolean).join(' · ') }),
			h('div', { class: 'op-row op-small op-muted' }, h('span', { class: 'ltr', text: fa(r.code) }), h('span', { class: 'op-grow' }), r.files ? h('span', { text: num(r.files) + ' فایل' }) : null, h('span', { text: ago(r.ts) }))));
	}

	var rq = { status: '', s: '', page: 1 };
	function requests() {
		setTitle('درخواست‌ها');
		var m = location.search.match(/status=([a-z_]+)/);
		if (m) { rq.status = m[1]; history.replaceState(null, '', B.base + 'requests/'); }
		var list = h('ul', { class: 'op-list' }), chips = h('div', { class: 'op-chips', role: 'group', 'aria-label': 'فیلتر وضعیت' });
		var more = h('button', { class: 'op-btn', type: 'button', hidden: true, text: 'موارد بیشتر', style: 'width:100%' });
		var timer, search = h('input', { class: 'op-input', type: 'search', placeholder: 'جستجو: کد پیگیری، موبایل، نام، شرکت', value: rq.s, 'aria-label': 'جستجو' });
		search.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { rq.s = search.value.trim(); load(true); }, 400); });
		var box = h('div', { class: 'op-card' }, list, more);
		fill(main, h('div', { style: 'margin-bottom:10px' }, search), chips, box);
		function load(reset) {
			if (reset) { rq.page = 1; fill(list, skel(3)); }
			api('rfq', { query: rq }).then(function (d) {
				fill(chips, [h('button', { class: 'op-chip', type: 'button', 'aria-pressed': rq.status === '' ? 'true' : 'false', onclick: function () { rq.status = ''; load(true); }, text: 'همه' })].concat(Object.keys(d.statuses).map(function (k) {
					return h('button', { class: 'op-chip', type: 'button', 'aria-pressed': rq.status === k ? 'true' : 'false', onclick: function () { rq.status = k; load(true); } }, d.statuses[k].label + ' ' + num((d.counts || {})[k]));
				})));
				if (reset) fill(list);
				add(list, d.items.map(rfqItem.bind(null, d.statuses)));
				if (!d.items.length && reset) fill(list, empty('درخواستی با این فیلتر پیدا نشد.'));
				more.hidden = rq.page >= d.pages;
			}, fail);
		}
		more.addEventListener('click', function () { rq.page++; load(false); });
		load(true);
	}

	function requestDetail(id) {
		setTitle('جزئیات درخواست');
		fill(main, skel(4));
		api('rfq/' + parseInt(id, 10)).then(draw, fail);
		function draw(d) {
			setTitle(fa(d.code) || 'درخواست');
			var tel = d.phone ? d.phone.replace(/[^\d+]/g, '') : '', intl = (d.intl || '').replace(/\D/g, ''), user = (d.username || '').replace(/[^\w.]/g, '');
			var acts = [];
			if (tel) acts.push(h('a', { href: 'tel:' + tel }, icon('phone'), 'تماس'), h('a', { href: 'sms:' + tel }, icon('sms'), 'پیامک'));
			if (intl) acts.push(h('a', { href: 'https://wa.me/' + intl, target: '_blank', rel: 'noopener' }, icon('chat'), 'واتساپ'));
			if (user || intl) acts.push(h('a', { href: 'https://t.me/' + (user || '+' + intl), target: '_blank', rel: 'noopener' }, icon('send'), 'تلگرام'));
			if (user) acts.push(h('a', { href: 'https://ble.ir/' + user, target: '_blank', rel: 'noopener' }, icon('chat'), 'بله'));
			if (d.email && /^[^\s@<>"]+@[^\s@<>"]+$/.test(d.email)) acts.push(h('a', { href: 'mailto:' + d.email }, icon('mail'), 'ایمیل'));
			var pick = h('div', { class: 'op-status-pick', role: 'group', 'aria-label': 'تغییر وضعیت' }, Object.keys(d.statuses).map(function (k) {
				var on = d.status === k, col = d.statuses[k].color;
				return h('button', { type: 'button', 'aria-pressed': on ? 'true' : 'false', style: 'border-color:' + col + (on ? ';background:' + col : ''), text: d.statuses[k].label, onclick: function (e) {
					if (on) return;
					act(e.currentTarget, api('rfq/' + d.id + '/status', { method: 'POST', body: { status: k } }), 'وضعیت به‌روز شد.').then(draw);
				} });
			}));
			var note = h('textarea', { class: 'op-input', placeholder: 'یادداشت داخلی (فقط همکاران می‌بینند)…', 'aria-label': 'یادداشت جدید', maxlength: 2000 });
			var saveNote = h('button', { class: 'op-btn op-btn--primary', type: 'button', text: 'ثبت یادداشت', onclick: function (e) {
				if (!note.value.trim()) return note.focus();
				act(e.currentTarget, api('rfq/' + d.id + '/note', { method: 'POST', body: { text: note.value } }), 'یادداشت ثبت شد.').then(draw);
			} });
			var out = [
				card(null, [
					h('div', { class: 'op-row' }, h('div', { class: 'op-grow' }, h('div', { class: 'op-title', text: (d.name || '—') + (d.company ? ' — ' + d.company : '') }), h('div', { class: 'op-small op-muted', text: date(d.ts) + (d.mode === 'quick' ? ' · فرم سریع' : '') })), pill(d.status, d.statuses)),
					d.phone ? h('p', { class: 'ltr', style: 'margin:8px 0;text-align:end;font-weight:700', text: fa(d.phone) }) : null,
					acts.length ? h('div', { class: 'op-actions' }, acts) : null,
				]),
				card('وضعیت', pick),
			];
			(d.items || []).forEach(function (it, i) {
				out.push(card((d.items.length > 1 ? fa(i + 1) + '. ' : '') + it.type + ' × ' + num(it.qty), it.specs.length ? h('dl', { class: 'op-dl' }, it.specs.map(function (s) { return [h('dt', { text: s.label }), h('dd', { dir: 'auto', text: s.value })]; })) : h('p', { class: 'op-small op-muted', text: 'مشخصات فنی وارد نشده است.' })));
			});
			(d.sections || []).forEach(function (s) {
				if (s.rows.length) out.push(card(s.title, h('dl', { class: 'op-dl' }, s.rows.map(function (r) { return [h('dt', { text: r.label }), h('dd', { dir: 'auto', text: r.value })]; }))));
			});
			if ((d.fileList || []).length) {
				out.push(card('فایل‌ها', h('ul', { class: 'op-list' }, d.fileList.map(function (f) {
					return h('li', null, h('a', { class: 'op-row', href: f.url, download: '', style: 'text-decoration:none;color:inherit' }, icon('file'), h('span', { class: 'op-grow op-title ltr', style: 'text-align:start', text: f.name }), f.size ? h('span', { class: 'op-small op-muted', text: size(f.size) }) : null));
				}))));
			}
			out.push(card('یادداشت‌ها و تاریخچه', [note, h('div', { class: 'op-btns', style: 'margin-top:8px' }, saveNote), (d.notes || []).map(function (n) {
				return h('div', { class: 'op-note' + (n.sys ? ' is-sys' : '') }, h('div', { class: 'op-small op-muted', text: n.by + ' — ' + date(n.ts) }), n.text);
			})]));
			if ((d.log || []).length) out.push(card('اعلان به تیم فروش', h('ul', { class: 'op-list' }, d.log.map(function (l) {
				return h('li', { class: 'op-row' }, h('span', { class: 'op-grow', text: l.channel }), h('span', { class: l.ok ? 'op-ok' : 'op-bad', text: l.ok ? 'ارسال شد' : 'ناموفق' }), h('span', { class: 'op-small op-muted', text: ago(l.ts) }));
			}))));
			var src = d.source || {}, srcRows = [['صفحه', src.page], ['ارجاع‌دهنده', src.referrer], ['محصول', src.product], ['کمپین', src.utm]].filter(function (r) { return r[1]; });
			if (srcRows.length) out.push(card('منبع', h('dl', { class: 'op-dl' }, srcRows.map(function (r) { return [h('dt', { text: r[0] }), h('dd', { class: 'ltr', style: 'text-align:start', text: r[1] })]; }))));
			out.push(h('div', { class: 'op-btns' }, h('a', { class: 'op-btn', href: B.base + 'requests/', 'data-nav': '' }, icon('back', 18), 'همه درخواست‌ها'), ext(d.edit, 'در پیشخوان')));
			fill(main, out);
		}
	}

	var cTab = 'drafts';
	function content() {
		setTitle('محتوا');
		var seg = h('div', { class: 'op-seg', role: 'group' }), box = h('div');
		fill(main, seg, box, h('div', { class: 'op-btns' }, B.links.newPost ? ext(B.links.newPost, 'نوشته جدید') : null, B.links.posts ? ext(B.links.posts, 'همه نوشته‌ها') : null));
		fill(box, skel(3));
		api('content').then(function (d) {
			function draw() {
				var tabs = [['drafts', 'پیش‌نویس‌ها', d.drafts.length], ['scheduled', 'زمان‌بندی‌شده', d.scheduled.length]];
				if (B.caps.comments) tabs.push(['comments', 'دیدگاه‌ها', d.comments.length]);
				fill(seg, tabs.map(function (t) { return h('button', { type: 'button', 'aria-pressed': cTab === t[0] ? 'true' : 'false', onclick: function () { cTab = t[0]; draw(); } }, t[1] + ' ' + num(t[2])); }));
				if (cTab === 'comments') return fill(box, d.comments.length ? d.comments.map(commentCard) : empty('دیدگاهی در انتظار تأیید نیست.'));
				var items = d[cTab] || [];
				fill(box, items.length ? items.map(postCard) : empty(cTab === 'drafts' ? 'پیش‌نویسی وجود ندارد.' : 'نوشته زمان‌بندی‌شده‌ای وجود ندارد.'));
			}
			function remove(list, id) { d[list] = d[list].filter(function (x) { return x.id !== id; }); }
			function postCard(p) {
				var when = h('input', { class: 'op-input ltr', type: 'datetime-local', 'aria-label': 'زمان انتشار', hidden: true });
				var save = h('button', { class: 'op-btn op-btn--sm op-btn--primary', type: 'button', hidden: true, text: 'ثبت زمان' });
				var btns = [];
				if (p.canPub) {
					btns.push(h('button', { class: 'op-btn op-btn--sm op-btn--cta', type: 'button', text: 'انتشار فوری', onclick: function (e) {
						if (!confirm('«' + p.title + '» همین حالا منتشر شود؟')) return;
						act(e.currentTarget, api('post/' + p.id, { method: 'POST', body: { op: 'publish' } }), 'منتشر شد.').then(function () { remove(cTab, p.id); draw(); });
					} }));
					btns.push(h('button', { class: 'op-btn op-btn--sm', type: 'button', text: 'زمان‌بندی', onclick: function () {
						var t = new Date(Date.now() + 864e5); t.setMinutes(0);
						if (!when.value) when.value = new Date(t.getTime() - t.getTimezoneOffset() * 6e4).toISOString().slice(0, 16);
						when.hidden = save.hidden = false; when.focus();
					} }));
					save.addEventListener('click', function (e) {
						act(e.currentTarget, api('post/' + p.id, { method: 'POST', body: { op: 'schedule', date: when.value } }), 'زمان‌بندی شد.').then(function (r) {
							remove('drafts', p.id); remove('scheduled', p.id); d.scheduled.push(r.item); d.scheduled.sort(function (a, b) { return a.ts - b.ts; }); draw();
						});
					});
				}
				if (p.edit) btns.push(ext(p.edit, 'ویرایش'));
				if (p.preview) btns.push(ext(p.preview, 'پیش‌نمایش'));
				return h('section', { class: 'op-card' },
					h('div', { class: 'op-row' }, h('span', { class: 'op-grow op-title', text: p.title }), h('span', { class: 'op-pill', style: 'background:' + (p.status === 'pending' ? '#d97706' : p.status === 'future' ? '#7c3aed' : '#64748b'), text: p.status === 'pending' ? 'در انتظار بررسی' : p.status === 'future' ? 'زمان‌بندی' : 'پیش‌نویس' })),
					h('div', { class: 'op-small op-muted', text: [p.type, p.author, p.status === 'future' ? 'انتشار: ' + date(p.ts) : 'ویرایش: ' + ago(p.mod)].filter(Boolean).join(' · ') }),
					h('div', { class: 'op-btns', style: 'margin-top:8px' }, btns), h('div', { class: 'op-btns', style: 'margin-top:8px' }, when, save));
			}
			function commentCard(c) {
				function mod(op, msg) {
					return function (e) { act(e.currentTarget, api('comment/' + c.id, { method: 'POST', body: { op: op } }), msg).then(function () { remove('comments', c.id); badges.comments = Math.max(0, (badges.comments || 1) - 1); drawTabs('content'); draw(); }); };
				}
				return h('section', { class: 'op-card' },
					h('div', { class: 'op-row' }, h('span', { class: 'op-grow op-title', text: c.author }), h('span', { class: 'op-small op-muted', text: ago(c.ts) })),
					h('div', { class: 'op-small op-muted' }, 'در: ', h('a', { href: c.link, target: '_blank', rel: 'noopener', text: c.post })),
					h('p', { style: 'margin:6px 0', text: c.text }),
					h('div', { class: 'op-btns' }, h('button', { class: 'op-btn op-btn--sm op-btn--primary', type: 'button', text: 'تأیید', onclick: mod('approve', 'تأیید شد.') }), h('button', { class: 'op-btn op-btn--sm', type: 'button', text: 'هرزنامه', onclick: mod('spam', 'به هرزنامه منتقل شد.') }), h('button', { class: 'op-btn op-btn--sm op-btn--danger', type: 'button', text: 'زباله‌دان', onclick: mod('trash', 'به زباله‌دان رفت.') })));
			}
			draw();
		}, fail);
	}

	var fForm = 0;
	function forms() {
		setTitle('پیام‌ها و فرم‌ها');
		fill(main, skel(3));
		api('forms', { query: { form: fForm } }).then(function (d) {
			var sel = h('select', { class: 'op-input', 'aria-label': 'انتخاب فرم', onchange: function () { fForm = parseInt(sel.value, 10) || 0; forms(); } }, h('option', { value: '0', text: 'همه فرم‌ها' }), d.forms.map(function (f) { return h('option', { value: f.id, selected: f.id === d.form ? true : null, text: f.title }); }));
			fill(main, h('div', { style: 'margin-bottom:10px' }, sel), d.items.length ? d.items.map(function (e) {
				return h('section', { class: 'op-card' },
					h('div', { class: 'op-row' }, e.read ? null : h('span', { class: 'op-unread', title: 'خوانده‌نشده' }), h('span', { class: 'op-grow op-title', text: e.form }), h('span', { class: 'op-small op-muted', text: ago(e.ts) })),
					h('dl', { class: 'op-dl', style: 'margin-top:6px' }, e.fields.map(function (f) {
						var v = f.type === 'phone' ? h('a', { href: 'tel:' + f.value.replace(/[^\d+]/g, ''), class: 'ltr', text: fa(f.value) }) : f.type === 'email' ? h('a', { href: 'mailto:' + f.value, text: f.value }) : f.value;
						return [h('dt', { text: f.label }), h('dd', null, v)];
					})),
					h('div', { class: 'op-btns', style: 'margin-top:8px' }, ext(e.link, 'در پیشخوان')));
			}) : empty('ورودی‌ای ثبت نشده است.'), B.links.forms ? h('div', { class: 'op-btns' }, ext(B.links.forms, 'همه ورودی‌ها')) : null);
		}, fail);
	}

	var installEvt = null;
	window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); installEvt = e; });
	function settings() {
		setTitle('تنظیمات سریع');
		var s = B.session || {};
		var sess = h('dl', { class: 'op-dl' }, h('dt', { text: 'کاربر' }), h('dd', { dir: 'auto', text: B.user }), h('dt', { text: 'ورود معتبر تا' }), h('dd', { dir: 'auto', text: date(s.expires) }), h('dt', { text: 'نشست‌های فعال' }), h('dd', { dir: 'auto', text: num(s.count) }));
		var out = [card('حساب و نشست‌ها', [sess,
			h('p', { class: 'op-small op-muted', text: s.remember ? 'با هر بار باز کردن پنل، ورود شما خودکار تمدید می‌شود (تا ' + num(B.days) + ' روز). اگر گوشی گم شد، از دستگاه دیگری «خروج از همه دستگاه‌ها» را بزنید.' : 'این ورود بدون «مرا به خاطر بسپار» انجام شده و کوتاه‌مدت است.' }),
			h('div', { class: 'op-btns' },
				h('button', { class: 'op-btn op-btn--sm', type: 'button', text: 'خروج از سایر دستگاه‌ها', onclick: function (e) {
					if (!confirm('همه دستگاه‌های دیگر از حساب شما خارج شوند؟')) return;
					act(e.currentTarget, api('sessions', { method: 'POST', body: { scope: 'others' } }), 'سایر دستگاه‌ها خارج شدند.').then(function (r) { if (r.session) B.session = r.session; settings(); });
				} }),
				h('button', { class: 'op-btn op-btn--sm op-btn--danger', type: 'button', text: 'خروج از همه دستگاه‌ها', onclick: function (e) {
					if (!confirm('از همه دستگاه‌ها، از جمله همین گوشی، خارج می‌شوید. ادامه می‌دهید؟')) return;
					act(e.currentTarget, api('sessions', { method: 'POST', body: { scope: 'all' } })).then(function () { location.href = B.base; });
				} }),
				h('a', { class: 'op-btn op-btn--sm', href: B.logout, text: 'خروج از این دستگاه' })),
		])];
		if (B.caps.cache) out.push(card('کش صفحات', [h('p', { class: 'op-small op-muted', text: 'پس از تغییر محتوا یا قالب، کش Cache Enabler را خالی کنید تا بازدیدکنندگان نسخه جدید را ببینند.' }), h('button', { class: 'op-btn op-btn--cta', type: 'button', text: 'پاک کردن کامل کش', onclick: function (e) {
			act(e.currentTarget, api('cache/clear', { method: 'POST' }), 'کش پاک شد.');
		} })]));
		var L = B.links || {}, links = [['rfqChannels', 'اتصال بات‌ها و اعلان‌ها (بله/تلگرام)'], ['rfqSettings', 'تنظیمات فرم استعلام'], ['rfqList', 'درخواست‌ها در پیشخوان'], ['ai', 'داشبورد هوش مصنوعی و llms.txt'], ['panel', 'تنظیمات پنل و اپ'], ['comments', 'دیدگاه‌ها در پیشخوان']].filter(function (l) { return L[l[0]]; });
		links.push(['admin', 'پیشخوان وردپرس'], ['site', 'مشاهده سایت']);
		out.push(card('میان‌بُرها', h('ul', { class: 'op-list' }, links.map(function (l) {
			return h('li', null, h('a', { class: 'op-row', href: L[l[0]] || B[l[0]], target: '_blank', rel: 'noopener', style: 'text-decoration:none;color:inherit' }, h('span', { class: 'op-grow', text: l[1] }), icon('ext', 18)));
		}))));
		var standalone = matchMedia('(display-mode: standalone)').matches || navigator.standalone;
		if (!standalone) {
			var ios = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
			out.push(card('نصب پنل روی گوشی', [
				h('p', { class: 'op-small op-muted', text: ios ? 'در Safari دکمه «اشتراک‌گذاری» را بزنید، «Add to Home Screen» را انتخاب کنید و سپس «Add». بعد از نصب یک بار داخل اپ وارد شوید.' : 'پنل را به‌صورت اپ جداگانه «PSB Panel» روی صفحه اصلی گوشی نصب کنید.' }),
				!ios ? h('button', { class: 'op-btn op-btn--primary', type: 'button', text: 'نصب اپ پنل', onclick: function () {
					if (installEvt) { installEvt.prompt(); installEvt = null; } else toast('از منوی مرورگر گزینه «نصب برنامه» یا «Add to Home screen» را بزنید.');
				} }) : null,
			]));
		}
		fill(main, out);
	}

	/* ---------------- start ---------------- */
	frame();
	render();
	api('me').then(function (d) { badges = d.badges || {}; B.session = d.session || B.session; drawTabs((route()[0]) || ''); }, function () {});
	if ('serviceWorker' in navigator && B.sw && location.protocol === 'https:') navigator.serviceWorker.register(B.sw, { scope: B.base }).catch(function () {});
	// Keep the nonce fresh for an app left open for hours.
	D.addEventListener('visibilitychange', function () { if (D.visibilityState === 'visible') refreshNonce().catch(function () {}); });
})();
