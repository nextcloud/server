<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiUsb } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

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
		<NcIconSvgWrapper :path="mdiUsb" />
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
		align-items: center;
	}
</style>
