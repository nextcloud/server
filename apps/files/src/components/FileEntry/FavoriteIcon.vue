<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcIconSvgWrapper class="favorite-marker-icon" :name="t('files', 'Favorite')" :svg="StarSvg" />
</template>

<script lang="ts">
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import StarSvg from '@mdi/svg/svg/star.svg?raw'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

/**
 * A favorite icon to be used for overlaying favorite entries like the file preview / icon
 * It has a stroke around the star icon to ensure enough contrast for accessibility.
 *
 * If the background has a hover state you might want to also apply it to the stroke like this:
 * ```scss
 * .parent:hover :deep(.favorite-marker-icon svg path) {
 *      stroke: var(--color-background-hover);
 * }
 * ```
 */
export default defineComponent({
	name: 'FavoriteIcon',
	components: {
		NcIconSvgWrapper,
	},
	data() {
		return {
			StarSvg,
		}
	},
	async mounted() {
		await this.$nextTick()
		// MDI default viewBox is "0 0 24 24" but we add a stroke of 10px so we must adjust it
		const el = this.$el.querySelector('svg')
		el?.setAttribute?.('viewBox', '-4 -4 30 30')
	},
	methods: {
		t,
	},
})
</script>

<style lang="scss" scoped>
.favorite-marker-icon {
	color: var(--color-favorite);
	// Override NcIconSvgWrapper defaults (clickable area)
	min-width: unset !important;
    min-height: unset !important;

	:deep() {
		svg {
			// We added a stroke for a11y so we must increase the size to include the stroke
			width: 26px !important;
			height: 26px !important;

			// Override NcIconSvgWrapper defaults of 20px
			max-width: unset !important;
			max-height: unset !important;

			// Sow a border around the icon for better contrast
			path {
				stroke: var(--color-main-background);
				stroke-width: 8px;
				stroke-linejoin: round;
				paint-order: stroke;
			}
		}
	}
}
</style>
