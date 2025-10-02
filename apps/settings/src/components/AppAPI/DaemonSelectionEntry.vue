<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcListItem
		:name="itemTitle"
		:details="isDefault ? t('settings', 'Default') : ''"
		:force-display-actions="true"
		:counter-number="daemon.exAppsCount"
		:active="isDefault"
		counter-type="highlighted"
		@click.stop="selectDaemonAndInstall">
		<template #subname>
			{{ daemon.accepts_deploy_id }}
		</template>
	</NcListItem>
</template>

<script>
import NcListItem from '@nextcloud/vue/components/NcListItem'
import AppManagement from '../../mixins/AppManagement.js'
import { useAppApiStore } from '../../store/app-api-store.js'
import { useAppsStore } from '../../store/apps-store.js'

export default {
	name: 'DaemonSelectionEntry',
	components: {
		NcListItem,
	},

	mixins: [AppManagement], // TODO: Convert to Composition API when AppManagement is refactored
	props: {
		daemon: {
			type: Object,
			required: true,
		},

		isDefault: {
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

	computed: {
		itemTitle() {
			return this.daemon.name + ' - ' + this.daemon.display_name
		},

		daemons() {
			return this.appApiStore.dockerDaemons
		},
	},

	methods: {
		closeModal() {
			this.$emit('close')
		},

		selectDaemonAndInstall() {
			this.closeModal()
			this.enable(this.app.id, this.daemon, this.deployOptions)
		},
	},
}
</script>
