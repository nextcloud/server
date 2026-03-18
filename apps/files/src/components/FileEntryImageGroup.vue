<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<tr
		class="files-list__row files-list__row--image-group"
		:class="{
			'files-list__row--image-group-expanded': source.expanded,
			'files-list__row--active': isSelected,
		}">
		<td class="files-list__row-checkbox" @click.stop>
			<NcCheckboxRadioSwitch
				:aria-label="t('files', 'Toggle selection for image group')"
				:modelValue="isSelected"
				:indeterminate="isPartiallySelected"
				@update:modelValue="onSelectionChange" />
		</td>

		<td class="files-list__row-name" @click="$emit('toggle', source.source)">
			<span class="files-list__row-icon">
				<ImageMultipleIcon :size="20" />
			</span>

			<span class="files-list__row-image-group-chevron">
				<ChevronRightIcon v-if="!source.expanded" :size="20" />
				<ChevronDownIcon v-else :size="20" />
			</span>

			<span class="files-list__row-name-text">
				{{ n('files', '{count} image', '{count} images', source.images.length, { count: source.images.length }) }}
			</span>
		</td>

		<td v-if="isMimeAvailable" class="files-list__row-mime" />
		<td v-if="isSizeAvailable" class="files-list__row-size" />
		<td v-if="isMtimeAvailable" class="files-list__row-mtime" />
	</tr>
</template>

<script lang="ts">
import type { PropType } from 'vue'
import type { ImageGroupNode } from '../composables/useImageGrouping.ts'

import { n, t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import ChevronDownIcon from 'vue-material-design-icons/ChevronDown.vue'
import ChevronRightIcon from 'vue-material-design-icons/ChevronRight.vue'
import ImageMultipleIcon from 'vue-material-design-icons/ImageMultiple.vue'
import { useSelectionStore } from '../store/selection.ts'

export default defineComponent({
	name: 'FileEntryImageGroup',

	components: {
		ChevronDownIcon,
		ChevronRightIcon,
		ImageMultipleIcon,
		NcCheckboxRadioSwitch,
	},

	props: {
		source: {
			type: Object as PropType<ImageGroupNode>,
			required: true,
		},

		isMimeAvailable: {
			type: Boolean,
			default: false,
		},

		isSizeAvailable: {
			type: Boolean,
			default: false,
		},

		isMtimeAvailable: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['toggle'],

	setup() {
		const selectionStore = useSelectionStore()
		return { selectionStore, n, t }
	},

	computed: {
		childSources() {
			return this.source.images.map((img) => img.source)
		},

		isSelected() {
			return this.childSources.every((src) => this.selectionStore.selected.includes(src))
		},

		isPartiallySelected() {
			return !this.isSelected && this.childSources.some((src) => this.selectionStore.selected.includes(src))
		},
	},

	methods: {
		onSelectionChange(selected: boolean) {
			const current = this.selectionStore.selected
			if (selected) {
				// select all children
				this.selectionStore.set([...new Set([...current, ...this.childSources])])
			} else {
				// unselect all children
				this.selectionStore.set(current.filter((src) => !this.childSources.includes(src)))
			}
		},

		onRowClick() {
			this.onSelectionChange(!this.isSelected)
		},
	},
})
</script>

<style scoped lang="scss">
.files-list__row--image-group {
    .files-list__row-name {
        cursor: pointer;
        * {
            cursor: pointer;
        }
    }

    .files-list__row-image-group-chevron {
        display: flex;
        align-items: center;
        color: var(--color-text-maxcontrast);
    }

    .files-list__row-name-text {
        color: var(--color-main-text);
    }
}
</style>
