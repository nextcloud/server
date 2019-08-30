<template>
	<input type="text" v-model="test">
</template>

<script>
import { Multiselect } from 'nextcloud-vue'

export default {
	name: 'SizeValue',
	components: {
		Multiselect
	},
	data() {
		return {
			test: 'test',
			predefinedTypes: [
				{
					icon: 'icon-picture',
					label: 'Images',
					pattern: '/image\\/.*/'
				},
				{
					icon: 'icon-category-office',
					label: 'Office documents',
					pattern: '/(vnd\\.(ms-|openxmlformats-).*))$/'
				},
				{
					icon: 'icon-filetype-file',
					label: 'PDF documents',
					pattern: 'application/pdf'
				}
			]
		}
	},
	methods: {
		validateRegex(string) {
			var regexRegex = /^\/(.*)\/([gui]{0,3})$/
			var result = regexRegex.exec(string)
			return result !== null
		}
	}
}
</script>

<style scoped>

</style>
