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
		<span class="close-icon" @click="deleteChip">
			<CloseIcon :size="18" />
		</span>
	</div>
</template>

<script>
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
	methods: {
		deleteChip() {
			this.$emit('delete', this.filter)
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

    .close-icon {
        cursor: pointer ;

        :hover {
            filter: invert(20%);
        }
    }
}
</style>
