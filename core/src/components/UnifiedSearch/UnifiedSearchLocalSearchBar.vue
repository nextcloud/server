<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<Transition>
		<div v-if="open"
			class="local-unified-search animated-width"
			:class="{ 'local-unified-search--open': open }">
			<!-- We can not use labels as it breaks the header layout so only aria-label and placeholder -->
			<NcInputField ref="searchInput"
				class="local-unified-search__input animated-width"
				:aria-label="t('core', 'Search in current app')"
				:placeholder="t('core', 'Search in current app')"
				show-trailing-button
				:trailing-button-label="t('core', 'Clear search')"
				:value="query"
				@update:value="$emit('update:query', $event)"
				@trailing-button-click="clearAndCloseSearch">
				<template #trailing-button-icon>
					<NcIconSvgWrapper :path="mdiClose" />
				</template>
			</NcInputField>

			<NcButton ref="searchGlobalButton"
				class="local-unified-search__global-search"
				:aria-label="t('core', 'Search everywhere')"
				:title="t('core', 'Search everywhere')"
				type="tertiary-no-background"
				@click="$emit('global-search')">
				<template v-if="!isMobile" #default>
					{{ t('core', 'Search everywhere') }}
				</template>
				<template #icon>
					<NcIconSvgWrapper :path="mdiCloudSearch" />
				</template>
			</NcButton>
		</div>
	</Transition>
</template>

<script lang="ts" setup>
import type { ComponentPublicInstance } from 'vue'
import { mdiCloudSearch, mdiClose } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { useIsMobile } from '@nextcloud/vue/dist/Composables/useIsMobile.js'
import { computed, ref, watchEffect } from 'vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcInputField from '@nextcloud/vue/dist/Components/NcInputField.js'
import { useElementSize } from '@vueuse/core'

const props = defineProps<{
	query: string,
	open: boolean
}>()

const emit = defineEmits<{
	(e: 'update:open', open: boolean): void
	(e: 'update:query', query: string): void
	(e: 'global-search'): void
}>()

// Hacky type until the library provides real Types
type FocusableComponent = ComponentPublicInstance<object, object, object, Record<string, never>, { focus: () => void }>
/** The input field component */
const searchInput = ref<FocusableComponent>()
/** When the search bar is opened we focus the input */
watchEffect(() => {
	if (props.open && searchInput.value) {
		searchInput.value.focus()
	}
})

/** Current window size is below the "mobile" breakpoint (currently 1024px) */
const isMobile = useIsMobile()

const searchGlobalButton = ref<ComponentPublicInstance>()
/** Width of the search global button, used to resize the input field */
const { width: searchGlobalButtonWidth } = useElementSize(searchGlobalButton)
const searchGlobalButtonCSSWidth = computed(() => searchGlobalButtonWidth.value ? `${searchGlobalButtonWidth.value}px` : 'var(--default-clickable-area)')

/**
 * Clear the search query and close the search bar
 */
function clearAndCloseSearch() {
	emit('update:query', '')
	emit('update:open', false)
}
</script>

<style scoped lang="scss">
.local-unified-search {
	--local-search-width: min(calc(250px + v-bind('searchGlobalButtonCSSWidth')), 95vw);
	box-sizing: border-box;
	position: relative;
	height: var(--header-height);
	width: var(--local-search-width);
	display: flex;
	align-items: center;
	// Ensure it overlays the other entries
	z-index: 10;
	// add some padding for the focus visible outline
	padding-inline: var(--border-width-input-focused);
	// hide the overflow - needed for the transition
	overflow: hidden;
	// Ensure the position is fixed also during "position: absolut" (transition)
	inset-inline-end: 0;

	#{&} &__global-search {
		position: absolute;
		inset-inline-end: var(--default-clickable-area);
	}

	#{&} &__input {
		box-sizing: border-box;
		// override some nextcloud-vue styles
		margin: 0;
		width: var(--local-search-width);

		// Fixup the spacing so we can fit in the "search globally" button
		// this can break at any time the component library changes
		:deep(input) {
			// search global width + close button width
			padding-inline-end: calc(v-bind('searchGlobalButtonWidth') + var(--default-clickable-area));
		}
	}
}

.animated-width {
	transition: width var(--animation-quick) linear;
}

// Make the position absolut during the transition
// this is needed to "hide" the button begind it
.v-leave-active {
	position: absolute !important;
}

.v-enter,
.v-leave-to {
	&.local-unified-search {
		// Start with only the overlayed button
		--local-search-width: var(--clickable-area-large);
	}
}

@media screen and (max-width: 500px) {
	.local-unified-search.local-unified-search--open {
		// 100% but still show the menu toggle on the very right
		--local-search-width: 100vw;
		padding-inline: var(--default-grid-baseline);
	}

	// when open we need to position it absolute to allow overlay the full bar
	:global(.unified-search-menu:has(.local-unified-search--open)) {
		position: absolute !important;
		inset-inline: 0;
	}
	// Hide all other entries, especially the user menu as it might leak pixels
	:global(.header-end:has(.local-unified-search--open) > :not(.unified-search-menu)) {
		display: none;
	}
}
</style>
