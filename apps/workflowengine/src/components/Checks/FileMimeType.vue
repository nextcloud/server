<template>
	<div>
		<Multiselect
			:value="currentValue"
			:placeholder="t('workflowengine', 'Select a file type')"
			label="label"
			track-by="pattern"
			:options="options"
			:multiple="false"
			:tagging="false"
			@input="setValue">
			<template slot="singleLabel" slot-scope="props">
				<span class="option__icon" :class="props.option.icon" />
				<span class="option__title option__title_single">{{ props.option.label }}</span>
			</template>
			<template slot="option" slot-scope="props">
				<span class="option__icon" :class="props.option.icon" />
				<span class="option__title">{{ props.option.label }}</span>
			</template>
		</Multiselect>
		<input v-if="!isPredefined"
			type="text"
			:value="currentValue.pattern"
			@input="updateCustom">
	</div>
</template>

<script>
import { Multiselect } from 'nextcloud-vue/dist/Components/Multiselect'
import valueMixin from './../../mixins/valueMixin'

export default {
	name: 'FileMimeType',
	components: {
		Multiselect
	},
	mixins: [
		valueMixin
	],
	data() {
		return {
			predefinedTypes: [
				{
					icon: 'icon-picture',
					label: t('workflowengine', 'Images'),
					pattern: '/image\\/.*/'
				},
				{
					icon: 'icon-category-office',
					label: t('workflowengine', 'Office documents'),
					pattern: '/(vnd\\.(ms-|openxmlformats-).*))$/'
				},
				{
					icon: 'icon-filetype-file',
					label: t('workflowengine', 'PDF documents'),
					pattern: 'application/pdf'
				}
			]
		}
	},
	computed: {
		options() {
			return [...this.predefinedTypes, this.customValue]
		},
		isPredefined() {
			const matchingPredefined = this.predefinedTypes.find((type) => this.newValue === type.pattern)
			if (matchingPredefined) {
				return true
			}
			return false
		},
		customValue() {
			return {
				icon: 'icon-settings-dark',
				label: t('workflowengine', 'Custom mimetype'),
				pattern: ''
			}
		},
		currentValue() {
			const matchingPredefined = this.predefinedTypes.find((type) => this.newValue === type.pattern)
			if (matchingPredefined) {
				return matchingPredefined
			}
			return {
				icon: 'icon-settings-dark',
				label: t('workflowengine', 'Custom mimetype'),
				pattern: this.newValue
			}
		}
	},
	methods: {
		validateRegex(string) {
			var regexRegex = /^\/(.*)\/([gui]{0,3})$/
			var result = regexRegex.exec(string)
			return result !== null
		},
		setValue(value) {
			// TODO: check if value requires a regex and set the check operator according to that
			if (value !== null) {
				this.newValue = value.pattern
				this.$emit('input', this.newValue)
			}
		},
		updateCustom(event) {
			this.newValue = event.target.value
			this.$emit('input', this.newValue)
		}
	}
}
</script>

<style scoped src="./../../css/multiselect.css"></style>
