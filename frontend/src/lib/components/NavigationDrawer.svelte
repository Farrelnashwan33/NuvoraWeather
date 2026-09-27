<script>
	import { weatherStore } from '$lib/stores/weatherState.svelte.js';
	import { i18n } from '$lib/stores/i18n.svelte.js';
	import {
		X,
		Home,
		CalendarDays,
		Map as MapIcon,
		Car,
		Video,
		ShieldAlert,
		Activity,
		Globe2,
		Sun,
		Moon,
		Thermometer,
		Languages,
		ShieldCheck,
		Info,
		ChevronRight,
		Radio
	} from 'lucide-svelte';

	import { goto } from '$app/navigation';

	let { open = $bindable(false), onLanguageClick } = $props();

	function navigateTo(url, tab = null) {
		open = false;
		if (tab) {
			weatherStore.activeTab = tab;
		}
		if (typeof window !== 'undefined') {
			if (window.location.pathname !== url) {
				goto(url);
			} else {
				window.scrollTo({ top: 0, behavior: 'smooth' });
			}
		}
	}
</script>

{#if open}
	<!-- Backdrop Blur Overlay -->
	<div 
		role="button"
		tabindex="0"
		onclick={() => open = false}
		onkeydown={(e) => e.key === 'Escape' && (open = false)}
		class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md transition-opacity animate-in fade-in duration-200"
		aria-label="Tutup Menu"
	></div>

	<!-- Drawer Container (Bottom Sheet on Mobile, Slide from Right) -->
	<div 
		class="fixed bottom-0 inset-x-0 z-50 max-h-[90vh] bg-slate-900/95 backdrop-blur-2xl border-t border-white/10 rounded-t-[32px] p-5 sm:p-6 overflow-y-auto shadow-2xl shadow-cyan-950/50 animate-in slide-in-from-bottom duration-300 pointer-events-auto"
	>
		<!-- Handle Bar & Header -->
		<div class="flex items-center justify-between pb-4 border-b border-white/10">
			<div class="flex items-center gap-2.5">
				<div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-cyan-500 to-blue-600 flex items-center justify-center p-0.5">
					<div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
						<Radio class="w-4 h-4 text-cyan-400 animate-pulse" />
					</div>
				</div>
				<div>
					<h3 class="font-heading font-extrabold text-lg text-white">Nuvora Intelligence</h3>
					<p class="text-[11px] text-slate-400">Pusat Navigasi & Monitoring Nasional</p>
				</div>
			</div>

			<button
				onclick={() => open = false}
				class="p-2 rounded-full bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white transition"
				aria-label="Close"
			>
				<X class="w-5 h-5" />
			</button>
		</div>

		<!-- Menu Categories -->
		<div class="space-y-6 pt-5 pb-safe">
			<!-- CATEGORY 1: CUACA -->
			<div class="space-y-2">
				<span class="text-[11px] font-bold uppercase tracking-wider text-cyan-400 px-1">
					{i18n.t('category_weather', 'Cuaca & Atmosfer')}
				</span>
				<div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
					<button
						onclick={() => navigateTo('/', 'overview')}
						class="flex items-center justify-between p-3.5 rounded-2xl bg-white/[0.03] hover:bg-white/[0.08] border border-white/5 text-left transition group"
					>
						<div class="flex items-center gap-3">
							<div class="p-2 rounded-xl bg-cyan-500/10 text-cyan-400 group-hover:scale-110 transition">
								<Home class="w-4 h-4" />
							</div>
							<div>
								<div class="text-xs font-bold text-white">{i18n.t('nav_home', 'Beranda Cuaca')}</div>
								<div class="text-[10px] text-slate-400">Suhu & Metrik Utama</div>
							</div>
						</div>
						<ChevronRight class="w-4 h-4 text-slate-500 group-hover:text-cyan-400 group-hover:translate-x-0.5 transition" />
					</button>

					<button
						onclick={() => navigateTo('/', 'forecast')}
						class="flex items-center justify-between p-3.5 rounded-2xl bg-white/[0.03] hover:bg-white/[0.08] border border-white/5 text-left transition group"
					>
						<div class="flex items-center gap-3">
							<div class="p-2 rounded-xl bg-blue-500/10 text-blue-400 group-hover:scale-110 transition">
								<CalendarDays class="w-4 h-4" />
							</div>
							<div>
								<div class="text-xs font-bold text-white">{i18n.t('nav_forecast', 'Prakiraan 7 Hari')}</div>
								<div class="text-[10px] text-slate-400">24 Jam & Mingguan</div>
							</div>
						</div>
						<ChevronRight class="w-4 h-4 text-slate-500 group-hover:text-blue-400 group-hover:translate-x-0.5 transition" />
					</button>

					<button
						onclick={() => navigateTo('/', 'map')}
						class="flex items-center justify-between p-3.5 rounded-2xl bg-white/[0.03] hover:bg-white/[0.08] border border-white/5 text-left transition group"
					>
						<div class="flex items-center gap-3">
							<div class="p-2 rounded-xl bg-sky-500/10 text-sky-400 group-hover:scale-110 transition">
								<MapIcon class="w-4 h-4" />
							</div>
							<div>
								<div class="text-xs font-bold text-white">{i18n.t('nav_radar', 'Radar & Peta Cuaca')}</div>
								<div class="text-[10px] text-slate-400">Satelit & Google Maps</div>
							</div>
						</div>
						<ChevronRight class="w-4 h-4 text-slate-500 group-hover:text-sky-400 group-hover:translate-x-0.5 transition" />
					</button>
				</div>
			</div>

			<!-- CATEGORY 2: MONITORING NASIONAL -->
			<div class="space-y-2">
				<span class="text-[11px] font-bold uppercase tracking-wider text-emerald-400 px-1">
					Monitoring & Telemetri Nasional
				</span>
				<div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
					<a
						href="/traffic"
						onclick={() => open = false}
						class="flex items-center justify-between p-3.5 rounded-2xl bg-gradient-to-r from-emerald-500/10 to-teal-500/5 hover:from-emerald-500/20 hover:to-teal-500/15 border border-emerald-500/20 text-left transition group"
					>
						<div class="flex items-center gap-3">
							<div class="p-2 rounded-xl bg-emerald-500/20 text-emerald-400 group-hover:scale-110 transition">
								<Car class="w-4 h-4" />
							</div>
							<div>
								<div class="text-xs font-bold text-white flex items-center gap-1.5">
									<span>Lalu Lintas Indonesia</span>
									<span class="px-1.5 py-0.5 rounded text-[9px] bg-emerald-500/20 text-emerald-300 font-mono">LIVE</span>
								</div>
								<div class="text-[10px] text-slate-400">Pantau Kepadatan & Jalan Raya</div>
							</div>
						</div>
						<ChevronRight class="w-4 h-4 text-slate-500 group-hover:text-emerald-400 group-hover:translate-x-0.5 transition" />
					</a>

					<a
						href="/cctv"
						onclick={() => open = false}
						class="flex items-center justify-between p-3.5 rounded-2xl bg-gradient-to-r from-amber-500/10 to-orange-500/5 hover:from-amber-500/20 hover:to-orange-500/15 border border-amber-500/20 text-left transition group"
					>
						<div class="flex items-center gap-3">
							<div class="p-2 rounded-xl bg-amber-500/20 text-amber-400 group-hover:scale-110 transition">
								<Video class="w-4 h-4" />
							</div>
							<div>
								<div class="text-xs font-bold text-white flex items-center gap-1.5">
									<span>CCTV Lalu Lintas</span>
									<span class="px-1.5 py-0.5 rounded text-[9px] bg-amber-500/20 text-amber-300 font-mono">ATCS</span>
								</div>
								<div class="text-[10px] text-slate-400">Kamera Jalan Raya Resmi Dishub</div>
							</div>
						</div>
						<ChevronRight class="w-4 h-4 text-slate-500 group-hover:text-amber-400 group-hover:translate-x-0.5 transition" />
					</a>

					<a
						href="/disaster"
						onclick={() => open = false}
						class="flex items-center justify-between p-3.5 rounded-2xl bg-gradient-to-r from-rose-500/10 to-red-500/5 hover:from-rose-500/20 hover:to-red-500/15 border border-rose-500/20 text-left transition group"
					>
						<div class="flex items-center gap-3">
							<div class="p-2 rounded-xl bg-rose-500/20 text-rose-400 group-hover:scale-110 transition">
								<ShieldAlert class="w-4 h-4" />
							</div>
							<div>
								<div class="text-xs font-bold text-white flex items-center gap-1.5">
									<span>Bencana Gempa & Banjir</span>
									<span class="px-1.5 py-0.5 rounded text-[9px] bg-rose-500/20 text-rose-300 font-mono">BMKG</span>
								</div>
								<div class="text-[10px] text-slate-400">Peringatan Dini & Pos Pantau</div>
							</div>
						</div>
						<ChevronRight class="w-4 h-4 text-slate-500 group-hover:text-rose-400 group-hover:translate-x-0.5 transition" />
					</a>

					<a
						href="/monitoring"
						onclick={() => open = false}
						class="flex items-center justify-between p-3.5 rounded-2xl bg-gradient-to-r from-indigo-500/10 to-purple-500/5 hover:from-indigo-500/20 hover:to-purple-500/15 border border-indigo-500/20 text-left transition group"
					>
						<div class="flex items-center gap-3">
							<div class="p-2 rounded-xl bg-indigo-500/20 text-indigo-400 group-hover:scale-110 transition">
								<Activity class="w-4 h-4" />
							</div>
							<div>
								<div class="text-xs font-bold text-white flex items-center gap-1.5">
									<span>Pusat Monitoring Terpadu</span>
								</div>
								<div class="text-[10px] text-slate-400">Hub 4 Panel Telemetri Lengkap</div>
							</div>
						</div>
						<ChevronRight class="w-4 h-4 text-slate-500 group-hover:text-indigo-400 group-hover:translate-x-0.5 transition" />
					</a>
				</div>
			</div>

			<!-- CATEGORY 3: LAINNYA & PENGATURAN CEPAT -->
			<div class="space-y-2">
				<span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-1">
					Setelan Cepat & Administrasi
				</span>
				<div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
					<!-- Unit Toggle -->
					<button
						onclick={() => weatherStore.toggleUnit()}
						class="p-3 rounded-2xl bg-white/[0.03] hover:bg-white/[0.08] border border-white/5 text-left transition flex items-center justify-between"
					>
						<div class="flex items-center gap-2">
							<Thermometer class="w-4 h-4 text-cyan-400" />
							<span class="text-xs font-semibold text-slate-200">Satuan</span>
						</div>
						<span class="px-2 py-0.5 rounded-lg bg-cyan-500/20 text-cyan-300 font-bold font-mono text-xs">
							°{weatherStore.unit}
						</span>
					</button>

					<!-- Theme Toggle -->
					<button
						onclick={() => weatherStore.toggleTheme()}
						class="p-3 rounded-2xl bg-white/[0.03] hover:bg-white/[0.08] border border-white/5 text-left transition flex items-center justify-between"
					>
						<div class="flex items-center gap-2">
							{#if weatherStore.theme === 'dark'}
								<Moon class="w-4 h-4 text-indigo-400" />
							{:else}
								<Sun class="w-4 h-4 text-amber-400" />
							{/if}
							<span class="text-xs font-semibold text-slate-200">Tema</span>
						</div>
						<span class="text-xs text-slate-400 capitalize">{weatherStore.theme}</span>
					</button>

					<!-- Language Selector -->
					<button
						onclick={() => { open = false; onLanguageClick?.(); }}
						class="p-3 rounded-2xl bg-white/[0.03] hover:bg-white/[0.08] border border-white/5 text-left transition flex items-center justify-between"
					>
						<div class="flex items-center gap-2">
							<Languages class="w-4 h-4 text-sky-400" />
							<span class="text-xs font-semibold text-slate-200">Bahasa</span>
						</div>
						<span class="text-xs text-cyan-300 font-bold uppercase">{i18n.currentLang}</span>
					</button>

					<!-- Admin Portal Link -->
					<a
						href="/admin/login"
						onclick={() => open = false}
						class="p-3 rounded-2xl bg-white/[0.03] hover:bg-white/[0.08] border border-white/5 text-left transition flex items-center justify-between text-slate-200 hover:text-cyan-300"
					>
						<div class="flex items-center gap-2">
							<ShieldCheck class="w-4 h-4 text-slate-400" />
							<span class="text-xs font-semibold">Admin</span>
						</div>
						<ChevronRight class="w-3.5 h-3.5 text-slate-500" />
					</a>
				</div>
			</div>
		</div>
	</div>
{/if}
