<script>
	import { weatherStore } from '$lib/stores/weatherState.svelte.js';
	import { i18n } from '$lib/stores/i18n.svelte.js';
	import { Home, CalendarDays, Map as MapIcon, ShieldAlert, LayoutGrid, Search } from 'lucide-svelte';
	import { goto } from '$app/navigation';
	import NavigationDrawer from './NavigationDrawer.svelte';

	let { onSearchClick, onLanguageClick } = $props();
	let drawerOpen = $state(false);

	function handleNav(tab) {
		if (typeof window !== 'undefined') {
			if (window.location.pathname !== '/') {
				goto('/').then(() => {
					weatherStore.activeTab = tab;
					window.scrollTo({ top: 0, behavior: 'smooth' });
				});
			} else {
				weatherStore.activeTab = tab;
				window.scrollTo({ top: 0, behavior: 'smooth' });
			}
		}
	}
</script>

<!-- Mobile Fixed Bottom Navigation Bar -->
<div 
	class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-slate-950/95 backdrop-blur-2xl border-t border-white/10 px-2 py-2 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-10px_30px_rgba(0,0,0,0.8)] pointer-events-auto"
	style="transform: translateZ(0); -webkit-transform: translateZ(0); will-change: transform;"
>
	<div class="flex items-center justify-around max-w-md mx-auto">
		<!-- 1. Beranda -->
		<button
			onclick={() => handleNav('overview')}
			class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl transition-all duration-200 {weatherStore.activeTab === 'overview' ? 'text-cyan-400 font-semibold scale-105' : 'text-slate-400 hover:text-white'}"
		>
			<Home class="w-4 h-4" />
			<span class="text-[10px]">{i18n.t('nav_home', 'Beranda')}</span>
		</button>

		<!-- 2. Prakiraan -->
		<button
			onclick={() => handleNav('forecast')}
			class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl transition-all duration-200 {weatherStore.activeTab === 'forecast' ? 'text-cyan-400 font-semibold scale-105' : 'text-slate-400 hover:text-white'}"
		>
			<CalendarDays class="w-4 h-4" />
			<span class="text-[10px]">{i18n.t('nav_forecast', 'Prakiraan')}</span>
		</button>

		<!-- 3. Peta -->
		<button
			onclick={() => handleNav('map')}
			class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl transition-all duration-200 {weatherStore.activeTab === 'map' ? 'text-cyan-400 font-semibold scale-105' : 'text-slate-400 hover:text-white'}"
		>
			<MapIcon class="w-4 h-4" />
			<span class="text-[10px]">{i18n.t('nav_radar', 'Peta')}</span>
		</button>

		<!-- 4. Bencana -->
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

		<!-- 5. Menu (Bottom Sheet / Fullscreen Drawer) -->
		<button
			onclick={() => drawerOpen = true}
			class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl transition-all duration-200 {drawerOpen ? 'text-cyan-400 font-semibold scale-105' : 'text-slate-400 hover:text-cyan-300'}"
			aria-label="Buka Menu"
		>
			<div class="p-1 rounded-lg bg-cyan-500/15 text-cyan-400">
				<LayoutGrid class="w-4 h-4" />
			</div>
			<span class="text-[10px] font-semibold text-cyan-300">Menu</span>
		</button>
	</div>
</div>

<!-- Fullscreen / Bottom Sheet Menu Drawer -->
<NavigationDrawer bind:open={drawerOpen} {onLanguageClick} />
