<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div id="security-webauthn" class="section">
		<h2>{{ t('settings', 'Passwordless Authentication') }}</h2>
		<p class="settings-hint hidden-when-empty">
			{{ t('settings', 'Set up your account for passwordless authentication following the FIDO2 standard.') }}
		</p>
		<NcNoteCard v-if="devices.length === 0" type="info">
			{{ t('settings', 'No devices configured.') }}
		</NcNoteCard>

		<h3 v-else id="security-webauthn__active-devices">
			{{ t('settings', 'The following devices are configured for your account:') }}
		</h3>
		<ul aria-labelledby="security-webauthn__active-devices" class="security-webauthn__device-list">
			<Device v-for="device in sortedDevices"
				:key="device.id"
				:name="device.name"
				@delete="deleteDevice(device.id)" />
		</ul>

		<NcNoteCard v-if="!supportsWebauthn" type="warning">
			{{ t('settings', 'Your browser does not support WebAuthn.') }}
		</NcNoteCard>

		<AddDevice v-if="supportsWebauthn"
			:is-https="isHttps"
			:is-localhost="isLocalhost"
			@added="deviceAdded" />
	</div>
</template>

<script>
import { browserSupportsWebAuthn } from '@simplewebauthn/browser'
import { confirmPassword } from '@nextcloud/password-confirmation'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import sortBy from 'lodash/fp/sortBy.js'

import AddDevice from './AddDevice.vue'
import Device from './Device.vue'
import logger from '../../logger.ts'
import { removeRegistration } from '../../service/WebAuthnRegistrationSerice.js'

import '@nextcloud/password-confirmation/dist/style.css'

const sortByName = sortBy('name')

export default {
	components: {
		AddDevice,
		Device,
		NcNoteCard,
	},
	props: {
		initialDevices: {
			type: Array,
			required: true,
		},
		isHttps: {
			type: Boolean,
			default: false,
		},
		isLocalhost: {
			type: Boolean,
			default: false,
		},
	},

	setup() {
		// Non reactive properties
		return {
			supportsWebauthn: browserSupportsWebAuthn(),
		}
	},

	data() {
		return {
			devices: this.initialDevices,
		}
	},
	computed: {
		sortedDevices() {
			return sortByName(this.devices)
		},
	},
	methods: {
		deviceAdded(device) {
			logger.debug(`adding new device to the list ${device.id}`)

			this.devices.push(device)
		},
		async deleteDevice(id) {
			logger.info(`deleting webauthn device ${id}`)

			await confirmPassword()
			await removeRegistration(id)

			this.devices = this.devices.filter(d => d.id !== id)

			logger.info(`webauthn device ${id} removed successfully`)
		},
	},
}
</script>

<style scoped>
.security-webauthn__device-list {
	margin-block: 12px 18px;
}
</style>
