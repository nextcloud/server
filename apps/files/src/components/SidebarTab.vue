<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcAppSidebarTab :id="id"
		ref="tab"
		:name="name"
		:icon="icon"
		@bottomReached="onScrollBottomReached">
		<template #icon>
			<slot name="icon" />
		</template>
		<!-- Fallback loading -->
		<NcEmptyContent v-if="loading" icon="icon-loading" />

		<!-- Using a dummy div as Vue mount replace the element directly
			It does NOT append to the content -->
		<div ref="mount" />
	</NcAppSidebarTab>
</template>

<script>
import NcAppSidebarTab from '@nextcloud/vue/dist/Components/NcAppSidebarTab.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'

export default {
	name: 'SidebarTab',

	components: {
		NcAppSidebarTab,
		NcEmptyContent,
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
			default: '',
		},

		/**
		 * Lifecycle methods.
		 * They are prefixed with `on` to avoid conflict with Vue
		 * methods like this.destroy
		 */
		onMount: {
			type: Function,
			required: true,
		},
		onUpdate: {
			type: Function,
			required: true,
		},
		onDestroy: {
			type: Function,
			required: true,
		},
		onScrollBottomReached: {
			type: Function,
			default: () => {},
		},
	},

	data() {
		return {
			loading: true,
		}
	},

	computed: {
		// TODO: implement a better way to force pass a prop from Sidebar
		activeTab() {
			return this.$parent.activeTab
		},
	},

	watch: {
		async fileInfo(newFile, oldFile) {
			// Update fileInfo on change
			if (newFile.id !== oldFile.id) {
				this.loading = true
				await this.onUpdate(this.fileInfo)
				this.loading = false
			}
		},
	},

	async mounted() {
		this.loading = true
		// Mount the tab:  mounting point,   fileInfo,      vue context
		await this.onMount(this.$refs.mount, this.fileInfo, this.$refs.tab)
		this.loading = false
	},

	async beforeDestroy() {
		// unmount the tab
		await this.onDestroy()
	},
}
</script>
