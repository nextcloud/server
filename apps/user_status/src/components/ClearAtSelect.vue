<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="clear-at-select">
		<label class="clear-at-select__label" for="clearStatus">
			{{ $t('user_status', 'Clear status after') }}
		</label>
		<NcSelect input-id="clearStatus"
			class="clear-at-select__select"
			:options="options"
			:value="option"
			:clearable="false"
			placement="top"
			label-outside
			@option:selected="select" />
	</div>
</template>

<script>
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { getAllClearAtOptions } from '../services/clearAtOptionsService.js'
import { clearAtFilter } from '../filters/clearAtFilter.js'

export default {
	name: 'ClearAtSelect',
	components: {
		NcSelect,
	},
	props: {
		clearAt: {
			type: Object,
			default: null,
		},
	},
	data() {
		return {
			options: getAllClearAtOptions(),
		}
	},
	computed: {
		/**
		 * Returns an object of the currently selected option
		 *
		 * @return {object}
		 */
		option() {
			return {
				clearAt: this.clearAt,
				label: clearAtFilter(this.clearAt),
			}
		},
	},
	methods: {
		/**
		 * Triggered when the user selects a new option.
		 *
		 * @param {object=} option The new selected option
		 */
		select(option) {
			if (!option) {
				return
			}

			this.$emit('select-clear-at', option.clearAt)
		},
	},
}
</script>

<style lang="scss" scoped>
.clear-at-select {
	display: flex;
	gap: calc(2 * var(--default-grid-baseline));
	align-items: center;
	margin-block: 0 calc(2 * var(--default-grid-baseline));

	&__select {
		flex-grow: 1;
		min-width: 215px;
	}
}
</style>
