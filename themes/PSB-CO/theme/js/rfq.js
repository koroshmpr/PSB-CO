/*! PSB RFQ — multi-step quote wizard + quick form. No dependencies. Config: window.psbRfq (inc/rfq/frontend.php). */
(function () {
	'use strict';
	var C = window.psbRfq;
	if (!C || !window.fetch || !document.querySelector) return;

	var FA = '۰۱۲۳۴۵۶۷۸۹', AR = '٠١٢٣٤٥٦٧٨٩', KEY = 'psb_rfq_v1';
	var NET = 'اتصال به سرور برقرار نشد. اینترنت را بررسی و دوباره تلاش کنید.';

	function latin(s) {
		return String(s == null ? '' : s).replace(/[۰-۹]/g, function (d) { return FA.indexOf(d); }).replace(/[٠-٩]/g, function (d) { return AR.indexOf(d); });
	}
	function fa(s) { return String(s).replace(/\d/g, function (d) { return FA[d]; }); }
	function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return '&#' + c.charCodeAt(0) + ';'; }); }
	/* Bidi: isolate Latin runs in Persian labels so «(SMDB / DB)», «(IP)», «25 kA» keep their order.
	   Runs with digits get the Latin face. Mirrors psb_rfq_bidi() in template-parts/rfq/helpers.php. */
	var TOK = '[A-Za-z0-9](?:[A-Za-z0-9.~\\-]*[A-Za-z0-9])?', RUN = new RegExp('(' + TOK + '(?:\\s*[\\/+]\\s*' + TOK + '|\\s+' + TOK + ')*)');
	function bidi(s) {
		return String(s == null ? '' : s).split(RUN).map(function (p, i) {
			return i % 2 && /[A-Za-z]/.test(p) ? '<bdi dir="ltr"' + (/\d/.test(p) ? ' class="rfq-lat"' : '') + '>' + esc(p) + '</bdi>' : esc(p);
		}).join('');
	}
	/* Same for <option> text, which cannot hold markup: LRI … PDI marks. */
	function bidiPlain(s) {
		return esc(String(s == null ? '' : s).split(RUN).map(function (p, i) { return i % 2 && /[A-Za-z]/.test(p) ? '⁦' + p + '⁩' : p; }).join(''));
	}
	/* A select showing a Latin code with digits (IP54, 25 kA, Form 2b) uses the Latin face so its digits stay Latin. */
	function latCode(t) { return /^[A-Za-z0-9 .\/~\-]+$/.test(t) && /\d/.test(t) && /[A-Za-z]/.test(t); }
	function noOpt(l) { return String(l).replace(/\s*\(اختیاری\)\s*/g, ' ').trim(); }
	function mobile(v) {
		v = latin(v).replace(/\D/g, '');
		if (v.indexOf('0098') === 0) v = '0' + v.slice(4);
		else if (v.indexOf('98') === 0 && v.length === 12) v = '0' + v.slice(2);
		else if (v.length === 10 && v.charAt(0) === '9') v = '0' + v;
		return /^09\d{9}$/.test(v) ? v : '';
	}
	function emailOk(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v); }
	function size(b) { return b >= 1048576 ? fa((b / 1048576).toFixed(1)) + ' مگابایت' : fa(Math.max(1, Math.round(b / 1024))) + ' کیلوبایت'; }
	var X = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>';
	var LTR = { 'contact.mobile': 1, 'contact.email': 1, 'contact.bale': 1, 'contact.username': 1 };
	function $(r, s) { return r.querySelector(s); }
	function $$(r, s) { return Array.prototype.slice.call(r.querySelectorAll(s)); }
	function wait(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }
	function store(v) {
		try {
			if (v === undefined) return JSON.parse(localStorage.getItem(KEY) || 'null');
			if (v === null) localStorage.removeItem(KEY); else localStorage.setItem(KEY, JSON.stringify(v));
		} catch (e) { return null; }
	}
	function empty(v) { return v == null || v === '' || (Array.isArray(v) && !v.length); }
	function optLabel(opts, v) {
		if (!opts) return v;
		if (Array.isArray(opts)) { for (var i = 0; i < opts.length; i++) if (opts[i][0] == v) return opts[i][1]; return v; }
		return opts[v] != null ? opts[v] : v;
	}
	function source(product) {
		var q = new URLSearchParams(location.search), utm = [];
		q.forEach(function (v, k) { if (k.indexOf('utm_') === 0) utm.push(k.slice(4) + '=' + v); });
		return { page: location.href.split('#')[0], referrer: document.referrer, product: product || '', utm: utm.join('&') };
	}

	/* ---------- session token (fetched at runtime: works on cached pages) ---------- */
	var sess = null, sessAt = 0, chEls = [];
	function session(force) {
		if (!force && sess && Date.now() - sessAt < 216e5) return Promise.resolve(sess);
		return fetch(C.rest + '/session', { credentials: 'omit', cache: 'no-store' })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				if (!j || !j.token) throw new Error('session');
				sess = j; sessAt = Date.now();
				chEls.forEach(function (el) { el.hidden = !j.challenge; var q = $(el, '[data-q]'); if (q) q.textContent = j.question || ''; });
				return j;
			});
	}
	function send(payload, tries) {
		tries = tries || 0;
		return session().then(function (s) {
			payload.token = s.token;
			return fetch(C.rest, { method: 'POST', credentials: 'omit', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
				.then(function (r) { return r.json().catch(function () { return { ok: false, message: 'پاسخ نامعتبر از سرور (' + r.status + ')' }; }); });
		}, function () { return { ok: false, message: NET }; }).then(function (j) {
			if (j.ok) { sess = null; session(true).catch(function () {}); return j; }
			if (j.code === 'too_fast' && tries < 3) return wait((j.retry_after || 2) * 1000 + 400).then(function () { return send(payload, tries + 1); });
			if (j.field === 'token' && tries < 1) { sess = null; return send(payload, tries + 1); }
			throw j;
		}, function (e) { throw e instanceof Error || !e || !e.message ? { message: NET } : e; });
	}
	function fieldErr(root, field, msg) {
		var w = $(root, '[data-f="' + field + '"]'), el = (w && $(w, '.rfq-err')) || $(root, '[data-err="' + field + '"]') || $(root, '[data-err="submit"]');
		if (!el) return null;
		el.textContent = msg;
		if (w) { w.classList.add('is-invalid'); $$(w, 'input,select,textarea').forEach(function (i) { i.setAttribute('aria-invalid', 'true'); }); }
		return w || el;
	}
	function clearErr(scope) {
		$$(scope, '.rfq-err').forEach(function (e) { e.textContent = ''; });
		$$(scope, '.is-invalid').forEach(function (w) { w.classList.remove('is-invalid'); $$(w, '[aria-invalid]').forEach(function (i) { i.removeAttribute('aria-invalid'); }); });
	}
	function focusFirst(el) {
		if (!el) return;
		var i = el.matches && el.matches('input,select,textarea,button') ? el : $(el, 'input,select,textarea,button');
		(i || el).focus && (i || el).focus();
		if (el.scrollIntoView) el.scrollIntoView({ block: 'center', behavior: 'smooth' });
	}
	function busy(btn, on, text) {
		if (!btn) return;
		if (on) { btn.dataset.t = btn.textContent; btn.textContent = text || 'در حال ارسال…'; btn.disabled = true; btn.setAttribute('aria-busy', 'true'); }
		else { btn.textContent = btn.dataset.t || btn.textContent; btn.disabled = false; btn.removeAttribute('aria-busy'); }
	}
	function success(root, j) {
		var box = $(root, '[data-success]'), set = function (s, v) { var e = $(box, s); if (e) e.textContent = v || ''; };
		set('[data-s-title]', j.title); set('[data-s-msg]', j.message); set('[data-s-code]', j.code); set('[data-s-time]', j.responseTime);
		var tel = $(box, '[data-s-tel]'), wa = $(box, '[data-s-wa]');
		if (tel && C.sales.tel) { tel.href = C.sales.tel; tel.hidden = false; }
		if (wa && C.sales.wa) { wa.href = C.sales.wa + '?text=' + encodeURIComponent('با سلام، کد پیگیری درخواست من: ' + j.code); wa.hidden = false; }
		box.hidden = false;
		box.focus();
		if (box.scrollIntoView) box.scrollIntoView({ block: 'start', behavior: 'smooth' });
	}

	/* ================================ Wizard ================================ */
	function Wizard(root) {
		var form = $(root, 'form'), uid = root.id, steps = C.steps.map(function (s) { return s[0]; });
		var st, uploading = [], saveT = 0, product = '';
		var blank = function () { return { items: [], project: {}, contact: {}, files: [], step: 0 }; };
		var saved = store();
		st = saved && Array.isArray(saved.items) ? saved : blank();
		if (saved && (saved.items.length || saved.contact.name)) $(root, '[data-restored]').hidden = false;

		// Deep link: ?product=<slug|id> or ?type=<key>
		var qs = new URLSearchParams(location.search), pk = qs.get('product'), tk = qs.get('type');
		if (pk && C.products[pk]) { tk = tk || C.products[pk][0]; product = C.products[pk][1]; }
		if (tk && C.types[tk] && !st.items.some(function (i) { return i.type === tk; })) { st.items.push({ type: tk, qty: 1, specs: {} }); st.step = 0; }
		if (product) st.product = product;

		function save() { clearTimeout(saveT); saveT = setTimeout(function () { store(st); }, 300); }
		function sec(k) { return $(form, '[data-step="' + k + '"]'); }
		function fieldVis(k) { return C.fields[k] ? C.fields[k].v : 'hide'; }
		function group(it) { return (C.types[it.type] || { g: 'other' }).g; }

		/* ----- step 1: cards + items ----- */
		function renderItems() {
			$$(form, '.rfq-card').forEach(function (b) {
				var first = null;
				st.items.some(function (i) { if (i.type === b.dataset.type) { first = i; return true; } return false; });
				b.setAttribute('aria-pressed', first ? 'true' : 'false');
				var tile = b.closest('.rfq-tile');
				if (!tile) return;
				tile.classList.toggle('is-on', !!first);
				var tq = $(tile, '[data-tqty]');
				if (tq) { tq.hidden = !first; if (first) $(tq, '[data-tqv]').textContent = fa(first.qty); }
			});
			var box = $(form, '[data-items]');
			if (!st.items.length) { box.innerHTML = ''; return; }
			box.innerHTML = '<h3 class="rfq-group">اقلام انتخاب‌شده (' + fa(st.items.length) + ')</h3>' + st.items.map(function (it, i) {
				var raw = (C.types[it.type] || { l: it.type }).l, l = esc(raw);
				return '<div class="rfq-item" data-i="' + i + '"><span class="rfq-item-n">' + fa(i + 1) + '</span><span class="rfq-item-t">' + bidi(raw) + '</span>' +
					'<span class="rfq-qty"><button type="button" data-q="-1" aria-label="کاهش تعداد ' + l + '">−</button>' +
					'<input type="text" inputmode="numeric" data-qty value="' + fa(it.qty) + '" aria-label="تعداد ' + l + '" maxlength="4">' +
					'<button type="button" data-q="1" aria-label="افزایش تعداد ' + l + '">+</button></span>' +
					'<button type="button" class="rfq-link rfq-dup" data-dup>+ ردیف مشابه</button>' +
					'<button type="button" class="rfq-item-del" data-del aria-label="حذف ' + l + '">' + X + '</button></div>';
			}).join('');
		}

		/* ----- step 2: specs per item ----- */
		function specField(f, i, g, val) {
			var id = uid + '-s' + i + '-' + f.k, name = 'items.' + i + '.' + f.k, o = f.g[g];
			var lab = bidi(f.l) + (f.u ? ' <span class="rfq-unit">(' + bidi(f.u) + ')</span>' : '') + (f.r ? ' <span class="rfq-req" aria-hidden="true">*</span>' : '');
			var wide = f.t === 'textarea' || f.t === 'chips' || f.t === 'radio';
			var h = '<div class="rfq-field' + (wide ? ' rfq-field--wide' : '') + '" data-f="' + name + '">';
			if (f.t === 'select') {
				h += '<label class="rfq-label" for="' + id + '">' + lab + '</label><select class="rfq-input' + (latCode(val ? optLabel(o, val) : '') ? ' rfq-input--lat' : '') + '" id="' + id + '" name="' + name + '"><option value="">انتخاب کنید</option>' +
					o.map(function (p) { return '<option value="' + esc(p[0]) + '"' + (val == p[0] ? ' selected' : '') + '>' + bidiPlain(p[1]) + '</option>'; }).join('') + '</select>';
			} else if (f.t === 'radio' || f.t === 'chips') {
				var type = f.t === 'radio' ? 'radio' : 'checkbox';
				h += '<fieldset class="rfq-fs"><legend class="rfq-label">' + lab + '</legend><div class="rfq-chips">' + o.map(function (p) {
					var on = Array.isArray(val) ? val.indexOf(p[0]) > -1 : val == p[0];
					return '<label class="rfq-chip"><input type="' + type + '" name="' + name + '" value="' + esc(p[0]) + '"' + (on ? ' checked' : '') + '><span>' + bidi(p[1]) + '</span></label>';
				}).join('') + '</div></fieldset>';
			} else if (f.t === 'textarea') {
				h += '<label class="rfq-label" for="' + id + '">' + lab + '</label><textarea class="rfq-input" rows="3" maxlength="1000" id="' + id + '" name="' + name + '" placeholder="' + esc(f.p) + '">' + esc(val) + '</textarea>';
			} else {
				h += '<label class="rfq-label" for="' + id + '">' + lab + '</label><input class="rfq-input" type="text" maxlength="120" id="' + id + '" name="' + name + '" value="' + esc(val) + '" placeholder="' + esc(f.p) + '"' + (f.u ? ' inputmode="decimal" dir="ltr"' : '') + '>';
			}
			return h + '<p class="rfq-err" aria-live="polite"></p></div>';
		}
		function renderSpecs() {
			var box = $(form, '[data-specs]');
			if (!box) return;
			box.innerHTML = st.items.map(function (it, i) {
				var t = C.types[it.type] || { l: it.type }, g = group(it);
				return '<fieldset class="rfq-spec"><legend><span class="rfq-spec-n">' + fa(i + 1) + '</span> ' + bidi(t.l) + ' <small>× ' + fa(it.qty) + '</small></legend>' +
					(i > 0 ? '<button type="button" class="rfq-link rfq-copy" data-copy="' + i + '">کپی مشخصات از ردیف قبل</button>' : '') +
					'<div class="rfq-grid">' + C.specs.filter(function (f) { return f.g[g]; }).map(function (f) { return specField(f, i, g, it.specs[f.k]); }).join('') + '</div></fieldset>';
			}).join('');
		}

		/* ----- step 3: files ----- */
		function renderFiles() {
			var ul = $(form, '[data-files]');
			if (!ul) return;
			ul.innerHTML = st.files.map(function (f, i) {
				return '<li class="rfq-file is-done"><span class="rfq-file-n"><bdi>' + esc(f.name) + '</bdi></span><span class="rfq-file-s">' + size(f.size) + '</span><button type="button" class="rfq-item-del" data-rmfile="' + i + '" aria-label="حذف ' + esc(f.name) + '">' + X + '</button></li>';
			}).join('') + uploading.map(function (u, i) {
				return '<li class="rfq-file' + (u.err ? ' is-error' : '') + '"><span class="rfq-file-n"><bdi>' + esc(u.name) + '</bdi></span><span class="rfq-file-s">' + (u.err ? esc(u.err) : fa(Math.round(u.pct)) + '٪') + '</span>' +
					(u.err ? '<button type="button" class="rfq-item-del" data-rmup="' + i + '" aria-label="بستن">' + X + '</button>' : '<span class="rfq-file-bar"><span style="width:' + u.pct + '%"></span></span>') + '</li>';
			}).join('');
		}
		function addFiles(list) {
			var errEl = $(form, '[data-err="files"]');
			errEl.textContent = '';
			Array.prototype.forEach.call(list, function (file) {
				var ext = (file.name.split('.').pop() || '').toLowerCase(), live = uploading.filter(function (u) { return !u.err; }).length;
				if (st.files.length + live >= C.files.max) { errEl.textContent = 'حداکثر ' + fa(C.files.max) + ' فایل قابل ارسال است.'; return; }
				if (C.files.ext.indexOf(ext) < 0) { errEl.textContent = '«' + file.name + '»: این نوع فایل پذیرفته نمی‌شود.'; return; }
				if (file.size > C.files.bytes) { errEl.textContent = '«' + file.name + '»: حجم فایل بیش از ' + size(C.files.bytes) + ' است.'; return; }
				var u = { name: file.name, pct: 0, err: '' };
				uploading.push(u);
				session().then(function (s) {
					var fd = new FormData(), x = new XMLHttpRequest();
					fd.append('file', file); fd.append('token', s.token);
					x.open('POST', C.rest + '/upload');
					x.upload.onprogress = function (e) { if (e.lengthComputable) { u.pct = e.loaded / e.total * 99; renderFiles(); } };
					x.onload = function () {
						var j; try { j = JSON.parse(x.responseText); } catch (e) { j = { message: x.status === 413 ? 'حجم فایل بیش از حد مجاز سرور است.' : 'خطای سرور (' + x.status + ')' }; }
						if (j.ok) { uploading.splice(uploading.indexOf(u), 1); st.files.push(j.file); save(); } else { u.err = j.message || 'بارگذاری ناموفق بود.'; if (j.field === 'token') sess = null; }
						renderFiles();
					};
					x.onerror = function () { u.err = 'اتصال قطع شد.'; renderFiles(); };
					x.send(fd);
				}, function () { u.err = NET; renderFiles(); });
			});
			renderFiles();
		}

		/* ----- static fields (project / contact) ----- */
		function fillStatic() {
			$$(form, '[name^="project."],[name^="contact."]').forEach(function (el) {
				var p = el.name.split('.'), v = (st[p[0]] || {})[p[1]];
				if (el.type === 'radio') el.checked = v != null && el.value === String(v);
				else el.value = v == null ? '' : v;
			});
			toggleUser();
		}
		function toggleUser() {
			var w = $(form, '[data-f="contact.username"]'), ch = st.contact.channel;
			if (w) w.hidden = ch !== 'telegram';
		}

		/* ----- review ----- */
		function renderReview() {
			// row(): label, value, kind — 'opt' (an option label or unit value: bidi), 'ltr' (mobile/email/ids), else free text (own isolate).
			var h = '', row = function (l, v, kind) {
				if (empty(v)) return '';
				var d = kind === 'opt' ? bidi(v) : kind === 'ltr' ? '<bdi dir="ltr">' + esc(v) + '</bdi>' : '<bdi>' + esc(v) + '</bdi>';
				return '<div class="rfq-rv-row"><dt>' + bidi(noOpt(l)) + '</dt><dd>' + d + '</dd></div>';
			};
			var block = function (title, step, body) {
				return steps.indexOf(step) < 0 && step !== 'types' ? '' : '<section class="rfq-rv"><div class="rfq-rv-h"><h3>' + title + '</h3><button type="button" class="rfq-link" data-goto="' + step + '">ویرایش<span class="rfq-sr"> ' + title + '</span></button></div>' + (body || '<p class="rfq-muted">موردی وارد نشده است.</p>') + '</section>';
			};
			h += block('اقلام و مشخصات فنی', steps.indexOf('specs') > -1 ? 'specs' : 'types', st.items.map(function (it, i) {
				var g = group(it), specs = C.specs.filter(function (f) { return f.g[g] && !empty(it.specs[f.k]); }).map(function (f) {
					var v = it.specs[f.k], o = f.g[g];
					v = Array.isArray(v) ? v.map(function (x) { return optLabel(o, x); }).join('، ') : (o.length ? optLabel(o, v) : (f.u ? latin(v) + ' ' + f.u : v));
					return row(f.l, v, o.length || f.u ? 'opt' : '');
				}).join('');
				return '<div class="rfq-rv-item"><p class="rfq-rv-it"><span class="rfq-spec-n">' + fa(i + 1) + '</span> ' + bidi((C.types[it.type] || { l: it.type }).l) + ' <small>× ' + fa(it.qty) + '</small></p>' + (specs ? '<dl>' + specs + '</dl>' : '') + '</div>';
			}).join(''));
			if (steps.indexOf('files') > -1) h += block('مدارک', 'files', st.files.length ? '<ul class="rfq-rv-files">' + st.files.map(function (f) { return '<li><bdi>' + esc(f.name) + '</bdi></li>'; }).join('') + '</ul>' : '');
			['project', 'contact'].forEach(function (s) {
				var rows = Object.keys(C.fields).filter(function (k) { return k.indexOf(s + '.') === 0; }).map(function (k) {
					var v = st[s][k.split('.')[1]];
					return row(C.fields[k].l, empty(v) ? '' : optLabel(C.fields[k].o, v), C.fields[k].o ? 'opt' : LTR[k] ? 'ltr' : '');
				}).join('');
				h += block(s === 'project' ? 'پروژه' : 'اطلاعات تماس', s, rows ? '<dl>' + rows + '</dl>' : '');
			});
			$(form, '[data-review]').innerHTML = h;
		}

		/* ----- validation (mirrors inc/rfq/rest.php) ----- */
		function validate(step) {
			var first = null, err = function (f, m) { var e = fieldErr(root, f, m); first = first || e; };
			clearErr(sec(step));
			if (step === 'types') {
				if (!st.items.length) err('items', 'حداقل یک نوع تابلو انتخاب کنید.');
				st.items.forEach(function (it, i) { if (!(it.qty >= 1 && it.qty <= 9999)) err('items', 'تعداد ردیف ' + fa(i + 1) + ' باید بین ۱ تا ۹۹۹۹ باشد.'); });
			} else if (step === 'specs') {
				st.items.forEach(function (it, i) {
					C.specs.forEach(function (f) { if (f.r && f.g[group(it)] && empty(it.specs[f.k])) err('items.' + i + '.' + f.k, '«' + f.l + '» را مشخص کنید (یا «' + C.unknown + '»).'); });
				});
			} else if (step === 'files') {
				if (uploading.some(function (u) { return !u.err; })) err('files', 'لطفاً تا پایان بارگذاری فایل‌ها صبر کنید.');
				else if (fieldVis('files') === 'required' && !st.files.length) err('files', 'لطفاً حداقل یک فایل بارگذاری کنید.');
			} else if (step === 'project' || step === 'contact') {
				Object.keys(C.fields).forEach(function (k) {
					if (k.indexOf(step + '.') !== 0 || C.fields[k].v !== 'required') return;
					var v = st[step][k.split('.')[1]];
					if (empty(v) || (k === 'contact.name' && String(v).trim().length < 2)) err(k, '«' + C.fields[k].l + '» را تکمیل کنید.');
				});
				if (step === 'contact') {
					var c = st.contact;
					if (!empty(c.mobile) && !mobile(c.mobile)) err('contact.mobile', 'شماره موبایل معتبر نیست (مثال: ۰۹۱۲۱۲۳۴۵۶۷).');
					if (!empty(c.email) && !emailOk(c.email)) err('contact.email', 'ایمیل معتبر نیست.');
					if (c.channel === 'email' && empty(c.email) && fieldVis('contact.email') !== 'hide') err('contact.email', 'برای ارتباط از طریق ایمیل، آدرس ایمیل را وارد کنید.');
					var a = $(form, '[name="answer"]');
					if (sess && sess.challenge && a && !latin(a.value).trim()) err('challenge', 'به سؤال امنیتی پاسخ دهید.');
				}
			}
			if (first) focusFirst(first);
			return !first;
		}

		/* ----- navigation ----- */
		function go(n, noFocus) {
			n = Math.max(0, Math.min(steps.length - 1, n));
			var k = steps[n];
			if (k === 'specs') renderSpecs();
			if (k === 'review') renderReview();
			if (k === 'files') renderFiles();
			$$(form, '.rfq-step').forEach(function (s) { s.hidden = s.dataset.step !== k; });
			$$(root, '.rfq-progress li').forEach(function (li, i) {
				li.classList.toggle('is-done', i < n); li.classList.toggle('is-current', i === n);
				if (i === n) li.setAttribute('aria-current', 'step'); else li.removeAttribute('aria-current');
				var ps = $(li, '[data-p-state]');
				if (ps) ps.textContent = i < n ? ' (انجام شد)' : '';
			});
			var ms = $(root, '[data-mstep]');
			if (ms) ms.innerHTML = 'مرحله ' + fa(n + 1) + ' از ' + fa(steps.length) + ' <span class="rfq-mstep-l">' + esc(C.steps[n][1]) + '</span>';
			var bar = $(root, '.rfq-bar'), pct = Math.round((n + 1) / steps.length * 100);
			bar.setAttribute('aria-valuenow', pct); bar.firstElementChild.style.width = pct + '%';
			$(form, '[data-act="prev"]').hidden = n === 0;
			$(form, '[data-act="next"]').hidden = n === steps.length - 1;
			$(form, '[data-act="submit"]').hidden = n !== steps.length - 1;
			$(form, '[data-count]').textContent = 'مرحله ' + fa(n + 1) + ' از ' + fa(steps.length);
			$(form, '[data-err="submit"]').textContent = '';
			st.step = n; save();
			if (!noFocus) {
				var h = $(sec(k), '.rfq-step-title');
				if (h) h.focus({ preventScroll: true });
				var top = root.getBoundingClientRect().top;
				if (top < 0 || top > innerHeight * 0.6) root.scrollIntoView({ block: 'start', behavior: 'smooth' });
			}
		}
		function next() { if (validate(steps[st.step])) go(st.step + 1); }

		function stepOf(field) {
			if (field === 'items' || /^items\.\d+\.qty$/.test(field)) return 'types';
			if (field.indexOf('items.') === 0) return steps.indexOf('specs') > -1 ? 'specs' : 'types';
			if (field === 'challenge') return 'contact';
			return field.split('.')[0];
		}
		function submit() {
			for (var i = 0; i < steps.length - 1; i++) if (!validate(steps[i])) { go(i, true); validate(steps[i]); return; }
			var btn = $(form, '[data-act="submit"]'), a = $(form, '[name="answer"]');
			busy(btn, true);
			send({
				mode: 'full', items: st.items, project: st.project, contact: st.contact,
				files: st.files.map(function (f) { return f.id; }), answer: a ? latin(a.value) : '',
				website: $(form, '[name="website"]').value, source: source(st.product)
			}).then(function (j) {
				busy(btn, false);
				store(null);
				form.hidden = true;
				$$(root, '.rfq-progress,.rfq-mstep,[data-restored]').forEach(function (e) { e.hidden = true; });
				success(root, j);
			}, function (j) {
				busy(btn, false);
				var f = j.field || '', s = f ? steps.indexOf(stepOf(f)) : -1;
				if (s > -1 && s !== st.step) go(s, true);
				focusFirst(fieldErr(root, f || 'submit', j.message || NET));
			});
		}
		function reset() {
			st = blank(); uploading = []; store(null);
			form.reset(); fillStatic(); renderItems();
			$(root, '[data-restored]').hidden = true;
			go(0);
		}

		/* ----- events ----- */
		form.addEventListener('click', function (e) {
			var t = e.target.closest('button');
			if (!t || !form.contains(t)) return;
			var item = t.closest('.rfq-item'), i = item ? +item.dataset.i : -1;
			if (t.dataset.tq) {
				var tt = t.closest('.rfq-tile').dataset.tile, ti = -1;
				st.items.some(function (x, k) { if (x.type === tt) { ti = k; return true; } return false; });
				if (ti < 0) return;
				st.items[ti].qty = Math.max(1, Math.min(9999, (st.items[ti].qty || 1) + +t.dataset.tq));
			} else if (t.classList.contains('rfq-card')) {
				var type = t.dataset.type, has = st.items.some(function (x) { return x.type === type; });
				st.items = has ? st.items.filter(function (x) { return x.type !== type; }) : st.items.concat([{ type: type, qty: 1, specs: {} }]);
				clearErr(sec('types'));
			} else if (t.dataset.q) {
				st.items[i].qty = Math.max(1, Math.min(9999, (st.items[i].qty || 1) + +t.dataset.q));
			} else if (t.hasAttribute('data-dup')) {
				st.items.splice(i + 1, 0, { type: st.items[i].type, qty: 1, specs: JSON.parse(JSON.stringify(st.items[i].specs)) });
			} else if (t.hasAttribute('data-del')) {
				st.items.splice(i, 1);
			} else if (t.dataset.copy) {
				var c = +t.dataset.copy, prev = st.items[c - 1], g = group(st.items[c]);
				C.specs.forEach(function (f) { if (f.g[g] && f.k !== 'note' && !empty(prev.specs[f.k]) && (f.t !== 'select' && f.t !== 'radio' && f.t !== 'chips' || f.g[g].some(function (p) { return [].concat(prev.specs[f.k]).indexOf(p[0]) > -1; }))) st.items[c].specs[f.k] = JSON.parse(JSON.stringify(prev.specs[f.k])); });
				renderSpecs(); save(); return;
			} else if (t.dataset.rmfile) {
				st.files.splice(+t.dataset.rmfile, 1); renderFiles(); save(); return;
			} else if (t.dataset.rmup) {
				uploading.splice(+t.dataset.rmup, 1); renderFiles(); return;
			} else if (t.dataset.goto) {
				go(steps.indexOf(t.dataset.goto)); return;
			} else if (t.dataset.act === 'prev') {
				go(st.step - 1); return;
			} else if (t.dataset.act === 'next') {
				next(); return;
			} else return;
			renderItems(); save();
		});
		form.addEventListener('input', onInput);
		form.addEventListener('change', onInput);
		function onInput(e) {
			var el = e.target, name = el.name || '';
			if (el.hasAttribute('data-qty')) {
				var it = st.items[+el.closest('.rfq-item').dataset.i], q = parseInt(latin(el.value).replace(/\D/g, ''), 10);
				if (it && q > 0) { it.qty = Math.min(9999, q); save(); }
				if (e.type === 'change') renderItems();
				return;
			}
			var p = name.split('.');
			if (p[0] === 'items' && p.length === 3) {
				var specs = st.items[+p[1]] && st.items[+p[1]].specs;
				if (!specs) return;
				if (el.tagName === 'SELECT') el.classList.toggle('rfq-input--lat', latCode(el.value ? el.options[el.selectedIndex].text.replace(/[\u2066\u2069]/g, '') : ''));
				specs[p[2]] = el.type === 'checkbox' ? $$(form, '[name="' + name + '"]:checked').map(function (c) { return c.value; }) : el.value;
			} else if (p[0] === 'project' || p[0] === 'contact') {
				if (el.type === 'radio' && !el.checked) return;
				st[p[0]][p[1]] = el.value;
				if (name === 'contact.channel') toggleUser();
				if (name === 'contact.mobile' && e.type === 'change' && mobile(el.value)) el.value = st.contact.mobile = mobile(el.value);
			} else return;
			var w = el.closest('.rfq-field');
			if (w && w.classList.contains('is-invalid')) clearErr(w);
			save();
		}
		form.addEventListener('keydown', function (e) {
			if (e.key !== 'Enter' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'BUTTON') return;
			e.preventDefault();
			if (st.step === steps.length - 1) submit(); else next();
		});
		form.addEventListener('submit', function (e) { e.preventDefault(); if (st.step === steps.length - 1) submit(); else next(); });

		var drop = $(form, '[data-drop]');
		if (drop) {
			var input = $(drop, 'input[type="file"]');
			input.addEventListener('change', function () { addFiles(input.files); input.value = ''; });
			['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); }); });
			['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-over'); }); });
			drop.addEventListener('drop', function (e) { if (e.dataTransfer) addFiles(e.dataTransfer.files); });
		}
		root.addEventListener('click', function (e) {
			var t = e.target.closest('[data-act]');
			if (!t) return;
			if (t.dataset.act === 'reset') reset();
			else if (t.dataset.act === 'new') {
				$(root, '[data-success]').hidden = true; form.hidden = false;
				$$(root, '.rfq-progress,.rfq-mstep').forEach(function (x) { x.hidden = false; });
				reset();
			} else if (t.dataset.act === 'copy') {
				var code = $(root, '[data-s-code]').textContent;
				if (navigator.clipboard) navigator.clipboard.writeText(code).then(function () { t.textContent = 'کپی شد'; });
			}
		});

		var ch = $(form, '[data-challenge]');
		if (ch) chEls.push(ch);
		fillStatic(); renderItems();
		go(Math.min(st.step || 0, steps.length - 1), true);
	}

	/* ============================== Quick form ============================== */
	function Quick(root) {
		var form = $(root, 'form'), ch = $(form, '[data-challenge]');
		if (ch) chEls.push(ch);
		var val = function (n) { var e = $(form, '[name="' + n + '"]'); return e ? e.value.trim() : ''; };
		form.addEventListener('input', function (e) { var w = e.target.closest('.is-invalid'); if (w) clearErr(w); });
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			clearErr(form);
			var first = null, err = function (f, m) { var x = fieldErr(root, f, m); first = first || x; };
			if (val('contact.name').length < 2) err('contact.name', 'نام خود را وارد کنید.');
			if (!mobile(val('contact.mobile'))) err('contact.mobile', 'شماره موبایل معتبر نیست (مثال: ۰۹۱۲۱۲۳۴۵۶۷).');
			if (sess && sess.challenge && !val('answer')) err('challenge', 'به سؤال امنیتی پاسخ دهید.');
			if (first) { focusFirst(first); return; }
			var btn = $(form, '[data-act="submit"]');
			busy(btn, true);
			send({
				mode: 'quick', items: [{ type: val('quick.type') || 'other', qty: 1 }],
				contact: { name: val('contact.name'), mobile: mobile(val('contact.mobile')), company: val('contact.company') },
				project: { description: val('project.description') }, answer: latin(val('answer')),
				website: val('website'), source: source(root.dataset.product)
			}).then(function (j) {
				busy(btn, false); form.hidden = true; success(root, j);
			}, function (j) {
				busy(btn, false);
				focusFirst(fieldErr(root, j.field && $(root, '[data-f="' + j.field + '"]') ? j.field : 'submit', j.message || NET));
			});
		});
	}

	function init() {
		var roots = $$(document, '[data-rfq]');
		if (!roots.length) return;
		roots.forEach(function (r) { if (r.dataset.rfqReady) return; r.dataset.rfqReady = 1; (r.dataset.rfq === 'quick' ? Quick : Wizard)(r); });
		var warm = function () { session().catch(function () {}); };
		if (window.requestIdleCallback) requestIdleCallback(warm, { timeout: 1500 }); else setTimeout(warm, 300);
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
