<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<NcSelect
			v-model="newValue"
			:aria-label-combobox="t('workflowengine', 'Request URL')"
			:model-value="currentValue"
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
		<input
			v-if="!isPredefined"
			type="text"
			:value="currentValue.id"
			:placeholder="placeholder"
			@input="updateCustom">
	</div>
</template>

<script>
import NcEllipsisedOption from '@nextcloud/vue/components/NcEllipsisedOption'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { useCheckValue } from '../../composables/useCheckValue.ts'

export default {
	name: 'RequestURL',
	components: {
		NcEllipsisedOption,
		NcSelect,
	},

	props: {
		modelValue: {
			type: String,
			default: '',
		},

		operator: {
			type: String,
			default: '',
		},
	},

	emits: ['update:model-value'],

	setup(props, { emit }) {
		return useCheckValue(() => props.modelValue, (value) => emit('update:model-value', value))
	},

	data() {
		return {
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
			if (this.operator === 'matches' || this.operator === '!matches') {
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
		setValue(value) {
			// TODO: check if value requires a regex and set the check operator according to that
			if (value !== null) {
				this.emitValue(value.id)
			}
		},

		updateCustom(event) {
			this.emitValue(event.target.value)
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
