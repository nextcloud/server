<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<span />
</template>

<script lang="ts">
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
			type: Object,
			required: true,
		},
		currentView: {
			type: Object,
			required: true,
		},
		render: {
			type: Function,
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
			const element = await this.render(this.source, this.currentView)
			if (element) {
				this.$el.replaceChildren(element)
			} else {
				this.$el.replaceChildren()
			}
		},
	},
}
</script>
