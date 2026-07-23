<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<!--
 - DEBUG-ONLY, THROWAWAY: previews unified-search reveal/blocking timing for the
 - designer. Gated behind ?searchDebug and only exists on the debug branch. Do not
 - ship. Slow a high-priority provider past the reveal interval and the lower ones
 - visibly reveal underneath it. The status dot shows each category's live state.
-->
<template>
	<div class="search-debug">
		<p class="search-debug__title">
			Search debug
		</p>
		<p class="search-debug__legend">
			🟢 loaded · 🟡 loading · 🔴 blocked · ⚫ failed · ⚪ idle
		</p>

		<div class="search-debug__row">
			<span class="search-debug__status" aria-hidden="true"></span>
			<label class="search-debug__label" for="search-debug-reveal">Reveal</label>
			<input
				id="search-debug-reveal"
				type="range"
				min="0"
				max="5000"
				step="100"
				:value="reveal"
				class="search-debug__slider"
				@input="onReveal"
				@change="commitReveal">
			<span class="search-debug__value">{{ reveal }}ms</span>
			<span aria-hidden="true"></span>
		</div>

		<hr class="search-debug__divider">

		<div
			v-for="provider in sortedProviders"
			:key="provider.id"
			class="search-debug__row">
			<span class="search-debug__status" :title="statusLabel(provider.id)">{{ statusEmoji(provider.id) }}</span>
			<label class="search-debug__label" :for="`search-debug-delay-${provider.id}`">
				{{ provider.name }}
			</label>
			<input
				:id="`search-debug-delay-${provider.id}`"
				type="range"
				min="0"
				max="5000"
				step="250"
				:value="delays[provider.id] || 0"
				class="search-debug__slider"
				@input="onDelay(provider.id, $event)"
				@change="commitDelay(provider.id)">
			<span class="search-debug__value">{{ delays[provider.id] || 0 }}ms</span>
			<input
				type="checkbox"
				class="search-debug__fail"
				:checked="fails[provider.id] || false"
				:title="`Fail ${provider.name}`"
				@change="onFail(provider.id, $event)">
		</div>
	</div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

const props = withDefaults(defineProps<{
	providers: { id: string, name: string, order?: number }[]
	revealInterval?: number
	states?: Record<string, { status: string }>
}>(), {
	revealInterval: 1500,
	states: () => ({}),
})

// Show providers in priority order (lowest `order` first), matching how the
// controller blocks lower-priority categories behind higher-priority ones.
const sortedProviders = computed(() => [...props.providers].sort((a, b) => (a.order ?? 0) - (b.order ?? 0)))

const emit = defineEmits(['update:revealInterval', 'setDelay', 'setFail'])

// Live slider values. delays is reassigned wholesale (not mutated per key) so
// Vue 2.7 reactivity picks up new provider entries. @input keeps the readout in
// sync while dragging; @change applies on release, so we don't re-search per tick.
const reveal = ref(props.revealInterval)
const delays = ref<Record<string, number>>({})
const fails = ref<Record<string, boolean>>({})

/**
 * Map a category's live controller status to a coloured dot.
 *
 * @param id the provider id
 */
function statusEmoji(id: string): string {
	switch (props.states[id]?.status) {
		case 'loaded': return '🟢'
		case 'loading': return '🟡'
		case 'blocked': return '🔴'
		case 'failed': return '⚫'
		default: return '⚪'
	}
}

/**
 * Human-readable status for the dot's tooltip.
 *
 * @param id the provider id
 */
function statusLabel(id: string): string {
	return props.states[id]?.status ?? 'idle'
}

/**
 * @param event the reveal slider input event
 */
function onReveal(event: Event) {
	reveal.value = Number((event.target as HTMLInputElement).value)
}

/**
 * Apply the reveal interval on slider release.
 */
function commitReveal() {
	emit('update:revealInterval', reveal.value)
}

/**
 * @param id the provider id
 * @param event the latency slider input event
 */
function onDelay(id: string, event: Event) {
	delays.value = { ...delays.value, [id]: Number((event.target as HTMLInputElement).value) }
}

/**
 * @param id the provider id
 */
function commitDelay(id: string) {
	emit('setDelay', id, delays.value[id] ?? 0)
}

/**
 * Toggle a simulated failure for a provider.
 *
 * @param id the provider id
 * @param event the checkbox change event
 */
function onFail(id: string, event: Event) {
	const fail = (event.target as HTMLInputElement).checked
	fails.value = { ...fails.value, [id]: fail }
	emit('setFail', id, fail)
}
</script>

<style lang="scss" scoped>
.search-debug {
	position: fixed;
	inset-block-start: calc(var(--header-height, 50px) + 12px);
	inset-inline-end: 12px;
	z-index: 2000;
	width: 300px;
	padding: 12px 14px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large, 12px);
	background-color: var(--color-main-background);
	color: var(--color-main-text);
	box-shadow: 0 2px 16px rgba(0, 0, 0, 0.3);
	font-size: 13px;

	&__title {
		font-weight: bold;
		color: var(--color-text-maxcontrast);
	}

	&__legend {
		margin-block-end: 8px;
		font-size: 11px;
		color: var(--color-text-maxcontrast);
	}

	&__divider {
		margin-block: 8px;
		border: none;
		border-top: 1px solid var(--color-border);
	}

	&__row {
		display: grid;
		grid-template-columns: 16px 52px 1fr 40px 16px;
		align-items: center;
		gap: 8px;
		margin-block: 4px;
	}

	&__fail {
		cursor: pointer;
		accent-color: var(--color-error, #c33);
	}

	&__status {
		font-size: 11px;
		line-height: 1;
	}

	&__label {
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}

	&__slider {
		width: 100%;
		accent-color: var(--color-primary-element);
		cursor: pointer;
	}

	&__value {
		text-align: end;
		font-variant-numeric: tabular-nums;
		color: var(--color-text-maxcontrast);
	}
}
</style>
