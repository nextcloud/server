<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcChip :text="text"
		@close="deleteChip">
		<slot name="icon" />
	</NcChip>
</template>

<script>
export default {
	name: 'SearchFilterChip',

	props: {
		text: {
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
