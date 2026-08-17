<?php
/**
 * Advanced Multi-CPT Live Search Modal
 * PSB-CO High-Voltage Electrical Panel Theme
 */
?>
<section id="searchModal"
    x-show="searchOpen"
    x-data="{
        query: '',
        activeTab: 'all',
        isSearching: false,
        counts: { all: 0, services: 0, portfolio: 0, certificates: 0, confirmation: 0, 'type-test': 0, post: 0 },
        htmlResults: '',
        searchDebounceTimer: null,
        performSearch() {
            clearTimeout(this.searchDebounceTimer);
            if (this.query.trim().length < 2) {
                this.htmlResults = '';
                this.isSearching = false;
                return;
            }
            this.isSearching = true;
            this.searchDebounceTimer = setTimeout(() => {
                let formData = new URLSearchParams();
                formData.append('action', 'custom_ajax_search');
                formData.append('keyword', this.query.trim());
                formData.append('post_type', this.activeTab);

                fetch('<?= admin_url("admin-ajax.php") ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                })
                .then(res => res.json())
                .then(res => {
                    this.isSearching = false;
                    if (res.success) {
                        this.htmlResults = res.data.html;
                        this.counts = res.data.counts;
                    }
                })
                .catch(err => {
                    this.isSearching = false;
                });
            }, 180);
        },
        setTab(tab) {
            this.activeTab = tab;
            this.performSearch();
        },
        clearQuery() {
            this.query = '';
            this.htmlResults = '';
            this.counts = { all: 0, services: 0, portfolio: 0, certificates: 0, confirmation: 0, 'type-test': 0, post: 0 };
        }
    }"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    @keydown.escape.window="searchOpen = false"
    @click.self="searchOpen = false"
    class="fixed inset-0 z-[110] bg-slate-950/85 backdrop-blur-xl flex flex-col justify-start select-none overflow-hidden"
    style="display: none;">

    <div class="bg-white  w-screen mx-auto overflow-hidden flex flex-col h-screen max-h-screen">
        
        <!-- 1. Search Header Bar -->
        <div class="p-4 bg-slate-950 pt-16 text-white border-b border-slate-800 shrink-0 space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-600/30 text-blue-400 border border-blue-500/30 flex items-center justify-center text-lg shrink-0">
                        🔍
                    </div>
                    <div>
                        <h3 class="font-black text-base sm:text-lg text-white">جستجوی پیشرفته صنعتی</h3>
                        <p class="text-xs text-slate-400">جستجوی آنی در محصولات، خدمات مهندسی، پروژه‌ها و گواهی‌نامه‌ها</p>
                    </div>
                </div>

                <!-- Close Button -->
                <button @click="searchOpen = false" aria-label="بستن جستجو" class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 hover:bg-red-600 hover:border-red-500 text-slate-400 hover:text-white flex items-center justify-center transition-all cursor-pointer shadow-md shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Main Input Field -->
            <div class="relative flex items-center">
                <input type="text"
                       x-model="query"
                       @input="performSearch()"
                       placeholder="نام کالا، سطح ولتاژ، استاندارد یا خدمات مورد نظر را تایپ کنید..."
                       class="w-full bg-slate-900 border border-slate-700/80 rounded-2xl py-3.5 px-12 text-sm sm:text-base text-white placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all font-sans"
                       autofocus />

                <!-- Search Icon Left -->
                <div class="absolute right-4 text-slate-400 pointer-events-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <!-- Loader or Clear Button Right -->
                <div class="absolute left-4 flex items-center gap-2">
                    <template x-if="isSearching">
                        <svg class="w-5 h-5 animate-spin text-blue-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <template x-if="query.length > 0 && !isSearching">
                        <button @click="clearQuery()" class="text-slate-400 hover:text-white transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- 2. Multi-CPT Tab Bar -->
        <div x-show="query.length >= 2" class="p-3 bg-slate-100 border-b border-slate-200 flex items-center gap-2 overflow-x-auto custom-scrollbar shrink-0">
            <button @click="setTab('all')"
                    :class="activeTab === 'all' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-200'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <span>همه نتایج</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/20" x-text="counts.all || 0"></span>
            </button>

            <button @click="setTab('services')"
                    :class="activeTab === 'services' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-200'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <span>⚡ خدمات مهندسی</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/20" x-text="counts.services || 0"></span>
            </button>

            <button @click="setTab('portfolio')"
                    :class="activeTab === 'portfolio' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-200'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <span>🏢 نمونه کارها</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/20" x-text="counts.portfolio || 0"></span>
            </button>

            <button @click="setTab('certificates')"
                    :class="activeTab === 'certificates' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-200'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <span>🏆 گواهی‌نامه‌ها</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/20" x-text="counts.certificates || 0"></span>
            </button>

            <button @click="setTab('confirmation')"
                    :class="activeTab === 'confirmation' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-200'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <span>🏛️ تأییدیه‌ها</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/20" x-text="counts.confirmation || 0"></span>
            </button>

            <button @click="setTab('type-test')"
                    :class="activeTab === 'type-test' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-200'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <span>🔬 تایپ‌تست‌ها</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/20" x-text="counts['type-test'] || 0"></span>
            </button>

            <button @click="setTab('post')"
                    :class="activeTab === 'post' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-200'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <span>📰 مقالات & اخبار</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/20" x-text="counts.post || 0"></span>
            </button>
        </div>

        <!-- 3. Results Container -->
        <div class="p-6 overflow-y-auto flex-1 bg-slate-50/50">
            <!-- Initial State (Before search) -->
            <template x-if="query.length < 2">
                <div class="py-8 text-center space-y-4">
                    <span class="text-xs font-bold text-slate-500 block">عبارات پیشنهادی برای جستجوی سریع:</span>
                    <div class="flex flex-wrap items-center justify-center gap-2 max-w-xl mx-auto">
                        <button @click="query = 'تابلو کشویی'; performSearch()" class="px-3 py-1.5 rounded-xl bg-white hover:bg-blue-50 border border-slate-200 text-xs font-medium text-slate-700 hover:text-blue-700 transition-all shadow-sm cursor-pointer">⚡ تابلو کشویی</button>
                        <button @click="query = 'توانیر'; performSearch()" class="px-3 py-1.5 rounded-xl bg-white hover:bg-blue-50 border border-slate-200 text-xs font-medium text-slate-700 hover:text-blue-700 transition-all shadow-sm cursor-pointer">🏛️ تأییدیه توانیر</button>
                        <button @click="query = 'تست'; performSearch()" class="px-3 py-1.5 rounded-xl bg-white hover:bg-blue-50 border border-slate-200 text-xs font-medium text-slate-700 hover:text-blue-700 transition-all shadow-sm cursor-pointer">🔬 تست‌های آزمایشگاهی</button>
                        <button @click="query = 'ISO 9001'; performSearch()" class="px-3 py-1.5 rounded-xl bg-white hover:bg-blue-50 border border-slate-200 text-xs font-medium text-slate-700 hover:text-blue-700 transition-all shadow-sm cursor-pointer">🏆 استاندارد ISO 9001</button>
                        <button @click="query = 'فشار متوسط'; performSearch()" class="px-3 py-1.5 rounded-xl bg-white hover:bg-blue-50 border border-slate-200 text-xs font-medium text-slate-700 hover:text-blue-700 transition-all shadow-sm cursor-pointer">⚡ تابلو فشار متوسط</button>
                    </div>
                </div>
            </template>

            <!-- Results Grid -->
            <div x-show="query.length >= 2" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" x-html="htmlResults">
            </div>
        </div>

    </div>
</section>