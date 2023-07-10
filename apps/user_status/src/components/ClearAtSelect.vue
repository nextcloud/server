<!--
  - @copyright Copyright (c) 2020 Georg Ehrke <oc.list@georgehrke.com>
  - @author Georg Ehrke <oc.list@georgehrke.com>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
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
			@option:selected="select" />
	</div>
</template>

<script>
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
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
	margin-bottom: 10px;
	align-items: center;

	&__label {
		margin-right: 12px;
	}

	&__select {
		flex-grow: 1;
	}
}
</style>
