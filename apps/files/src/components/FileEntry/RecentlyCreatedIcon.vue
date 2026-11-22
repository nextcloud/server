<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcIconSvgWrapper class="recently-created-marker-icon" :name="t('files', 'Recently created')" :path="mdiPlus" />
</template>

<script lang="ts">
import { mdiPlus } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

/**
 * A recently created icon to be used for overlaying recently created entries like the file preview / icon
 * It has a stroke around the icon to ensure enough contrast for accessibility.
 *
 * If the background has a hover state you might want to also apply it to the stroke like this:
 * ```scss
 * .parent:hover :deep(.recently-created-marker-icon svg path) {
 *      stroke: var(--color-background-hover);
 * }
 * ```
 */
export default defineComponent({
	name: 'RecentlyCreatedIcon',
	components: {
		NcIconSvgWrapper,
	},

	setup() {
		return {
			mdiPlus,
		}
	},

	methods: {
		t,
	},
})
</script>

<style lang="scss" scoped>
.recently-created-marker-icon {
	color: var(--color-element-success);
	// Override NcIconSvgWrapper defaults (clickable area)
	min-width: unset !important;
	min-height: unset !important;

	:deep() {
		svg {
			// We added a stroke for a11y so we must increase the size to include the stroke
			width: 20px !important;
			height: 20px !important;

			// Override NcIconSvgWrapper defaults of 20px
			max-width: unset !important;
			max-height: unset !important;

			// Show a border around the icon for better contrast
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
