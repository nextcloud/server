<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
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
			@click="deleteChip">
			<CloseIcon :size="18" />
		</button>
	</div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import CloseIcon from 'vue-material-design-icons/Close.vue'

export default {
	name: 'SearchFilterChip',
	components: {
		CloseIcon,
	},

	props: {
		text: {
			type: String,
			required: true,
		},

		pretext: {
			type: String,
			required: true,
		},
	},

	emits: ['delete'],

	computed: {
		// Accessible name for the icon-only remove button (screen readers can't read a bare ×).
		removeLabel() {
			return t('core', 'Remove filter: {name}', { name: this.text })
		},
	},

	methods: {
		deleteChip() {
			// The parent reads the filter from its own v-for scope, so no payload is needed.
			this.$emit('delete')
		},
	},
}
</script>

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
