<template>
	<div>

		<multiselect
			:value="currentValue"
			placeholder="Select a file type"
			label="label"
			track-by="pattern"
			:options="options" :multiple="false" :tagging="false" @input="setValue">
			<template slot="singleLabel" slot-scope="props">
				<span class="option__icon" :class="props.option.icon"></span>
				<span class="option__title option__title_single">{{ props.option.label }}</span>
			</template>
			<template slot="option" slot-scope="props">
				<span class="option__icon" :class="props.option.icon"></span>
				<span class="option__title">{{ props.option.label }}</span>
			</template>
		</multiselect>
		<input type="text" :value="currentValue.pattern" @input="updateCustom"/>
	</div>
</template>

<script>
import { Multiselect } from 'nextcloud-vue'

export default {
	name: 'FileMimeType',
	components: {
		Multiselect
	},
	data() {
		return {
			value: '',
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
		customValue() {
			const matchingPredefined = this.predefinedTypes.find((type) => this.value.pattern === type.pattern)
			return {
				icon: 'icon-settings-dark',
				label: t('workflowengine', 'Custom pattern'),
				pattern: '',
			}
		},
		currentValue() {
			const matchingPredefined = this.predefinedTypes.find((type) => this.value === type.pattern)
			if (matchingPredefined) {
				return matchingPredefined
			}
			return {
				icon: 'icon-settings-dark',
				label: t('workflowengine', 'Custom pattern'),
				pattern: this.value,
			}
		}
	},
	methods: {
		validateRegex(string) {
			var regexRegex = /^\/(.*)\/([gui]{0,3})$/
			var result = regexRegex.exec(string)
			return result !== null
		},
		setValue (value) {
			// TODO: check if value requires a regex and set the check operator according to that
			if (value !== null) {
				this.value = value.pattern
			}
		},
		updateCustom (event) {
			console.log(event)
			this.value = event.target.value
		}
	}
}
</script>

<style scoped>
	.multiselect::v-deep .multiselect__single {
		display: flex;
	}
	input, .multiselect {
		width: 100%;
	}
</style>
