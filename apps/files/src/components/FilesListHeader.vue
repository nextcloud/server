<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div v-show="enabled" :class="`files-list__header-${header.id}`">
		<span ref="mount" />
	</div>
</template>

<script lang="ts">
/**
 * This component is used to render custom
 * elements provided by an API. Vue doesn't allow
 * to directly render an HTMLElement, so we can do
 * this magic here.
 */
export default {
	name: 'FilesListHeader',
	props: {
		header: {
			type: Object,
			required: true,
		},
		currentFolder: {
			type: Object,
			required: true,
		},
		currentView: {
			type: Object,
			required: true,
		},
	},
	computed: {
		enabled() {
			return this.header.enabled(this.currentFolder, this.currentView)
		},
	},
	watch: {
		enabled(enabled) {
			if (!enabled) {
				return
			}
			this.header.updated(this.currentFolder, this.currentView)
		},
		currentFolder() {
			this.header.updated(this.currentFolder, this.currentView)
		},
	},
	mounted() {
		console.debug('Mounted', this.header.id)
		this.header.render(this.$refs.mount, this.currentFolder, this.currentView)
	},
}
</script>
