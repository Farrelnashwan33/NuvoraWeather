<script>
	/**
	 * WeatherBackground: Dynamic atmospheric backdrop matching current weather conditions
	 */
	let { theme = 'sunny', isDay = true } = $props();
</script>

<div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden transition-colors duration-1000">
	{#if theme === 'sunny'}
		<!-- Vibrant Sky & Solar Flare -->
		<div class="absolute inset-0 bg-gradient-to-b from-sky-950 via-slate-950 to-slate-950 dark:from-sky-950 dark:via-slate-950 dark:to-slate-950"></div>
		<div class="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-cyan-500/20 blur-[120px] animate-pulse-glow"></div>
		<div class="absolute top-1/4 -left-32 w-96 h-96 rounded-full bg-blue-600/15 blur-[140px] animate-pulse-glow" style="animation-delay: 3s;"></div>
		<div class="absolute top-1/2 right-1/4 w-80 h-80 rounded-full bg-indigo-500/10 blur-[130px]"></div>

	{:else if theme === 'clear-night'}
		<!-- Deep Midnight Cosmos with Stars -->
		<div class="absolute inset-0 bg-gradient-to-b from-indigo-950 via-slate-950 to-slate-950"></div>
		<div class="absolute -top-20 right-10 w-96 h-96 rounded-full bg-indigo-600/20 blur-[130px]"></div>
		<div class="absolute top-1/3 left-10 w-80 h-80 rounded-full bg-violet-600/15 blur-[140px]"></div>
		
		<!-- Starry specs -->
		<div class="absolute inset-0 opacity-40">
			<div class="absolute top-[10%] left-[20%] w-1 h-1 bg-white rounded-full animate-ping" style="animation-duration: 4s;"></div>
			<div class="absolute top-[18%] left-[75%] w-1 h-1 bg-cyan-200 rounded-full animate-pulse" style="animation-duration: 3s;"></div>
			<div class="absolute top-[40%] left-[45%] w-1.5 h-1.5 bg-blue-100 rounded-full animate-ping" style="animation-duration: 6s;"></div>
			<div class="absolute top-[65%] left-[85%] w-1 h-1 bg-indigo-200 rounded-full animate-pulse" style="animation-duration: 5s;"></div>
			<div class="absolute top-[75%] left-[15%] w-1 h-1 bg-sky-200 rounded-full animate-pulse" style="animation-duration: 4s;"></div>
			<div class="absolute top-[28%] left-[90%] w-1.5 h-1.5 bg-white rounded-full animate-ping" style="animation-duration: 7s;"></div>
		</div>

	{:else if theme === 'rainy'}
		<!-- Moody Rain Atmosphere with subtle glowing mist -->
		<div class="absolute inset-0 bg-gradient-to-b from-slate-950 via-cyan-950/40 to-slate-950"></div>
		<div class="absolute -top-10 left-1/3 w-[500px] h-[350px] rounded-full bg-cyan-600/15 blur-[150px]"></div>
		<div class="absolute top-1/2 -right-20 w-96 h-96 rounded-full bg-blue-800/20 blur-[130px]"></div>
		
		<!-- Rain streaks illusion -->
		<div class="absolute inset-0 opacity-20 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:24px_24px]"></div>

	{:else if theme === 'thunderstorm'}
		<!-- Electric Purple & Deep Navy with Lightning Aura -->
		<div class="absolute inset-0 bg-gradient-to-b from-slate-950 via-indigo-950/60 to-slate-950"></div>
		<div class="absolute -top-10 right-1/4 w-[600px] h-[400px] rounded-full bg-amber-500/10 blur-[160px] animate-pulse" style="animation-duration: 2s;"></div>
		<div class="absolute top-1/3 left-10 w-96 h-96 rounded-full bg-purple-700/25 blur-[140px]"></div>

	{:else if theme === 'snowy'}
		<!-- Crisp Frosty Ice Blue -->
		<div class="absolute inset-0 bg-gradient-to-b from-slate-900 via-sky-950/50 to-slate-950"></div>
		<div class="absolute -top-20 left-10 w-96 h-96 rounded-full bg-sky-300/15 blur-[130px]"></div>
		<div class="absolute top-1/2 right-10 w-80 h-80 rounded-full bg-indigo-400/10 blur-[140px]"></div>

	{:else}
		<!-- Default / Partly Cloudy / Overcast -->
		<div class="absolute inset-0 bg-gradient-to-b from-slate-950 via-slate-900/60 to-slate-950"></div>
		<div class="absolute -top-20 right-20 w-[500px] h-[350px] rounded-full bg-blue-600/15 blur-[140px]"></div>
		<div class="absolute top-1/2 -left-20 w-96 h-96 rounded-full bg-cyan-700/10 blur-[150px]"></div>
	{/if}

	<!-- Subtle grid overlay for high-tech SaaS weather dashboard feel -->
	<div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(255,255,255,0.015)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.015)_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)]"></div>
</div>
