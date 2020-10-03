
<!--
  - @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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
	<AppSidebarTab
		:id="id"
		:name="name"
		:icon="icon">
		<!-- Using a dummy div as Vue mount replace the element directly
			It does NOT append to the content -->
		<div ref="mount"></div>
	</AppSidebarTab>
</template>

<script>
import AppSidebarTab from '@nextcloud/vue/dist/Components/AppSidebarTab'
export default {
	name: 'SidebarTab',

	components: {
		AppSidebarTab,
	},

	props: {
		fileInfo: {
			type: Object,
			default: () => {},
			required: true,
		},
		id: {
			type: String,
			required: true,
		},
		name: {
			type: String,
			required: true,
		},
		icon: {
			type: String,
			required: true,
		},
		render: {
			type: Function,
			required: true,
		},
	},

	computed: {
		// TODO: implement a better way to force pass a prop fromm Sidebar
		activeTab() {
			return this.$parent.activeTab
		},
	},

	watch: {
		fileInfo(newFile, oldFile) {
			if (newFile.id !== oldFile.id) {
				this.mountTab()
			}
		},
	},

	mounted() {
		this.mountTab()
	},

	methods: {
		mountTab() {
			// Mount the tab into this component
			this.render(this.$refs.mount, this.fileInfo)
		},
	},
}
</script>
