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
import type { Folder, Header, View } from '@nextcloud/files'
import type { PropType } from 'vue'

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
			type: Object as PropType<Header>,
			required: true,
		},
		currentFolder: {
			type: Object as PropType<Folder>,
			required: true,
		},
		currentView: {
			type: Object as PropType<View>,
			required: true,
		},
	},
	computed: {
		enabled() {
			return this.header.enabled?.(this.currentFolder, this.currentView) ?? true
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
		this.header.render(this.$refs.mount as HTMLElement, this.currentFolder, this.currentView)
	},
}
</script>
