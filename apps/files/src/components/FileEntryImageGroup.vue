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

        <td class="files-list__row-name" @click="emit('toggle', source.source)">
            <span class="files-list__row-icon">
                <ImageMultipleIcon :size="20" />
            </span>

            <span class="files-list__row-image-group-chevron">
                <NcIconSvgWrapper
                    :path="mdiChevronDown"
                    :size="20"
                    :class="{ 'files-list__row-image-group-chevron--expanded': source.expanded }" />
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

<script lang="ts" setup>
import type { ImageGroupNode } from '../composables/useImageGrouping.ts'

import { mdiChevronDown } from '@mdi/js'
import { n, t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import ImageMultipleIcon from 'vue-material-design-icons/ImageMultiple.vue'
import { useSelectionStore } from '../store/selection.ts'

const props = defineProps<{
    source: ImageGroupNode
    isMimeAvailable?: boolean
    isSizeAvailable?: boolean
    isMtimeAvailable?: boolean
}>()

const emit = defineEmits<{
    (e: 'toggle', key: string): void
}>()

const selectionStore = useSelectionStore()

const childSources = computed(() => props.source.images.map((img) => img.source))

const isSelected = computed(() => childSources.value.every((src) => selectionStore.selected.includes(src)))

const isPartiallySelected = computed(() => !isSelected.value && childSources.value.some((src) => selectionStore.selected.includes(src)))

/**
 * Handle selection change for the image group
 *
 * @param selected - Whether the group should be selected or deselected
 */
async function onSelectionChange(selected: boolean) {
    const current = selectionStore.selected
    if (selected) {
        // select all children
        selectionStore.set([...new Set([...current, ...childSources.value])])
    } else {
        // unselect all children
        selectionStore.set(current.filter((src) => !childSources.value.includes(src)))
    }
}
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
        &--expanded {
            transform: rotate(180deg);
        }
    }

    .files-list__row-name-text {
        color: var(--color-main-text);
    }
}
</style>
