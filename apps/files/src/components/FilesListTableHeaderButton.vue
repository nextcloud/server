<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcButton :class="['files-list__column-sort-button', {
			'files-list__column-sort-button--active': sortingMode === mode,
			'files-list__column-sort-button--size': sortingMode === 'size',
		}]"
		:alignment="mode === 'size' ? 'end' : 'start-reverse'"
		type="tertiary"
		:title="name"
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
	margin: 0 calc(var(--button-padding, var(--cell-margin)) * -1);
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
