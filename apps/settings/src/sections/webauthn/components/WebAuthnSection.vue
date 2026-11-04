<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
/* eslint vue/multi-word-component-names: "warn" */

import type { WebAuthnDevice as Device } from '../../../service/WebAuthnRegistrationSerice.ts'

import { t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { browserSupportsWebAuthn } from '@simplewebauthn/browser'
import { computed, ref } from 'vue'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import WebAuthnAddDevice from './WebAuthnAddDevice.vue'
import WebAuthnDevice from './WebAuthnDevice.vue'
import logger from '../../../logger.ts'
import { removeRegistration } from '../../../service/WebAuthnRegistrationSerice.ts'
import { naturalCollator } from '../../../utils/sorting.ts'

const props = defineProps<{
	/** Devices registered when the page loaded */
	initialDevices: Device[]
	/** Whether the page is served over HTTPS */
	isHttps?: boolean
	/** Whether the page is served from localhost */
	isLocalhost?: boolean
}>()

// Non reactive properties
const supportsWebauthn = browserSupportsWebAuthn()

const devices = ref(props.initialDevices)

const sortedDevices = computed(() => [...devices.value].sort((a, b) => naturalCollator.compare(a.name ?? '', b.name ?? '')))

/**
 * @param device The device the server just registered
 */
function deviceAdded(device: Device) {
	logger.debug(`adding new device to the list ${device.id}`)

	devices.value.push(device)
}

/**
 * @param id The device to delete
 */
async function deleteDevice(id: number) {
	logger.info(`deleting webauthn device ${id}`)

	await confirmPassword()
	await removeRegistration(id)

	devices.value = devices.value.filter((d) => d.id !== id)

	logger.info(`webauthn device ${id} removed successfully`)
}
</script>

<template>
	<div class="section">
		<h2>{{ t('settings', 'Passwordless Authentication') }}</h2>
		<p class="settings-hint hidden-when-empty">
			{{ t('settings', 'Set up your account for passwordless authentication following the FIDO2 standard.') }}
		</p>

		<NcNoteCard v-if="devices.length === 0" type="info">
			{{ t('settings', 'No devices configured.') }}
		</NcNoteCard>
		<template v-else>
			<h3 id="security-webauthn__active-devices">
				{{ t('settings', 'The following devices are configured for your account:') }}
			</h3>
			<ul aria-labelledby="security-webauthn__active-devices" class="security-webauthn__device-list">
				<WebAuthnDevice
					v-for="device in sortedDevices"
					:key="device.id"
					:name="device.name"
					@delete="deleteDevice(device.id)" />
			</ul>
		</template>

		<NcNoteCard v-if="!supportsWebauthn" type="warning">
			{{ t('settings', 'Your browser does not support WebAuthn.') }}
		</NcNoteCard>

		<WebAuthnAddDevice
			v-if="supportsWebauthn"
			:isHttps="isHttps"
			:isLocalhost="isLocalhost"
			@added="deviceAdded" />
	</div>
</template>

<style scoped>
.security-webauthn__device-list {
	margin-block: 12px 18px;
}
</style>
