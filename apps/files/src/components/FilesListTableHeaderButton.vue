<!--
  - @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  - @author Ferdinand Thiessen <opensource@fthiessen.de>
  -
  - @license AGPL-3.0-or-later
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
	<NcButton :class="['files-list__column-sort-button', {
			'files-list__column-sort-button--active': sortingMode === mode,
			'files-list__column-sort-button--size': sortingMode === 'size',
		}]"
		:alignment="mode === 'size' ? 'end' : 'start-reverse'"
		type="tertiary"
		@click="toggleSortBy(mode)">
		<template #icon>
			<MenuUp v-if="sortingMode !== mode || isAscSorting" class="files-list__column-sort-button-icon" />
			<MenuDown v-else class="files-list__column-sort-button-icon" />
		</template>
		<span class="files-list__column-sort-button-text">{{ name }}</span>
	</NcButton>
</template>

<script lang="ts">
import { translate } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import MenuDown from 'vue-material-design-icons/MenuDown.vue'
import MenuUp from 'vue-material-design-icons/MenuUp.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'

import filesSortingMixin from '../mixins/filesSorting.ts'

export default defineComponent({
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
		t: translate,
	},
})
</script>

<style scoped lang="scss">
.files-list__column-sort-button {
	// Compensate for cells margin
	margin: 0 calc(var(--cell-margin) * -1);
	min-width: calc(100% - 3 * var(--cell-margin))!important;

	&-text {
		color: var(--color-text-maxcontrast);
		font-weight: normal;
	}

	&-icon {
		color: var(--color-text-maxcontrast);
		opacity: 0;
		transition: opacity var(--animation-quick);
		inset-inline-start: -10px;
	}

	&--size &-icon {
		inset-inline-start: 10px;
	}

	&--active &-icon,
	&:hover &-icon,
	&:focus &-icon,
	&:active &-icon {
		opacity: 1;
	}
}
</style>
