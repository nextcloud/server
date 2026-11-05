<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'

defineProps<{
	/** Name the user gave the device, if any */
	name: string | null
}>()

defineEmits<{
	/** The user asked to delete the device */
	delete: []
}>()
</script>

<template>
	<li class="webauthn-device">
		<span class="icon-webauthn-device" />
		{{ name || t('settings', 'Unnamed device') }}
		<NcActions :forceMenu="true">
			<NcActionButton icon="icon-delete" @click="$emit('delete')">
				{{ t('settings', 'Delete') }}
			</NcActionButton>
		</NcActions>
	</li>
</template>

<style scoped>
	.webauthn-device {
		line-height: 300%;
		display: flex;
	}

	.icon-webauthn-device {
		display: inline-block;
		background-size: 100%;
		padding: 3px;
		margin: 3px;
	}
</style>
