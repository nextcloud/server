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
