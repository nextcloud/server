<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<NcSelect v-model="currentValue"
			:placeholder="t('workflowengine', 'Select a user agent')"
			label="label"
			:options="options"
			:clearable="false"
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
			v-model="newValue"
			type="text"
			@input="updateCustom">
	</div>
</template>

<script>
import NcEllipsisedOption from '@nextcloud/vue/components/NcEllipsisedOption'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import valueMixin from '../../mixins/valueMixin.js'

export default {
	name: 'RequestUserAgent',
	components: {
		NcEllipsisedOption,
		NcSelect,
	},
	mixins: [
		valueMixin,
	],
	props: {
		modelValue: {
			type: String,
			default: '',
		},
	},
	emits: ['update:model-value'],
	data() {
		return {
			newValue: '',
			predefinedTypes: [
				{ id: 'android', label: t('workflowengine', 'Android client'), icon: 'icon-phone' },
				{ id: 'ios', label: t('workflowengine', 'iOS client'), icon: 'icon-phone' },
				{ id: 'desktop', label: t('workflowengine', 'Desktop client'), icon: 'icon-desktop' },
				{ id: 'mail', label: t('workflowengine', 'Thunderbird & Outlook addons'), icon: 'icon-mail' },
			],
		}
	},
	computed: {
		options() {
			return [...this.predefinedTypes, this.customValue]
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
				label: t('workflowengine', 'Custom user agent'),
				id: '',
			}
		},
		currentValue: {
			get() {
				if (this.matchingPredefined) {
					return this.matchingPredefined
				}
				return {
					icon: 'icon-settings-dark',
					label: t('workflowengine', 'Custom user agent'),
					id: this.newValue,
				}
			},
			set(value) {
				this.newValue = value
			},
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
				this.$emit('update:model-value', this.newValue)
			}
		},
		updateCustom() {
			this.newValue = this.currentValue.id
			this.$emit('update:model-value', this.newValue)
		},
	},
}
</script>
<style scoped>
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
