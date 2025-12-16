<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<span />
</template>

<script lang="ts">
import type { FileAction, Folder, Node, View } from '@nextcloud/files'
import type { PropType } from 'vue'

type RenderFunction = typeof FileAction.prototype.renderInline

/**
 * This component is used to render custom
 * elements provided by an API. Vue doesn't allow
 * to directly render an HTMLElement, so we can do
 * this magic here.
 */
export default {
	name: 'CustomElementRender',
	props: {
		source: {
			type: Object as PropType<Node>,
			required: true,
		},

		activeView: {
			type: Object as PropType<View>,
			required: true,
		},

		activeFolder: {
			type: Object as PropType<Folder>,
			required: true,
		},

		render: {
			type: Function as PropType<RenderFunction>,
			required: true,
		},
	},

	watch: {
		source() {
			this.updateRootElement()
		},

		currentView() {
			this.updateRootElement()
		},
	},

	mounted() {
		this.updateRootElement()
	},

	methods: {
		async updateRootElement() {
			const element = await this.render!({
				nodes: [this.source],
				view: this.activeView,
				folder: this.activeFolder,
				contents: [],
			})

			if (element) {
				this.$el.replaceChildren(element)
			} else {
				this.$el.replaceChildren()
			}
		},
	},
}
</script>
