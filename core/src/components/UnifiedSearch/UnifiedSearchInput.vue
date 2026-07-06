<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<search class="unified-search-input" :class="[{ 'unified-search-input--mobile': isSmallMobile }]">
		<NcHeaderButton
			v-if="isSmallMobile"
			:aria-label="placeholderText"
			aria-haspopup="dialog"
			:aria-expanded="expanded ? 'true' : 'false'"
			@click="$emit('click', $event)">
			<template #icon>
				<IconMagnify :size="20" />
			</template>
		</NcHeaderButton>
		<button
			v-else
			type="button"
			class="unified-search-input__button"
			aria-haspopup="dialog"
			:aria-expanded="expanded ? 'true' : 'false'"
			@click="$emit('click', $event)">
			<IconMagnify
				class="unified-search-input__icon"
				:size="20"
				aria-hidden="true" />
			<span class="unified-search-input__label">
				{{ placeholderText }}
			</span>
		</button>
	</search>
</template>

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { useIsSmallMobile } from '@nextcloud/vue/composables/useIsMobile'
import NcHeaderButton from '@nextcloud/vue/components/NcHeaderButton'
import IconMagnify from 'vue-material-design-icons/Magnify.vue'

/**
 * First phase of the unified-search input: a button styled to look like an
 * input field that opens the unified-search modal on click. A later phase
 * will replace the button with a real input that filters results inline.
 *
 * Implemented as a custom component because no `@nextcloud/vue` component
 * fits the design role here: NcInputField is a real input whose styling
 * assumes a light page background and clashes with the themed header,
 * and NcTextField has the same issue. On narrow viewports the trigger
 * collapses to a standard NcHeaderButton so it matches the visual
 * language of the other header items.
 */

defineProps<{
	/** Whether the popup the input controls is currently open. Bound to aria-expanded. */
	expanded?: boolean
}>()

defineEmits<{
	click: [mouseEvent: MouseEvent]
}>()

const isSmallMobile = useIsSmallMobile()
const placeholderText = t('core', 'Search apps, files, tags, messages …')
</script>

<style lang="scss" scoped>
.unified-search-input {
	&:not(.unified-search-input--mobile) {
		display: flex;
		align-items: center;
		width: clamp(200px, 35vw, 600px);
		max-width: calc(100% - 32px);
		pointer-events: none;
	}

	&--mobile {
		display: contents;
	}

	&__button {
		pointer-events: auto;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		width: 100%;
		height: calc(var(--default-clickable-area) - 8px);
		padding: 0 12px;
		border: none;
		border-radius: var(--border-radius-element, 8px);
		background-color: rgba(0, 0, 0, 0.15);
		-webkit-backdrop-filter: var(--filter-background-blur);
		backdrop-filter: var(--filter-background-blur);
		box-shadow: inset 0 2px 0 rgba(0, 0, 0, 0.12);
		color: color-mix(in srgb, var(--color-background-plain-text) 70%, var(--color-background-plain));
		cursor: pointer;
		text-align: center;
		font: inherit;
		transition: background-color var(--animation-quick) ease-in-out;

		&:hover {
			background-color: rgba(0, 0, 0, 0.22);
		}

		&:focus-visible {
			background-color: rgba(0, 0, 0, 0.22);
			outline: 2px solid var(--color-background-plain-text);
			outline-offset: 2px;
		}

		&:active {
			background-color: rgba(0, 0, 0, 0.28) !important;
			color: var(--color-background-plain-text) !important;
			outline: none;
		}
	}

	&__icon {
		flex-shrink: 0;
		display: flex;
		align-items: center;
	}

	&__label {
		min-width: 0;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
}

.unified-search-input--mobile :deep(.header-menu) {
	height: var(--default-clickable-area);
}

.unified-search-input--mobile :deep(.header-menu__trigger) {
	--button-size: var(--default-clickable-area) !important;
	height: var(--default-clickable-area) !important;
}

.unified-search-input--mobile :deep(.button-vue) {
	--color-main-text: var(--color-background-plain-text);
	color: var(--color-background-plain-text);
	border-radius: var(--border-radius-element) !important;

	&:hover:not(:disabled) {
		background-color: rgba(0, 0, 0, 0.1) !important;
	}

	&:active:not(:disabled) {
		background-color: rgba(0, 0, 0, 0.15) !important;
	}

	&:focus-visible {
		background-color: rgba(0, 0, 0, 0.1) !important;
		outline: none !important;
		box-shadow: inset 0 0 0 2px var(--color-background-plain-text) !important;
	}
}

[data-theme-dark] .unified-search-input__button,
[data-theme-dark-highcontrast] .unified-search-input__button {
	background-color: color-mix(in srgb, var(--color-primary-element) 16%, transparent);

	&:hover {
		background-color: color-mix(in srgb, var(--color-primary-element) 22%, transparent);
	}

	&:focus-visible {
		background-color: color-mix(in srgb, var(--color-primary-element) 22%, transparent);
	}

	&:active {
		background-color: color-mix(in srgb, var(--color-primary-element) 28%, transparent) !important;
		color: var(--color-background-plain-text) !important;
		outline: none;
	}
}
</style>
