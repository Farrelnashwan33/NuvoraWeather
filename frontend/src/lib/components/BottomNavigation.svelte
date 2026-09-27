<script>
	import { weatherStore } from '$lib/stores/weatherState.svelte.js';
	import { i18n } from '$lib/stores/i18n.svelte.js';
	import { Home, CalendarDays, Map as MapIcon, Globe2, Search, ShieldAlert } from 'lucide-svelte';

	let { onSearchClick } = $props();

	function handleNav(tab) {
		if (typeof window !== 'undefined' && window.location.pathname !== '/') {
			window.location.href = '/';
		} else {
			weatherStore.activeTab = tab;
		}
	}
</script>

<div 
	class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-slate-950/95 backdrop-blur-2xl border-t border-white/10 px-3 py-2 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-10px_30px_rgba(0,0,0,0.8)] pointer-events-auto"
	style="transform: translateZ(0); -webkit-transform: translateZ(0); will-change: transform;"
>
	<div class="flex items-center justify-around max-w-md mx-auto">
		<button
			onclick={() => handleNav('overview')}
			class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl transition-all duration-200 {weatherStore.activeTab === 'overview' ? 'text-cyan-400 font-semibold scale-105' : 'text-slate-400 hover:text-white'}"
		>
			<Home class="w-4 h-4" />
			<span class="text-[10px]">{i18n.t('nav_home', 'Home')}</span>
		</button>

		<button
			onclick={() => handleNav('forecast')}
			class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl transition-all duration-200 {weatherStore.activeTab === 'forecast' ? 'text-cyan-400 font-semibold scale-105' : 'text-slate-400 hover:text-white'}"
		>
			<CalendarDays class="w-4 h-4" />
			<span class="text-[10px]">{i18n.t('nav_forecast', 'Forecast')}</span>
		</button>

		<button
			onclick={() => handleNav('map')}
			class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl transition-all duration-200 {weatherStore.activeTab === 'map' ? 'text-cyan-400 font-semibold scale-105' : 'text-slate-400 hover:text-white'}"
		>
			<MapIcon class="w-4 h-4" />
			<span class="text-[10px]">{i18n.t('nav_radar', 'Radar')}</span>
		</button>

		<!-- Disaster Monitor Page Link -->
		<a
			href="/disaster"
			class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl text-rose-400 font-bold transition-all duration-200 hover:text-rose-300"
		>
			<div class="relative">
				<ShieldAlert class="w-4 h-4" />
				<span class="w-1.5 h-1.5 rounded-full bg-rose-500 absolute -top-0.5 -right-0.5 animate-ping"></span>
			</div>
			<span class="text-[10px]">{i18n.t('nav_disaster', 'Bencana')}</span>
		</a>

		<button
			onclick={() => handleNav('cities')}
			class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl transition-all duration-200 {weatherStore.activeTab === 'cities' ? 'text-cyan-400 font-semibold scale-105' : 'text-slate-400 hover:text-white'}"
		>
			<Globe2 class="w-4 h-4" />
			<span class="text-[10px]">{i18n.t('nav_cities', 'Kota')}</span>
		</button>

		<!-- Search Trigger -->
		<button
			onclick={onSearchClick}
			class="p-2.5 rounded-2xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold shadow-lg shadow-cyan-500/30 active:scale-95 transition-all duration-200 ml-1"
			aria-label="Search"
		>
			<Search class="w-4 h-4" />
		</button>
	</div>
</div>
