/**
 * «سوابق اجرایی» — client-side filtering over the JSON embedded in the page (#pr-data). No AJAX, no deps.
 * Mirrors the PHP in inc/project-records/data.php + frontend.php (same query string, same row markup).
 * URL state: ?q=&sector=&prov=&type=a,b&dec=1370&sort=new|old|az
 */
(function () {
	'use strict';
	var root = document.getElementById('pr-list');
	var dataEl = document.getElementById('pr-data');
	if (!root || !dataEl) return;
	document.documentElement.classList.add('pr-js');

	var D;
	try { D = JSON.parse(dataEl.textContent); } catch (e) { return; }

	var PER = D.page || 30;
	var FA = '۰۱۲۳۴۵۶۷۸۹';
	function fa(v) { return String(v).replace(/[0-9]/g, function (d) { return FA[d]; }); }
	function faInt(n) { return fa(String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '٬')); }
	function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
	// Latin tokens with digits (20KV, 250 kVA, IP54) keep Latin digits.
	var LAT = /(^|[^A-Za-z0-9؀-ۿ])([A-Za-z][A-Za-z\-\/]*\s?[0-9][0-9A-Za-z.\/\-~]*|[0-9۰-۹][0-9۰-۹.,\/\-~]*\s?[A-Za-z][A-Za-z0-9\/\-.]*)(?![A-Za-z0-9؀-ۿ])/g;
	function lat(s) { return esc(s).replace(LAT, function (m, a, b) { return a + '<span class="lat">' + b.replace(/[۰-۹]/g, function (d) { return d.charCodeAt(0) - 0x06F0; }) + '</span>'; }); }
	function norm(s) {
		return String(s || '')
			.replace(/[۰-۹]/g, function (d) { return d.charCodeAt(0) - 0x06F0; })
			.replace(/[٠-٩]/g, function (d) { return d.charCodeAt(0) - 0x0660; })
			.replace(/[يى]/g, 'ی').replace(/ك/g, 'ک').replace(/[آأإ]/g, 'ا').replace(/[ۀة]/g, 'ه').replace(/ؤ/g, 'و').replace(/ئ/g, 'ی')
			.replace(/[ً-ْٰـ]/g, '')
			.toLowerCase()
			.replace(/[^\p{L}\p{N}]+/gu, ' ')
			.trim();
	}

	var sectors = D.s, provs = D.p, types = D.t;
	var rows = D.r.map(function (r, i) {
		var t = [];
		for (var b = 0; b < types.length; b++) if (r[8] & (1 << b)) t.push(b);
		var o = { id: r[0], title: r[1], client: r[2], anon: !!r[3], loc: r[4], prov: r[5], year: r[6], sec: r[7], types: t, url: r[9], seq: i };
		o.hay = ' ' + norm([o.title, o.client, o.loc, sectors[o.sec][1], o.year || ''].concat(t.map(function (k) { return types[k][1]; })).join(' '));
		return o;
	});
	var secIdx = {}; sectors.forEach(function (s, i) { secIdx[s[0]] = i; });
	var typeIdx = {}; types.forEach(function (t, i) { typeIdx[t[0]] = i; });
	var provIdx = {}; provs.forEach(function (p, i) { provIdx[p] = i; });

	var form = root.querySelector('[data-pr-form]');
	var body = root.querySelector('[data-pr-body]');
	var qInput = root.querySelector('[data-pr-q]');
	var provSel = root.querySelector('[data-pr-prov]');
	var decSel = root.querySelector('[data-pr-dec]');
	var sortSel = root.querySelector('[data-pr-sort]');
	var moreBtn = root.querySelector('[data-pr-more]');
	var progress = root.querySelector('[data-pr-progress]');
	var countEl = root.querySelector('[data-pr-count]');
	var n2 = root.querySelector('[data-pr-n2]');
	var filteredEl = root.querySelector('[data-pr-filtered]');
	var emptyEl = root.querySelector('[data-pr-empty]');
	var activeEl = root.querySelector('[data-pr-active]');
	var panel = root.querySelector('[data-pr-panel]');
	var toggle = root.querySelector('[data-pr-toggle]');
	var table = root.querySelector('.pr-table');
	var collator = window.Intl && Intl.Collator ? new Intl.Collator('fa') : null;

	var state = readUrl();
	var list = [];
	var shown = 0;

	function readUrl() {
		var p = new URLSearchParams(location.search);
		var tps = [];
		(p.getAll('type').length > 1 ? p.getAll('type') : (p.get('type') || '').split(',')).forEach(function (k) { if (k in typeIdx && tps.indexOf(k) < 0) tps.push(k); });
		p.getAll('type[]').forEach(function (k) { if (k in typeIdx && tps.indexOf(k) < 0) tps.push(k); });
		var sec = p.get('sector') || '';
		return {
			q: (p.get('q') || '').slice(0, 80),
			sector: sec in secIdx ? sec : '',
			prov: p.get('prov') || '',
			type: tps,
			dec: parseInt(p.get('dec'), 10) || 0,
			sort: ['new', 'old', 'az'].indexOf(p.get('sort')) > -1 ? p.get('sort') : 'new',
			pg: Math.max(1, Math.min(100, parseInt(p.get('pg'), 10) || 1))
		};
	}

	function writeUrl() {
		var p = new URLSearchParams(location.search);
		['q', 'sector', 'prov', 'type', 'type[]', 'dec', 'sort', 'pg'].forEach(function (k) { p.delete(k); });
		if (state.q) p.set('q', state.q);
		if (state.sector) p.set('sector', state.sector);
		if (state.prov) p.set('prov', state.prov);
		if (state.type.length) p.set('type', state.type.join(','));
		if (state.dec) p.set('dec', state.dec);
		if (state.sort !== 'new') p.set('sort', state.sort);
		var qs = p.toString().replace(/%2C/g, ',');
		history.replaceState(history.state, '', location.pathname + (qs ? '?' + qs : '') + location.hash);
	}

	function matches(r, skip) {
		if (skip !== 'sector' && state.sector && r.sec !== secIdx[state.sector]) return false;
		if (skip !== 'prov' && state.prov && provs[r.prov] !== state.prov) return false;
		if (skip !== 'dec' && state.dec && (!r.year || Math.floor(r.year / 10) * 10 !== state.dec)) return false;
		if (skip !== 'type' && state.type.length) {
			var ok = false;
			for (var i = 0; i < state.type.length; i++) if (r.types.indexOf(typeIdx[state.type[i]]) > -1) { ok = true; break; }
			if (!ok) return false;
		}
		if (state.q) {
			var toks = norm(state.q).split(' ');
			for (var j = 0; j < toks.length; j++) if (toks[j] && r.hay.indexOf(toks[j]) < 0) return false;
		}
		return true;
	}

	function sortList(a) {
		return a.sort(function (x, y) {
			if (state.sort === 'az') {
				var ex = x.anon || !x.client ? 1 : 0, ey = y.anon || !y.client ? 1 : 0;
				if (ex !== ey) return ex - ey;
				var c = collator ? collator.compare(x.client, y.client) : (x.client < y.client ? -1 : x.client > y.client ? 1 : 0);
				return c || x.seq - y.seq;
			}
			var nx = x.year ? 0 : 1, ny = y.year ? 0 : 1;
			if (nx !== ny) return nx - ny;
			if (state.sort === 'old') return (x.year - y.year) || (x.seq - y.seq);
			return (y.year - x.year) || (y.seq - x.seq);
		});
	}

	function rowHtml(r) {
		var title = lat(r.title);
		if (r.url) title = '<a class="pr-link" href="' + esc(r.url) + '">' + title + '</a>';
		var na = '<span class="pr-na">—</span>';
		var client = r.client ? (r.anon ? '<span class="pr-anon">' + lat(r.client) + '</span>' : lat(r.client)) : na;
		var tags = r.types.map(function (k) { return '<span class="pr-tag">' + esc(types[k][1]) + '</span>'; }).join('');
		return '<tr class="pr-row is-new' + (r.anon ? ' is-anon' : '') + '">' +
			'<th scope="row" class="pr-c-title"><span class="pr-title">' + title + '</span></th>' +
			'<td class="pr-c-client" data-l="کارفرما">' + client + '</td>' +
			'<td class="pr-c-loc" data-l="محل">' + (r.loc ? esc(r.loc) : na) + '</td>' +
			'<td class="pr-c-year" data-l="سال">' + (r.year ? fa(r.year) : na) + '</td>' +
			'<td class="pr-c-sector" data-l="حوزه"><span class="pr-sector">' + esc(sectors[r.sec][1]) + '</span></td>' +
			'<td class="pr-c-types" data-l="نوع تابلو">' + tags + '</td></tr>';
	}

	function renderMore(n) {
		var next = list.slice(shown, shown + n);
		if (next.length) body.insertAdjacentHTML('beforeend', next.map(rowHtml).join(''));
		shown += next.length;
		updateProgress();
	}

	function updateProgress() {
		var total = list.length;
		progress.hidden = !total;
		progress.textContent = 'نمایش ' + faInt(shown) + ' از ' + faInt(total);
		moreBtn.hidden = shown >= total;
	}

	function facets() {
		var f = { sector: {}, type: {}, prov: {}, dec: {} };
		rows.forEach(function (r) {
			if (matches(r, 'sector')) f.sector[r.sec] = (f.sector[r.sec] || 0) + 1;
			if (matches(r, 'type')) r.types.forEach(function (t) { f.type[t] = (f.type[t] || 0) + 1; });
			if (r.prov > -1 && matches(r, 'prov')) f.prov[r.prov] = (f.prov[r.prov] || 0) + 1;
			if (r.year && matches(r, 'dec')) { var d = Math.floor(r.year / 10) * 10; f.dec[d] = (f.dec[d] || 0) + 1; }
		});
		return f;
	}

	function syncControls(f) {
		var sum = 0;
		Object.keys(f.sector).forEach(function (k) { sum += f.sector[k]; });
		root.querySelectorAll('[data-pr-sectors] .pr-chip').forEach(function (chip) {
			var inp = chip.querySelector('input');
			inp.checked = inp.value === state.sector;
			var n = inp.value ? (f.sector[secIdx[inp.value]] || 0) : sum;
			chip.querySelector('.pr-n').textContent = faInt(n);
			chip.classList.toggle('is-zero', !n);
		});
		root.querySelectorAll('[data-pr-types] .pr-chip').forEach(function (chip) {
			var inp = chip.querySelector('input');
			inp.checked = state.type.indexOf(inp.value) > -1;
			var n = f.type[typeIdx[inp.value]] || 0;
			chip.querySelector('.pr-n').textContent = faInt(n);
			chip.classList.toggle('is-zero', !n);
		});
		if (provSel) {
			Array.prototype.forEach.call(provSel.options, function (o) {
				if (!o.value) return;
				o.textContent = o.value + ' (' + fa(f.prov[provIdx[o.value]] || 0) + ')';
			});
			provSel.value = state.prov;
		}
		if (decSel) {
			Array.prototype.forEach.call(decSel.options, function (o) {
				if (!o.value) return;
				o.textContent = 'دهه ' + fa(o.value) + ' (' + fa(f.dec[o.value] || 0) + ')';
			});
			decSel.value = state.dec ? String(state.dec) : '';
		}
		if (sortSel) sortSel.value = state.sort;
		if (qInput && document.activeElement !== qInput) qInput.value = state.q;
		var active = (state.sector ? 1 : 0) + (state.prov ? 1 : 0) + state.type.length + (state.dec ? 1 : 0);
		if (activeEl) { activeEl.hidden = !active; activeEl.textContent = fa(active); }
		var any = active || state.q;
		root.querySelectorAll('.pr-summary [data-pr-reset]').forEach(function (a) { a.hidden = !any; });
		if (filteredEl) filteredEl.hidden = !any;
	}

	var dirty = false;
	function apply(opts) {
		opts = opts || {};
		dirty = true;
		list = sortList(rows.filter(function (r) { return matches(r); }));
		countEl.textContent = faInt(list.length);
		if (n2) n2.textContent = faInt(list.length);
		emptyEl.hidden = list.length > 0;
		table.hidden = list.length === 0;
		syncControls(facets());
		body.textContent = '';
		shown = 0;
		renderMore(Math.max(PER, opts.keep || 0));
		if (!opts.noUrl) writeUrl();
	}

	// ---- Events
	var tId;
	qInput && qInput.addEventListener('input', function () {
		clearTimeout(tId);
		tId = setTimeout(function () { state.q = qInput.value.trim().slice(0, 80); apply(); }, 140);
	});
	form.addEventListener('submit', function (e) {
		e.preventDefault();
		if (qInput) { state.q = qInput.value.trim(); qInput.blur(); }
		apply();
	});
	form.addEventListener('change', function (e) {
		var t = e.target;
		if (t.name === 'sector') state.sector = t.value;
		else if (t.name === 'type[]') {
			state.type = Array.prototype.map.call(root.querySelectorAll('[data-pr-types] input:checked'), function (i) { return i.value; });
		} else if (t === provSel) state.prov = t.value;
		else if (t === decSel) state.dec = parseInt(t.value, 10) || 0;
		else if (t === sortSel) state.sort = t.value;
		else return;
		apply();
	});
	moreBtn.addEventListener('click', function (e) {
		e.preventDefault();
		var first = shown;
		renderMore(PER);
		var row = body.rows[first];
		if (row) { row.setAttribute('tabindex', '-1'); row.focus({ preventScroll: true }); }
	});
	function reset(e) {
		e.preventDefault();
		state = { q: '', sector: '', prov: '', type: [], dec: 0, sort: state.sort, pg: 1 };
		if (qInput) qInput.value = '';
		apply();
	}
	root.querySelectorAll('[data-pr-reset]').forEach(function (a) { a.addEventListener('click', reset); });

	// Breakdown bars → filter the list.
	document.querySelectorAll('[data-pr-set]').forEach(function (a) {
		a.addEventListener('click', function (e) {
			e.preventDefault();
			var k = a.getAttribute('data-pr-set'), v = a.getAttribute('data-v');
			state = { q: '', sector: '', prov: '', type: [], dec: 0, sort: state.sort, pg: 1 };
			state[k] = v;
			apply();
			root.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
		});
	});

	// ---- Mobile sheet
	var lastFocus = null;
	function openPanel() {
		lastFocus = document.activeElement;
		dirty = false;
		panel.classList.add('is-open');
		document.documentElement.classList.add('pr-sheet-open');
		toggle.setAttribute('aria-expanded', 'true');
		panel.setAttribute('role', 'dialog');
		panel.setAttribute('aria-modal', 'true');
		panel.setAttribute('aria-label', 'فیلترها');
		var f = panel.querySelector('input,select,button');
		f && f.focus({ preventScroll: true });
	}
	function closePanel() {
		if (!panel.classList.contains('is-open')) return;
		panel.classList.remove('is-open');
		document.documentElement.classList.remove('pr-sheet-open');
		toggle.setAttribute('aria-expanded', 'false');
		panel.removeAttribute('role');
		panel.removeAttribute('aria-modal');
		(lastFocus || toggle).focus({ preventScroll: true });
		var sum = root.querySelector('.pr-summary');
		// Results were re-rendered: bring their top just under the sticky header + filter bar.
		if (sum && dirty) {
			var under = form.getBoundingClientRect().bottom;
			if (sum.getBoundingClientRect().top < under) window.scrollTo(0, sum.getBoundingClientRect().top + window.scrollY - under - 8);
		}
	}
	toggle && toggle.addEventListener('click', openPanel);
	panel.querySelectorAll('[data-pr-close]').forEach(function (b) { b.addEventListener('click', closePanel); });
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closePanel(); });
	document.addEventListener('click', function (e) {
		if (panel.classList.contains('is-open') && !panel.contains(e.target) && !toggle.contains(e.target)) {
			e.preventDefault();
			closePanel();
		}
	}, true);
	var mq = matchMedia('(min-width: 64rem)');
	(mq.addEventListener ? mq.addEventListener.bind(mq, 'change') : mq.addListener.bind(mq))(function () { if (mq.matches) closePanel(); });

	// Hairline under the sticky bar once it sticks.
	var ticking = false;
	window.addEventListener('scroll', function () {
		if (ticking) return;
		ticking = true;
		requestAnimationFrame(function () {
			var cs = getComputedStyle(form);
			if (cs.position === 'sticky') {
				var top = parseFloat(cs.top) || 0;
				form.classList.toggle('is-stuck', form.getBoundingClientRect().top <= top + 1);
			}
			ticking = false;
		});
	}, { passive: true });

	// Initial: the server already rendered the same state; only re-render if JS-side state differs (e.g. pg>1).
	list = sortList(rows.filter(function (r) { return matches(r); }));
	shown = body.rows.length;
	syncControls(facets());
	updateProgress();
	if (shown !== Math.min(list.length, PER * state.pg)) apply({ noUrl: true, keep: PER * state.pg });
	if (state.pg > 1) { state.pg = 1; writeUrl(); }
})();
