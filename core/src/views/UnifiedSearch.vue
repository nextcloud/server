<!--
 - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="header-menu unified-search-menu">
		<NcButton class="header-menu__trigger"
			:aria-label="t('core', 'Unified search')"
			type="tertiary-no-background"
			@click="toggleUnifiedSearch">
			<template #icon>
				<Magnify class="header-menu__trigger-icon" :size="20" />
			</template>
		</NcButton>
		<UnifiedSearchModal :is-visible="showUnifiedSearch" @update:isVisible="handleModalVisibilityChange" />
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import Magnify from 'vue-material-design-icons/Magnify.vue'
import UnifiedSearchModal from './UnifiedSearchModal.vue'

export default {
	name: 'UnifiedSearch',
	components: {
		NcButton,
		Magnify,
		UnifiedSearchModal,
	},
	data() {
		return {
			showUnifiedSearch: false,
		}
	},
	mounted() {
		console.debug('Unified search initialized!')
		window.addEventListener('keydown', this.handleKeyDown)
	},
	beforeDestroy() {
		window.removeEventListener('keydown', this.handleKeyDown)
	},
	methods: {
		toggleUnifiedSearch() {
			this.showUnifiedSearch = !this.showUnifiedSearch
		},
		handleModalVisibilityChange(newVisibilityVal) {
			this.showUnifiedSearch = newVisibilityVal
		},
		handleKeyDown(event) {
			// if not already opened, allows us to trigger default browser on second keydown
			if (event.ctrlKey && event.code === 'KeyF' && !this.showUnifiedSearch) {
				event.preventDefault()
				this.showUnifiedSearch = true
			} else if (event.ctrlKey && event.key === 'f' && this.showUnifiedSearch) {
				// User wants to use the native browser search, so we close ours again
				this.showUnifiedSearch = false
			}
		},
	},
}
</script>

<style lang="scss" scoped>
// this is needed to allow us overriding component styles (focus-visible)
#header {
	.header-menu {
		display: flex;
		align-items: center;
		justify-content: center;

		&__trigger {
			height: var(--header-height);
			width: var(--header-height) !important;

			&:focus-visible {
				// align with other header menu entries
				outline: none !important;
				box-shadow: none !important;
			}

			&:not(:hover,:focus,:focus-visible) {
				opacity: .85;
			}

			&-icon {
				// ensure the icon has the correct color
				color: var(--color-background-plain-text) !important;
			}
		}
	}
}
</style>
