 <!--
  - @copyright Copyright (c) 2020 Fon E. Noel NFEBE <fenn25.fn@gmail.com>
  -
  - @author Fon E. Noel NFEBE <fenn25.fn@gmail.com>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<div class="header-menu">
		<NcButton class="unified-search__button" :aria-label="t('core', 'Unified search')" @click="toggleUnifiedSearch">
			<template #icon>
				<Magnify class="unified-search__trigger" :size="22" />
			</template>
		</NcButton>
		<UnifiedSearchModal :class="'unified-search-modal'" :is-visible="showUnifiedSearch" @update:isVisible="handleModalVisibilityChange" />
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
	},
	methods: {
		toggleUnifiedSearch() {
			this.showUnifiedSearch = !this.showUnifiedSearch
		},
		handleModalVisibilityChange(newVisibilityVal) {
			this.showUnifiedSearch = newVisibilityVal
		},
	},
}
</script>

<style lang="scss" scoped>
.header-menu {
	display: flex;
	align-items: center;
	justify-content: center;

	.unified-search__button {
		display: flex;
		align-items: center;
		justify-content: center;
		width: var(--header-height);
		// height: var(--header-height);
		margin: 0;
		padding: 0;
		cursor: pointer;
		opacity: .85;
		background-color: transparent;
		border: none;
		filter: none !important;
		color: var(--color-primary-text) !important;

		&:hover {
			background-color: transparent !important;
		}
	}
}

.unified-search-modal {
	::v-deep .modal-container {
		height: 80%;
	}
}
</style>
