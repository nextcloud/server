<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="clear-at-select">
		<label class="clear-at-select__label" for="clearStatus">
			{{ t('user_status', 'Clear status after') }}
		</label>
		<NcSelect
			input-id="clearStatus"
			class="clear-at-select__select"
			:options="options"
			:model-value="option"
			:clearable="false"
			placement="top"
			label-outside
			@option:selected="select" />
	</div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { getAllClearAtOptions } from '../services/clearAtOptionsService.js'
import { clearAtFormat } from '../services/clearAtService.js'

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

	emits: ['selectClearAt'],

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
				label: clearAtFormat(this.clearAt),
			}
		},
	},

	methods: {
		t,

		/**
		 * Triggered when the user selects a new option.
		 *
		 * @param {object=} option The new selected option
		 */
		select(option) {
			if (!option) {
				return
			}

			this.$emit('selectClearAt', option.clearAt)
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
