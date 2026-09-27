<script>
	import { i18n, LANGUAGES } from '$lib/stores/i18n.svelte.js';
	import { X, Search, Globe, Check } from 'lucide-svelte';
	import { fade, scale } from 'svelte/transition';

	let { open = $bindable(false) } = $props();
	let searchQuery = $state('');

	let filteredLanguages = $derived(
		LANGUAGES.filter(l => 
			l.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
			l.nativeName.toLowerCase().includes(searchQuery.toLowerCase()) ||
			l.code.toLowerCase().includes(searchQuery.toLowerCase())
		)
	);

	function selectLang(code) {
		i18n.setLanguage(code);
		open = false;
		searchQuery = '';
	}
</script>

{#if open}
	<div 
		transition:fade={{ duration: 150 }}
		class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-3 sm:p-4"
	>
		<!-- Backdrop click -->
		<div 
			role="button" 
			tabindex="0"
			class="absolute inset-0" 
			onclick={() => open = false}
			onkeydown={(e) => e.key === 'Escape' && (open = false)}
		></div>

		<div 
			transition:scale={{ start: 0.95, duration: 200 }}
			class="relative w-full max-w-md bg-slate-900/95 border border-white/10 rounded-3xl p-5 sm:p-6 shadow-2xl shadow-cyan-500/10 space-y-4 z-10"
		>
			<!-- Modal Header -->
			<div class="flex items-center justify-between pb-3 border-b border-white/5">
				<div class="flex items-center gap-2.5">
					<div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-cyan-500 to-blue-600 p-0.5 shadow-md shadow-cyan-500/20">
						<div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
							<Globe class="w-4 h-4 text-cyan-400" />
						</div>
					</div>
					<div>
						<h3 class="font-heading font-bold text-base text-white">{i18n.t('select_language', 'Pilih Bahasa Dunia')}</h3>
						<p class="text-[11px] text-slate-400">17+ World Languages Supported</p>
					</div>
				</div>

				<button 
					onclick={() => open = false}
					class="p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 transition"
					aria-label="Tutup modal bahasa"
				>
					<X class="w-4 h-4" />
				</button>
			</div>

			<!-- Search Filter -->
			<div class="relative">
				<Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
				<input 
					type="text"
					bind:value={searchQuery}
					placeholder="Search language / Cari bahasa..."
					class="w-full pl-9 pr-4 py-2.5 rounded-xl glass-input text-xs text-white placeholder-slate-500"
				/>
			</div>

			<!-- Languages Grid -->
			<div class="max-h-72 overflow-y-auto pr-1 space-y-1.5 custom-scrollbar">
				{#each filteredLanguages as lang}
					<button
						onclick={() => selectLang(lang.code)}
						class="w-full flex items-center justify-between p-2.5 sm:p-3 rounded-2xl transition duration-150 {i18n.current === lang.code ? 'bg-gradient-to-r from-cyan-500/20 to-blue-500/10 border border-cyan-500/40 text-white shadow-sm' : 'bg-slate-950/40 border border-white/5 hover:bg-white/5 text-slate-300'}"
					>
						<div class="flex items-center gap-3">
							<span class="text-xl shrink-0">{lang.flag}</span>
							<div class="text-left">
								<div class="text-xs font-semibold leading-tight {i18n.current === lang.code ? 'text-cyan-300' : 'text-white'}">
									{lang.nativeName}
								</div>
								<div class="text-[10px] text-slate-400">{lang.name}</div>
							</div>
						</div>

						<div class="flex items-center gap-2">
							<span class="font-mono text-[10px] px-1.5 py-0.5 rounded uppercase font-bold {i18n.current === lang.code ? 'bg-cyan-400/20 text-cyan-300 border border-cyan-400/30' : 'bg-slate-800 text-slate-400'}">
								{lang.code}
							</span>
							{#if i18n.current === lang.code}
								<Check class="w-4 h-4 text-cyan-400 shrink-0" />
							{/if}
						</div>
					</button>
				{/each}

				{#if filteredLanguages.length === 0}
					<div class="text-center py-6 text-xs text-slate-500">
						No language matching "{searchQuery}"
					</div>
				{/if}
			</div>
		</div>
	</div>
{/if}

<style>
	.custom-scrollbar::-webkit-scrollbar {
		width: 4px;
	}
	.custom-scrollbar::-webkit-scrollbar-track {
		background: rgba(255, 255, 255, 0.02);
		border-radius: 4px;
	}
	.custom-scrollbar::-webkit-scrollbar-thumb {
		background: rgba(255, 255, 255, 0.15);
		border-radius: 4px;
	}
</style>
