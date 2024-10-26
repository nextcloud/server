<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<NcSelect :value="currentValue"
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
		<input v-if="!isPredefined"
			type="text"
			:value="currentValue.id"
			:placeholder="t('workflowengine', 'e.g. httpd/unix-directory')"
			@input="updateCustom">
	</div>
</template>

<script>
import NcEllipsisedOption from '@nextcloud/vue/dist/Components/NcEllipsisedOption.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import valueMixin from './../../mixins/valueMixin.js'
import { imagePath } from '@nextcloud/router'

export default {
	name: 'FileMimeType',
	components: {
		NcEllipsisedOption,
		NcSelect,
	},
	mixins: [
		valueMixin,
	],
	data() {
		return {
			predefinedTypes: [
				{
					icon: 'icon-folder',
					label: t('workflowengine', 'Folder'),
					id: 'httpd/unix-directory',
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
					id: 'application/pdf',
				},
			],
		}
	},
	computed: {
		options() {
			return [...this.predefinedTypes, this.customValue]
		},
		isPredefined() {
			const matchingPredefined = this.predefinedTypes.find((type) => this.newValue === type.id)
			if (matchingPredefined) {
				return true
			}
			return false
		},
		customValue() {
			return {
				icon: 'icon-settings-dark',
				label: t('workflowengine', 'Custom MIME type'),
				id: '',
			}
		},
		currentValue() {
			const matchingPredefined = this.predefinedTypes.find((type) => this.newValue === type.id)
			if (matchingPredefined) {
				return matchingPredefined
			}
			return {
				icon: 'icon-settings-dark',
				label: t('workflowengine', 'Custom mimetype'),
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
