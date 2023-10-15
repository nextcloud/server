<!--
  - @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
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
		:alignment="mode !== 'size' ? 'start-reverse' : 'center'"
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
import { translate } from '@nextcloud/l10n'
import MenuDown from 'vue-material-design-icons/MenuDown.vue'
import MenuUp from 'vue-material-design-icons/MenuUp.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import Vue from 'vue'

import filesSortingMixin from '../mixins/filesSorting.ts'

export default Vue.extend({
	name: 'FilesListTableHeaderButton',

	components: {
		MenuDown,
		MenuUp,
		NcButton,
	},

	mixins: [
		filesSortingMixin,
	],

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

	methods: {
		sortAriaLabel(column) {
			return this.t('files', 'Sort list by {column}', {
				column,
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
