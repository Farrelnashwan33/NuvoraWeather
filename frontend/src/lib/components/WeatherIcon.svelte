<script>
	/**
	 * WeatherIcon component with smooth animations and weather-matched colors
	 */
	let { name = 'sun', size = 32, class: customClass = '' } = $props();
</script>

<div class="inline-flex items-center justify-center relative {customClass}">
	{#if name === 'sun'}
		<div class="relative flex items-center justify-center" style="width: {size}px; height: {size}px;">
			<!-- Glowing halo -->
			<div class="absolute inset-0 rounded-full bg-amber-400/20 blur-md animate-pulse"></div>
			<svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-400 drop-shadow-[0_0_8px_rgba(251,191,36,0.6)]">
				<circle cx="12" cy="12" r="4" fill="currentColor" fill-opacity="0.25"/>
				<path d="M12 2v2"/>
				<path d="M12 20v2"/>
				<path d="m4.93 4.93 1.41 1.41"/>
				<path d="m17.66 17.66 1.41 1.41"/>
				<path d="M2 12h2"/>
				<path d="M20 12h2"/>
				<path d="m6.34 17.66-1.41 1.41"/>
				<path d="m19.07 4.93-1.41 1.41"/>
			</svg>
		</div>

	{:else if name === 'moon'}
		<div class="relative flex items-center justify-center" style="width: {size}px; height: {size}px;">
			<div class="absolute inset-0 rounded-full bg-indigo-400/20 blur-md"></div>
			<svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-300 drop-shadow-[0_0_8px_rgba(165,180,252,0.5)]">
				<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" fill="currentColor" fill-opacity="0.2"/>
			</svg>
		</div>

	{:else if name === 'sun-cloud' || name === 'cloud-sun'}
		<div class="relative flex items-center justify-center" style="width: {size}px; height: {size}px;">
			<svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-cyan-300">
				<path d="M12 2v2" class="text-amber-400" stroke="currentColor"/>
				<path d="m4.93 4.93 1.41 1.41" class="text-amber-400" stroke="currentColor"/>
				<path d="M20 12h2" class="text-amber-400" stroke="currentColor"/>
				<path d="m19.07 4.93-1.41 1.41" class="text-amber-400" stroke="currentColor"/>
				<path d="M15.947 12.65a4 4 0 0 0-5.925-4.128" class="text-amber-400" stroke="currentColor"/>
				<path d="M13 22H7a5 5 0 1 1 4.9-6H13a3 3 0 0 1 0 6Z" fill="currentColor" fill-opacity="0.2" class="text-sky-300" stroke="currentColor"/>
			</svg>
		</div>

	{:else if name === 'moon-cloud' || name === 'cloud-moon'}
		<div class="relative flex items-center justify-center" style="width: {size}px; height: {size}px;">
			<svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-300">
				<path d="M10.188 8.5A6 6 0 0 1 16 4a7 7 0 0 0-6 8 6 6 0 0 0 0 2" stroke="currentColor" class="text-indigo-400"/>
				<path d="M17 19H7a5 5 0 0 1 1-9.9 5 5 0 0 1 9.8 1.9A3 3 0 0 1 17 19Z" fill="currentColor" fill-opacity="0.25" stroke="currentColor" class="text-sky-300"/>
			</svg>
		</div>

	{:else if name === 'cloud'}
		<div class="relative flex items-center justify-center" style="width: {size}px; height: {size}px;">
			<svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-300">
				<path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z" fill="currentColor" fill-opacity="0.2" stroke="currentColor"/>
			</svg>
		</div>

	{:else if name === 'cloud-rain' || name === 'cloud-drizzle'}
		<div class="relative flex items-center justify-center" style="width: {size}px; height: {size}px;">
			<svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-cyan-400 drop-shadow-[0_0_8px_rgba(34,211,238,0.4)]">
				<path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242" fill="currentColor" fill-opacity="0.15" stroke="currentColor"/>
				<path d="M16 14v6" class="text-cyan-300"/>
				<path d="M8 14v6" class="text-cyan-300"/>
				<path d="M12 16v6" class="text-cyan-300"/>
			</svg>
		</div>

	{:else if name === 'cloud-heavy-rain'}
		<div class="relative flex items-center justify-center" style="width: {size}px; height: {size}px;">
			<svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-400 drop-shadow-[0_0_10px_rgba(59,130,246,0.6)]">
				<path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242" fill="currentColor" fill-opacity="0.2" stroke="currentColor"/>
				<path d="m8 19-2 3" stroke="currentColor"/>
				<path d="m12 17-2 5" stroke="currentColor"/>
				<path d="m16 19-2 3" stroke="currentColor"/>
				<path d="m20 17-2 5" stroke="currentColor"/>
			</svg>
		</div>

	{:else if name === 'cloud-lightning'}
		<div class="relative flex items-center justify-center" style="width: {size}px; height: {size}px;">
			<svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-300 drop-shadow-[0_0_12px_rgba(251,191,36,0.7)]">
				<path d="M6 16.326A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 .5 8.973" fill="currentColor" fill-opacity="0.2" class="text-slate-300" stroke="currentColor"/>
				<path d="m13 10-3 5h4l-3 5" fill="currentColor" fill-opacity="0.3" class="text-amber-400 animate-pulse" stroke="currentColor"/>
			</svg>
		</div>

	{:else if name === 'snowflake' || name === 'cloud-snow'}
		<div class="relative flex items-center justify-center" style="width: {size}px; height: {size}px;">
			<svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-sky-200 drop-shadow-[0_0_8px_rgba(186,230,253,0.6)]">
				<path d="m10 20-1.25-2.5L6 18"/>
				<path d="M10 4 8.75 6.5 6 6"/>
				<path d="m14 20 1.25-2.5L18 18"/>
				<path d="m14 4 1.25 2.5L18 6"/>
				<path d="m17 21 3-6-1.5-3"/>
				<path d="m17 3 3 6-1.5 3"/>
				<path d="M2 12h20"/>
				<path d="m20 10-4-4"/>
				<path d="m20 14-4 4"/>
				<path d="m4 10 4-4"/>
				<path d="m4 14 4 4"/>
				<path d="m7 21-3-6 1.5-3"/>
				<path d="m7 3-3 6 1.5 3"/>
				<path d="M12 2v20"/>
			</svg>
		</div>

	{:else if name === 'cloud-fog'}
		<div class="relative flex items-center justify-center" style="width: {size}px; height: {size}px;">
			<svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-teal-300">
				<path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242" fill="currentColor" fill-opacity="0.15" stroke="currentColor"/>
				<path d="M4 17h16"/>
				<path d="M7 20h10"/>
			</svg>
		</div>

	{:else}
		<div class="relative flex items-center justify-center" style="width: {size}px; height: {size}px;">
			<svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-cyan-400">
				<circle cx="12" cy="12" r="10"/>
				<path d="M12 6v6l4 2"/>
			</svg>
		</div>
	{/if}
</div>
