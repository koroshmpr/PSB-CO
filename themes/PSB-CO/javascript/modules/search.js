/**
 * Instant search component (Alpine.data('psbSearch')).
 * Markup: theme/template-parts/layout/header/search-modal.php
 * API:    GET /wp-json/psb/v1/search?q=…  (theme/inc/search-route.php)
 */
const RECENT_KEY = 'psb_recent_searches';
const FA = '۰۱۲۳۴۵۶۷۸۹';

const readRecent = () => {
	try {
		return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]').slice(0, 6);
	} catch (e) {
		return [];
	}
};

export default function psbSearch(cfg = {}) {
	return {
		query: '',
		groups: [],
		flat: [],
		active: -1,
		total: 0,
		suggest: [],
		recent: [],
		loading: false,
		searched: false,
		status: '',
		vh: 0,
		opener: null,
		icons: {},
		timer: null,
		ctrl: null,
		cache: new Map(),

		init() {
			this.icons = window.psbSearchIcons || {};
			this.recent = readRecent();
			this.$watch('searchOpen', (open) => {
				if (open) {
					// Remember the opener so focus returns there on close (a11y).
					const a = document.activeElement;
					this.opener = a && a !== document.body ? a : null;
					this.fitViewport();
					this.$nextTick(() => this.$refs.q && this.$refs.q.focus());
				} else {
					this.active = -1;
					const o = this.opener;
					this.opener = null;
					if (o && document.contains(o)) this.$nextTick(() => o.focus());
				}
			});
			// Keep the sheet above the on-screen keyboard on mobile.
			const vv = window.visualViewport;
			if (vv) {
				const fit = () => this.searchOpen && this.fitViewport();
				vv.addEventListener('resize', fit);
				vv.addEventListener('scroll', fit);
			}
		},

		fitViewport() {
			const vv = window.visualViewport;
			this.vh = vv && window.innerWidth < 640 ? Math.round(vv.height) : 0;
		},

		hotkey(e) {
			const t = e.target;
			const typing = t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName));
			if ((e.key === 'k' || e.key === 'K') && (e.ctrlKey || e.metaKey)) {
				e.preventDefault();
				this.searchOpen = true;
			} else if (e.key === '/' && !typing && !this.searchOpen) {
				e.preventDefault();
				this.searchOpen = true;
			} else if (e.key === 'Escape' && this.searchOpen && !e.defaultPrevented) {
				this.close();
			}
		},

		onInput() {
			clearTimeout(this.timer);
			const q = this.query.trim();
			this.active = -1;
			if (q.length < 2) {
				if (this.ctrl) this.ctrl.abort();
				this.groups = [];
				this.flat = [];
				this.loading = false;
				this.searched = false;
				return;
			}
			this.timer = setTimeout(() => this.fetch(q), 150);
		},

		async fetch(q) {
			const key = q.toLowerCase();
			if (this.cache.has(key)) {
				this.apply(this.cache.get(key));
				return;
			}
			if (this.ctrl) this.ctrl.abort();
			this.ctrl = new AbortController();
			this.loading = true;
			try {
				const url = `${cfg.endpoint}${cfg.endpoint.includes('?') ? '&' : '?'}q=${encodeURIComponent(q)}`;
				const res = await fetch(url, { signal: this.ctrl.signal, headers: { Accept: 'application/json' } });
				if (!res.ok) throw new Error(String(res.status));
				const data = await res.json();
				if (this.cache.size > 40) this.cache.clear();
				this.cache.set(key, data);
				if (this.query.trim() === q) this.apply(data);
			} catch (e) {
				if (e.name !== 'AbortError') {
					this.groups = [];
					this.flat = [];
					this.searched = true;
				}
			} finally {
				this.loading = false;
			}
		},

		apply(data) {
			let idx = 0;
			const flat = [];
			const groups = (data.groups || []).map((g) => ({
				...g,
				items: g.items.map((it) => {
					const item = { ...it, idx: idx++ };
					flat.push(item);
					return item;
				}),
			}));
			this.groups = groups;
			this.flat = flat;
			this.total = data.total || 0;
			this.suggest = data.suggest || [];
			this.searched = true;
			this.active = -1;
			this.status = this.total ? `${this.faNum(this.total)} نتیجه یافت شد` : 'نتیجه‌ای یافت نشد';
		},

		onKey(e) {
			if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
				if (!this.flat.length) return;
				e.preventDefault();
				const n = this.flat.length;
				this.active = e.key === 'ArrowDown' ? (this.active + 1) % n : (this.active - 1 + n) % n;
				this.$nextTick(() => {
					const el = document.getElementById(`psb-opt-${this.active}`);
					if (el) el.scrollIntoView({ block: 'nearest' });
				});
			} else if (e.key === 'Enter' && this.active >= 0 && this.flat[this.active]) {
				e.preventDefault();
				this.remember();
				window.location.href = this.flat[this.active].url;
			} else if (e.key === 'Escape') {
				e.preventDefault();
				if (this.query) this.clear();
				else this.close();
			}
		},

		onSubmit(e) {
			if (this.query.trim().length < 2) {
				e.preventDefault();
				return;
			}
			this.remember();
		},

		pick(term) {
			this.query = term;
			this.onInput();
			this.$refs.q && this.$refs.q.focus();
		},

		remember() {
			const q = this.query.trim();
			if (q.length < 2) return;
			this.recent = [q, ...this.recent.filter((r) => r !== q)].slice(0, 6);
			try {
				localStorage.setItem(RECENT_KEY, JSON.stringify(this.recent));
			} catch (e) {}
		},

		clearRecent() {
			this.recent = [];
			try {
				localStorage.removeItem(RECENT_KEY);
			} catch (e) {}
		},

		clear() {
			this.query = '';
			this.onInput();
			this.$refs.q && this.$refs.q.focus();
		},

		close() {
			this.searchOpen = false;
		},

		resultsUrl() {
			return `${cfg.results}?s=${encodeURIComponent(this.query.trim())}`;
		},

		faNum(n) {
			return String(n).replace(/\d/g, (d) => FA[d]);
		},
	};
}
