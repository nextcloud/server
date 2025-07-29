<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div />
</template>
<script>
export default {
	name: 'LegacyView',
	props: {
		component: {
			type: Object,
			required: true,
		},
		fileInfo: {
			type: Object,
			default: () => {},
			required: true,
		},
	},
	watch: {
		fileInfo(fileInfo) {
			// update the backbone model FileInfo
			this.setFileInfo(fileInfo)
		},
	},
	mounted() {
		// append the backbone element and set the FileInfo
		this.component.$el.replaceAll(this.$el)
		this.setFileInfo(this.fileInfo)
	},
	methods: {
		setFileInfo(fileInfo) {
			this.component.setFileInfo(new OCA.Files.FileInfoModel(fileInfo))
		},
	},
}
</script>
