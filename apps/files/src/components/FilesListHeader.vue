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

import PQueue from 'p-queue'

import logger from '../logger.ts'

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
	setup() {
		// Create a queue to ensure that the header is only rendered once at a time
		const queue = new PQueue({ concurrency: 1 })

		return {
			queue,
		}
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
			// If the header is enabled, we need to render it
			logger.debug(`Enabled ${this.header.id} FilesListHeader`, { header: this.header })
			this.queueUpdate(this.currentFolder, this.currentView)
		},
		currentFolder(folder: Folder) {
			// This method can be used to queue an update of the header
			// It will ensure that the header is only updated once at a time
			this.queueUpdate(folder, this.currentView)
		},
		currentView(view: View) {
			this.queueUpdate(this.currentFolder, view)
		},
	},

	mounted() {
		logger.debug(`Mounted ${this.header.id} FilesListHeader`, { header: this.header })
		const initialRender = () => this.header.render(this.$refs.mount as HTMLElement, this.currentFolder, this.currentView)
		this.queue.add(initialRender)
			.then(() => {
				logger.debug(`Rendered ${this.header.id} FilesListHeader`, { header: this.header })
			}).catch((error) => {
				logger.error(`Error rendering ${this.header.id} FilesListHeader`, { header: this.header, error })
			})
	},
	destroyed() {
		logger.debug(`Destroyed ${this.header.id} FilesListHeader`, { header: this.header })
	},

	methods: {
		queueUpdate(currentFolder: Folder, currentView: View) {
			// This method can be used to queue an update of the header
			// It will ensure that the header is only updated once at a time
			this.queue.add(() => this.header.updated(currentFolder, currentView))
				.then(() => {
					logger.debug(`Updated ${this.header.id} FilesListHeader`, { header: this.header })
				})
				.catch((error) => {
					logger.error(`Error updating ${this.header.id} FilesListHeader`, { header: this.header, error })
				})
		},
	},
}
</script>
