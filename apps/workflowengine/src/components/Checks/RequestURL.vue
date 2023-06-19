<!--
  - @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
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
	<div>
		<NcSelect :value="currentValue"
			:placeholder="t('workflowengine', 'Select a request URL')"
			label="label"
			:clearable="false"
			:options="options"
			@input="setValue">
			<template #option="option">
				<span class="option__icon" :class="option.icon" />
				<span class="option__title">
					<NcEllipsisedOption :name="String(option.label)" />
				</span>
			</template>
			<template #selected-option="selectedOption">
				<span class="option__icon" :class="selectedOption.icon" />
				<span class="option__title">
					<NcEllipsisedOption :name="String(selectedOption.label)" />
				</span>
			</template>
		</NcSelect>
		<input v-if="!isPredefined"
			type="text"
			:value="currentValue.id"
			:placeholder="placeholder"
			@input="updateCustom">
	</div>
</template>

<script>
import NcEllipsisedOption from '@nextcloud/vue/dist/Components/NcEllipsisedOption.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import valueMixin from '../../mixins/valueMixin.js'

export default {
	name: 'RequestURL',
	components: {
		NcEllipsisedOption,
		NcSelect,
	},
	mixins: [
		valueMixin,
	],
	data() {
		return {
			newValue: '',
			predefinedTypes: [
				{
					icon: 'icon-files-dark',
					id: 'webdav',
					label: t('workflowengine', 'Files WebDAV'),
				},
			],
		}
	},
	computed: {
		options() {
			return [...this.predefinedTypes, this.customValue]
		},
		placeholder() {
			if (this.check.operator === 'matches' || this.check.operator === '!matches') {
				return '/^https\\:\\/\\/localhost\\/index\\.php$/i'
			}
			return 'https://localhost/index.php'
		},
		matchingPredefined() {
			return this.predefinedTypes
				.find((type) => this.newValue === type.id)
		},
		isPredefined() {
			return !!this.matchingPredefined
		},
		customValue() {
			return {
				icon: 'icon-settings-dark',
				label: t('workflowengine', 'Custom URL'),
				id: '',
			}
		},
		currentValue() {
			if (this.matchingPredefined) {
				return this.matchingPredefined
			}
			return {
				icon: 'icon-settings-dark',
				label: t('workflowengine', 'Custom URL'),
				id: this.newValue,
			}
		},
	},
	methods: {
		validateRegex(string) {
			const regexRegex = /^\/(.*)\/([gui]{0,3})$/
			const result = regexRegex.exec(string)
			return result !== null
		},
		setValue(value) {
			// TODO: check if value requires a regex and set the check operator according to that
			if (value !== null) {
				this.newValue = value.id
				this.$emit('input', this.newValue)
			}
		},
		updateCustom(event) {
			this.newValue = event.target.value
			this.$emit('input', this.newValue)
		},
	},
}
</script>
<style scoped lang="scss">
	.v-select,
	input[type='text'] {
		width: 100%;
	}
	input[type='text'] {
		min-height: 48px;
	}

	.option__icon {
		display: inline-block;
		min-width: 30px;
		background-position: center;
		vertical-align: middle;
	}

	.option__title {
		display: inline-flex;
		width: calc(100% - 36px);
		vertical-align: middle;
	}
</style>
