<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog :open="show"
		:name="t('settings', 'Choose Deploy Daemon for {appName}', {appName: app.name })"
		size="normal"
		@update:open="closeModal">
		<DaemonSelectionList :app="app"
			:deploy-options="deployOptions"
			@close="closeModal" />
	</NcDialog>
</template>

<script>
import NcDialog from '@nextcloud/vue/components/NcDialog'
import DaemonSelectionList from './DaemonSelectionList.vue'
import { useAppsStore } from '../../store/apps-store'
import { useAppApiStore } from '../../store/app-api-store'

export default {
	name: 'DaemonSelectionDialog',
	components: {
		NcDialog,
		DaemonSelectionList,
	},
	props: {
		show: {
			type: Boolean,
			required: true,
		},
		app: {
			type: Object,
			required: true,
		},
		deployOptions: {
			type: Object,
			required: false,
			default: () => ({}),
		},
	},
	setup() {
		const store = useAppsStore()
		const appApiStore = useAppApiStore()

		return {
			store,
			appApiStore,
		}
	},
	data() {
		return {
			selectDaemonModal: false,
		}
	},
	methods: {
		closeModal() {
			this.$emit('update:show', false)
		},
	},
}
</script>
