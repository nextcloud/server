<!--
  - @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
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
			const span = document.createElement('span') as HTMLSpanElement
			this.$el.replaceWith(span)
			this.$el = span

			const element = await this.render(this.source, this.currentView)
			if (element) {
				this.$el.replaceWith(element)
				this.$el = element
			}
		},
	},
}
</script>
