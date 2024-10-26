<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog content-classes="app-changelog-dialog"
		:buttons="dialogButtons"
		:name="t('updatenotification', 'What\'s new in {app} {version}', { app: appName, version: appVersion })"
		:open="open && markdown !== undefined"
		size="normal"
		@update:open="$emit('update:open', $event)">
		<Markdown class="app-changelog-dialog__text" :markdown="markdown" :min-heading-level="3" />
	</NcDialog>
</template>

<script setup lang="ts">
import { translate as t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { ref, watchEffect } from 'vue'

import axios from '@nextcloud/axios'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import Markdown from './Markdown.vue'

const props = withDefaults(
	defineProps<{
		appId: string
		version?: string
		open?: boolean
	}>(),

	// Default values
	{
		open: true,
		version: undefined,
	},
)

const emit = defineEmits<{
	/**
	 * Event that is called when the "Get started"-button is pressed
	 */
	(e: 'dismiss'): void

	(e: 'update:open', v: boolean): void
}>()

const dialogButtons = [
	{
		label: t('updatenotification', 'Give feedback'),
		callback: () => {
			window.open(`https://apps.nextcloud.com/apps/${props.appId}#comments`, '_blank', 'noreferrer noopener')
		},
	},
	{
		label: t('updatenotification', 'Get started'),
		type: 'primary',
		callback: () => {
			emit('dismiss')
			emit('update:open', false)
		},
	},
]

const appName = ref(props.appId)
const appVersion = ref(props.version ?? '')
const markdown = ref<string>('')
watchEffect(() => {
	const url = props.version
		? generateOcsUrl('/apps/updatenotification/api/v1/changelog/{app}?version={version}', { version: props.version, app: props.appId })
		: generateOcsUrl('/apps/updatenotification/api/v1/changelog/{app}', { version: props.version, app: props.appId })

	axios.get(url)
		.then(({ data }) => {
			appName.value = data.ocs.data.appName
			appVersion.value = data.ocs.data.version
			markdown.value = data.ocs.data.content
		})
		.catch((error) => {
			if (error?.response?.status === 404) {
				appName.value = props.appId
				markdown.value = t('updatenotification', 'No changelog available')
			} else {
				console.error('Failed to load changelog entry', error)
				emit('update:open', false)
			}
		})

})
</script>

<style scoped lang="scss">
:deep(.app-changelog-dialog) {
	min-height: 50vh !important;
}

.app-changelog-dialog__text {
	padding-inline: 14px;
}
</style>
