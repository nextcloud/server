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
