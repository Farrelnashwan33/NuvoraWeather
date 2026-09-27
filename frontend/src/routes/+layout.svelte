<script>
	import '../app.css';
	import { onMount } from 'svelte';
	import { i18n } from '$lib/stores/i18n.svelte.js';

	let { children } = $props();

	onMount(() => {
		i18n.init();
	});
</script>

<div class="min-h-screen flex flex-col relative text-slate-100 selection:bg-cyan-500 selection:text-white">
	{@render children()}
</div>
