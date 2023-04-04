<!--
  - @copyright Copyright (c) 2019 Gary Kim <gary@garykim.dev>
  -
  - @author Gary Kim <gary@garykim.dev>
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
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<NcButton :aria-label="sortAriaLabel(name)"
		:class="{'files-list__column-sort-button--active': sortingMode === mode}"
		class="files-list__column-sort-button"
		type="tertiary"
		@click.stop.prevent="toggleSortBy(mode)">
		<!-- Sort icon before text as size is align right -->
		<MenuUp v-if="sortingMode !== mode || isAscSorting" slot="icon" />
		<MenuDown v-else slot="icon" />
		{{ name }}
	</NcButton>
</template>

<script lang="ts">
import { mapState } from 'pinia'
import { translate } from '@nextcloud/l10n'
import MenuDown from 'vue-material-design-icons/MenuDown.vue'
import MenuUp from 'vue-material-design-icons/MenuUp.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import Vue from 'vue'

import { useSortingStore } from '../store/sorting'

export default Vue.extend({
	name: 'FilesListHeaderButton',

	components: {
		MenuDown,
		MenuUp,
		NcButton,
	},

	inject: ['toggleSortBy'],

	props: {
		name: {
			type: String,
			required: true,
		},
		mode: {
			type: String,
			required: true,
		},
	},

	setup() {
		const sortingStore = useSortingStore()
		return {
			sortingStore,
		}
	},

	computed: {
		...mapState(useSortingStore, ['filesSortingConfig']),

		currentView() {
			return this.$navigation.active
		},

		sortingMode() {
			return this.sortingStore.getSortingMode(this.currentView.id)
				|| this.currentView.defaultSortKey
				|| 'basename'
		},
		isAscSorting() {
			return this.sortingStore.isAscSorting(this.currentView.id) === true
		},
	},

	methods: {
		sortAriaLabel(column) {
			const direction = this.isAscSorting
				? this.t('files', 'ascending')
				: this.t('files', 'descending')
			return this.t('files', 'Sort list by {column} ({direction})', {
				column,
				direction,
			})
		},

		t: translate,
	},
})
</script>

<style lang="scss">
.files-list__column-sort-button {
	// Compensate for cells margin
	margin: 0 calc(var(--cell-margin) * -1);
	// Reverse padding
	padding: 0 4px 0 16px !important;

	// Icon after text
	.button-vue__wrapper {
		flex-direction: row-reverse;
		// Take max inner width for text overflow ellipsis
		// Remove when https://github.com/nextcloud/nextcloud-vue/pull/3936 is merged
		width: 100%;
	}

	.button-vue__icon {
		transition-timing-function: linear;
		transition-duration: .1s;
		transition-property: opacity;
		opacity: 0;
	}

	// Remove when https://github.com/nextcloud/nextcloud-vue/pull/3936 is merged
	.button-vue__text {
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}

	&--active,
	&:hover,
	&:focus,
	&:active {
		.button-vue__icon {
			opacity: 1 !important;
		}
	}
}
</style>
