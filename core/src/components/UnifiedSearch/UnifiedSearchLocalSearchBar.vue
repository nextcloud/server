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
			<NcInputField class="local-unified-search__input animated-width"
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

			<NcButton class="local-unified-search__global-search"
				:aria-label="t('core', 'Search everywhere')"
				:title="t('core', 'Search everywhere')"
				type="tertiary-no-background"
				@click="$emit('global-search')">
				<template #icon>
					<NcIconSvgWrapper :path="mdiEarth" />
				</template>
			</NcButton>
		</div>
	</Transition>
</template>

<script lang="ts" setup>
import { mdiEarth, mdiClose } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcInputField from '@nextcloud/vue/dist/Components/NcInputField.js'

defineProps<{
	query: string,
	open: boolean
}>()

const emit = defineEmits<{
	(e: 'update:open', open: boolean): void
	(e: 'update:query', query: string): void
	(e: 'global-search'): void
}>()

function clearAndCloseSearch() {
	emit('update:query', '')
	emit('update:open', false)
}
</script>

<style scoped lang="scss">
.local-unified-search {
	--width: min(250px, 95vw);
	position: relative;
	height: var(--header-height);
	width: var(--width);
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
		inset-inline-end: 0;
	}

	#{&} &__input {
		// override some nextcloud-vue styles
		margin: 0;
		width: var(--width);

		// Fixup the spacing so we can fit in the "search globally" button
		// this can break at any time the component library changes
		:deep(input) {
			padding-inline-end: calc(2 * var(--default-clickable-area) + var(--default-grid-baseline));
		}
		:deep(button) {
			inset-inline-end: var(--default-clickable-area);
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
		// Start with only those two buttons + a little bit of the input element
		--width: calc(3 * var(--default-clickable-area));
	}
}

@media screen and (max-width: 500px) {
	.local-unified-search.local-unified-search--open {
		// 100% but still show the menu toggle on the very right
		--width: calc(100vw - (var(--clickable-area-large) + 5 * var(--default-grid-baseline)));
	}

	// when open we need to position it absolut to allow overlay the full bar
	:global(.unified-search-menu:has(.local-unified-search--open)) {
		position: absolute !important;
		// Keep showing the menu toggle
		inset-inline-end: calc(var(--clickable-area-large) + 4 * var(--default-grid-baseline));
	}
}
</style>
