<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="files-list__header-home-search-wrapper">
		<NcTextField v-model="searchText"
			class="files-list__header-home-search-input"
			:label="t('files', 'Search files and folders')"
			:pill="true"
			trailing-button-icon="close"
			:trailing-button-label="t('files', 'Clear search')"
			:show-trailing-button="searchText.trim() !== ''"
			type="search"
			@trailing-button-click="searchText = ''">
			<template #icon>
				<Magnify :size="20" />
			</template>
		</NcTextField>
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { t } from '@nextcloud/l10n'

import NcTextField from '@nextcloud/vue/components/NcTextField'
import Magnify from 'vue-material-design-icons/Magnify.vue'

export default defineComponent({
	name: 'FilesHeaderHomeSearch',

	components: {
		NcTextField,
		Magnify,
	},

	setup() {
		return {
			t,
		}
	},

	data() {
		return {
			searchText: '',
		}
	},

	methods: {
	},
})
</script>

<style lang="scss">
// Align everything in the middle
.files-list__header-home-search-wrapper,
.files-list__filters {
	display: flex !important;
	max-width: var(--breakpoint-mobile) !important;
	height: auto !important;
	margin: 0 auto !important;
	padding-inline: calc(var(--clickable-area-small, 24px) / 2) !important;
}

.files-list__header-home-search-wrapper {
	// global default is 34px, but we want to have a bigger clickable area
	--default-clickable-area: var(---clickable-area-large, 48px);
	justify-content: center;
}

// Align the filters with the search input for the Home view
.files-list__filters {
	padding-block: calc(var(--default-grid-baseline, 4px) * 2) !important;
}
</style>
