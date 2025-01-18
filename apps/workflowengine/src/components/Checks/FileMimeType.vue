<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<NcSelect
			:model-value="currentValue"
			:placeholder="t('workflowengine', 'Select a file type')"
			label="label"
			:options="options"
			:clearable="false"
			@input="setValue">
			<template #option="option">
				<span v-if="option.icon" class="option__icon" :class="option.icon" />
				<span v-else class="option__icon-img">
					<img :src="option.iconUrl" alt="">
				</span>
				<span class="option__title">
					<NcEllipsisedOption :name="String(option.label)" />
				</span>
			</template>
			<template #selected-option="selectedOption">
				<span v-if="selectedOption.icon" class="option__icon" :class="selectedOption.icon" />
				<span v-else class="option__icon-img">
					<img :src="selectedOption.iconUrl" alt="">
				</span>
				<span class="option__title">
					<NcEllipsisedOption :name="String(selectedOption.label)" />
				</span>
			</template>
		</NcSelect>
		<input
			v-if="!isPredefined"
			:value="currentValue.id"
			type="text"
			:placeholder="t('workflowengine', 'e.g. /^application\\/xml$/')"
			@input="updateCustom">
	</div>
</template>

<script>
import { imagePath } from '@nextcloud/router'
import NcEllipsisedOption from '@nextcloud/vue/components/NcEllipsisedOption'
import NcSelect from '@nextcloud/vue/components/NcSelect'

export default {
	name: 'FileMimeType',
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

	data() {
		return {
			literalOperators: ['is', '!is'],
			regexOperators: ['matches', '!matches'],
			predefinedLiteralTypes: [
				{
					icon: 'icon-folder',
					label: t('workflowengine', 'Folder'),
					id: 'httpd/unix-directory',
				},
				{
					iconUrl: imagePath('core', 'filetypes/application-pdf'),
					label: t('workflowengine', 'PDF documents'),
					id: 'application/pdf',
				},
			],

			predefinedRegexTypes: [
				{
					iconUrl: imagePath('core', 'filetypes/audio'),
					label: t('workflowengine', 'Audio'),
					id: '/audio\\/.*/',
				},
				{
					icon: 'icon-folder',
					label: t('workflowengine', 'Folder'),
					id: '/^httpd\\/unix-directory$/',
				},
				{
					icon: 'icon-picture',
					label: t('workflowengine', 'Images'),
					id: '/image\\/.*/',
				},
				{
					iconUrl: imagePath('core', 'filetypes/x-office-document'),
					label: t('workflowengine', 'Office documents'),
					id: '/(vnd\\.(ms-|openxmlformats-|oasis\\.opendocument).*)$/',
				},
				{
					iconUrl: imagePath('core', 'filetypes/application-pdf'),
					label: t('workflowengine', 'PDF documents'),
					id: '/^application\\/pdf$/',
				},
				{
					iconUrl: imagePath('core', 'filetypes/video'),
					label: t('workflowengine', 'Video'),
					id: '/video\\/.*/',
				},
			],

			newValue: '',
		}
	},

	computed: {
		availablePredefinedTypes() {
			return this.literalOperators.includes(this.operator) ? this.predefinedLiteralTypes : this.predefinedRegexTypes
		},

		options() {
			const customTypes = this.regexOperators.includes(this.operator) ? [this.customValue] : []
			return [...this.availablePredefinedTypes, ...customTypes]
		},

		isPredefined() {
			return this.availablePredefinedTypes.some((type) => this.newValue === type.id)
		},

		customValue() {
			return {
				icon: 'icon-settings-dark',
				label: t('workflowengine', 'Custom MIME type'),
				id: '',
			}
		},

		currentValue() {
			const matchingPredefined = this.availablePredefinedTypes.find((type) => this.newValue === type.id)
			if (matchingPredefined) {
				return matchingPredefined
			}
			return {
				icon: 'icon-settings-dark',
				label: t('workflowengine', 'Custom MIME type'),
				id: this.newValue,
			}
		},
	},

	watch: {
		modelValue() {
			this.updateInternalValue()
		},

		// If user changed operation from is/!is to matches/!matches (or vice versa), reset value
		operator(newVal, oldVal) {
			const switchedGroups = (this.literalOperators.includes(oldVal) && this.regexOperators.includes(newVal))
				|| (this.regexOperators.includes(oldVal) && this.literalOperators.includes(newVal))
			if (switchedGroups) {
				this.setValue(this.options[0])
			}
		},
	},

	methods: {
		validateRegex(string) {
			const regexRegex = /^\/(.*)\/([gui]{0,3})$/
			const result = regexRegex.exec(string)
			return result !== null
		},

		updateInternalValue() {
			this.newValue = this.modelValue
		},

		setValue(value) {
			if (value !== null) {
				this.newValue = value.id
				this.$emit('update:model-value', this.newValue)
			}
		},

		updateCustom(event) {
			this.newValue = event.target.value || event.detail[0]
			this.$emit('update:model-value', this.newValue)
		},
	},
}
</script>

<style scoped lang="scss">
.v-select,
input[type='text'] {
	width: 100%;
}

input[type=text] {
	min-height: 48px;
}

.option__icon,
.option__icon-img {
	display: inline-block;
	min-width: 30px;
	background-position: center;
	vertical-align: middle;
}

.option__icon-img {
	text-align: center;
}

.option__title {
	display: inline-flex;
	width: calc(100% - 36px);
	vertical-align: middle;
}
</style>
