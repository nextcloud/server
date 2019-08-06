<template>
	<multiselect :options="options" track-by="id" label="text" v-model="value"></multiselect>
</template>

<script>
	const pdfConvertOptions = [
		{
			id: 'keep;preserve',
			text: t('workflow_pdf_converter', 'Keep original, preserve existing PDFs'),
		},
		{
			id: 'keep;overwrite',
			text: t('workflow_pdf_converter', 'Keep original, overwrite existing PDF'),
		},
		{
			id: 'delete;preserve',
			text: t('workflow_pdf_converter', 'Delete original, preserve existing PDFs'),
		},
		{
			id: 'delete;overwrite',
			text: t('workflow_pdf_converter', 'Delete original, overwrite existing PDF'),
		},
	]

	import { Multiselect } from 'nextcloud-vue'
	export default {
		name: "ConvertToPdf",
		components: {Multiselect},
		data() {
			return {
				options: pdfConvertOptions,
				value: pdfConvertOptions[0]
			}
		}
	}
</script>

<style scoped>
	.multiselect {
		width: 100%;
		max-width: 300px;
		margin: auto;
		text-align: center;
	}
</style>