<template>
	<input type="text" />
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
		}
	}
</script>

<style scoped>

</style>