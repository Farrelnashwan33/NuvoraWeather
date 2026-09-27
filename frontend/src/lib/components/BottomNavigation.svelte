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

<div class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-slate-950/90 backdrop-blur-xl border-t border-white/10 px-2 py-2 pb-safe">
	<div class="flex items-center justify-around">
		<button
			onclick={() => handleNav('overview')}
			class="flex flex-col items-center gap-1 py-1 px-2 rounded-xl transition {weatherStore.activeTab === 'overview' ? 'text-cyan-400 font-semibold' : 'text-slate-400'}"
		>
			<Home class="w-4 h-4" />
			<span class="text-[9px]">{i18n.t('nav_home', 'Home')}</span>
		</button>

		<button
			onclick={() => handleNav('forecast')}
			class="flex flex-col items-center gap-1 py-1 px-2 rounded-xl transition {weatherStore.activeTab === 'forecast' ? 'text-cyan-400 font-semibold' : 'text-slate-400'}"
		>
			<CalendarDays class="w-4 h-4" />
			<span class="text-[9px]">{i18n.t('nav_forecast', 'Forecast')}</span>
		</button>

		<button
			onclick={() => handleNav('map')}
			class="flex flex-col items-center gap-1 py-1 px-2 rounded-xl transition {weatherStore.activeTab === 'map' ? 'text-cyan-400 font-semibold' : 'text-slate-400'}"
		>
			<MapIcon class="w-4 h-4" />
			<span class="text-[9px]">{i18n.t('nav_radar', 'Radar')}</span>
		</button>

		<!-- Disaster Monitor Page Link -->
		<a
			href="/disaster"
			class="flex flex-col items-center gap-1 py-1 px-2 rounded-xl text-rose-400 font-bold transition"
		>
			<div class="relative">
				<ShieldAlert class="w-4 h-4" />
				<span class="w-1.5 h-1.5 rounded-full bg-rose-500 absolute -top-0.5 -right-0.5 animate-ping"></span>
			</div>
			<span class="text-[9px]">{i18n.t('nav_disaster', 'Disaster')}</span>
		</a>

		<button
			onclick={() => handleNav('cities')}
			class="flex flex-col items-center gap-1 py-1 px-2 rounded-xl transition {weatherStore.activeTab === 'cities' ? 'text-cyan-400 font-semibold' : 'text-slate-400'}"
		>
			<Globe2 class="w-4 h-4" />
			<span class="text-[9px]">{i18n.t('nav_cities', 'Cities')}</span>
		</button>

		<!-- Search Trigger -->
		<button
			onclick={onSearchClick}
			class="p-2 rounded-xl bg-cyan-500 text-slate-950 font-bold shadow-lg shadow-cyan-500/30 active:scale-95 transition"
			aria-label="Search"
		>
			<Search class="w-3.5 h-3.5" />
		</button>
	</div>
</div>
