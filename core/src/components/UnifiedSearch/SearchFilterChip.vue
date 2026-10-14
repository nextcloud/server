<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { mdiClose } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

const props = defineProps<{
	text: string
	pretext: string
}>()

// The parent reads the filter from its own v-for scope, so no payload is needed.
defineEmits<{
	delete: []
}>()

// Accessible name for the icon-only remove button (screen readers can't read a bare ×).
const removeLabel = computed(() => t('core', 'Remove filter: {name}', { name: props.text }))
</script>

<template>
	<div class="chip">
		<span class="icon">
			<slot name="icon" />
			<span v-if="pretext.length"> {{ pretext }} : </span>
		</span>
		<span class="text">{{ text }}</span>
		<button
			type="button"
			class="close-button"
			:aria-label="removeLabel"
			@click="$emit('delete')">
			<NcIconSvgWrapper :path="mdiClose" :size="18" />
		</button>
	</div>
</template>

<style lang="scss" scoped>
.chip {
    display: flex;
    align-items: center;
    padding: 2px 4px;
    border: 1px solid var(--color-primary-element-light);
    border-radius: 20px;
    background-color: var(--color-primary-element-light);
    margin: 2px;

    .icon {
        display: flex;
        align-items: center;
        padding-inline-end: 5px;

        img {
            width: 20px;
            padding: 2px;
            border-radius: 20px;
            filter: var(--background-invert-if-bright);
        }
    }

    .text {
        margin: 0 2px;
    }

    .close-button {
        display: flex;
        align-items: center;
        width: auto;
        min-width: 0;
        min-height: 0;
        margin: 0;
        padding: 0;
        border: none;
        background: transparent;
        color: inherit;
        cursor: pointer;
        border-radius: var(--border-radius-element, 8px);

        &:hover {
            filter: invert(20%);
        }

        &:focus-visible {
            outline: 2px solid var(--color-main-text);
            outline-offset: 1px;
        }
    }
}
</style>
