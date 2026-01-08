<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog
		:open="show"
		:name="t('settings', 'Choose Deploy Daemon for {appName}', { appName: app.name })"
		size="normal"
		@update:open="closeModal">
		<DaemonSelectionList
			:app="app"
			:deploy-options="deployOptions"
			@close="closeModal" />
	</NcDialog>
</template>

<script setup>
import { defineEmits, defineProps } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import DaemonSelectionList from './DaemonSelectionList.vue'

defineProps({
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
})

const emit = defineEmits(['update:show'])
/**
 *
 */
function closeModal() {
	emit('update:show', false)
}
</script>
