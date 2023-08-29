<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<div v-if="!adding" id="generate-app-token-section" class="row spacing">
		<!-- Port to TextField component when available -->
		<NcTextField v-model="deviceName"
			type="text"
			:maxlength="120"
			:disabled="loading"
			class="app-name-text-field"
			:label="t('settings', 'App name')"
			:placeholder="t('settings', 'App name')"
			@keydown.enter="submit" />
		<NcButton :disabled="loading || deviceName.length === 0"
			type="primary"
			@click="submit">
			{{ t('settings', 'Create new app password') }}
		</NcButton>
	</div>
	<div v-else class="spacing">
		{{ t('settings', 'Use the credentials below to configure your app or device.') }}
		{{ t('settings', 'For security reasons this password will only be shown once.') }}
		<div class="app-password-row">
			<label for="app-username" class="app-password-label">{{ t('settings', 'Username') }}</label>
			<input id="app-username"
				:value="loginName"
				type="text"
				class="monospaced"
				readonly="readonly"
				@focus="selectInput">
		</div>
		<div class="app-password-row">
			<label for="app-password" class="app-password-label">{{ t('settings', 'Password') }}</label>
			<input id="app-password"
				ref="appPassword"
				:value="appPassword"
				type="text"
				class="monospaced"
				readonly="readonly"
				@focus="selectInput">
			<NcButton type="tertiary"
				:title="copyTooltipOptions"
				:aria-label="copyTooltipOptions"
				@click="copyPassword">
				<template #icon>
					<Check v-if="copied" :size="20" />
					<ContentCopy v-else :size="20" />
				</template>
			</NcButton>
			<NcButton @click="reset">
				{{ t('settings', 'Done') }}
			</NcButton>
		</div>
		<div class="app-password-row">
			<span class="app-password-label" />
			<NcButton v-if="!showQR"
				@click="showQR = true">
				{{ t('settings', 'Show QR code for mobile apps') }}
			</NcButton>
			<QR v-else
				:value="qrUrl" />
		</div>
	</div>
</template>

<script>
import QR from '@chenfengyuan/vue-qrcode'
import { confirmPassword } from '@nextcloud/password-confirmation'
import '@nextcloud/password-confirmation/dist/style.css'
import { showError } from '@nextcloud/dialogs'
import { getRootUrl } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import Check from 'vue-material-design-icons/Check.vue'
import ContentCopy from 'vue-material-design-icons/ContentCopy.vue'

export default {
	name: 'AuthTokenSetupDialogue',
	components: {
		Check,
		ContentCopy,
		NcButton,
		QR,
		NcTextField,
	},
	props: {
		add: {
			type: Function,
			required: true,
		},
	},
	data() {
		return {
			adding: false,
			loading: false,
			deviceName: '',
			appPassword: '',
			loginName: '',
			copied: false,
			showQR: false,
			qrUrl: '',
		}
	},
	computed: {
		copyTooltipOptions() {
			if (this.copied) {
				return t('settings', 'Copied!')
			}
			return t('settings', 'Copy')
		},
	},
	methods: {
		selectInput(e) {
			e.currentTarget.select()
		},
		submit() {
			confirmPassword()
				.then(() => {
					this.loading = true
					return this.add(this.deviceName)
				})
				.then(token => {
					this.adding = true
					this.loginName = token.loginName
					this.appPassword = token.token

					const server = window.location.protocol + '//' + window.location.host + getRootUrl()
					this.qrUrl = `nc://login/user:${token.loginName}&password:${token.token}&server:${server}`

					this.$nextTick(() => {
						this.$refs.appPassword.select()
					})
				})
				.catch(err => {
					console.error('could not create a new app password', err)
					OC.Notification.showTemporary(t('settings', 'Error while creating device token'))

					this.reset()
				})
		},
		async copyPassword() {
			try {
				await navigator.clipboard.writeText(this.appPassword)
				this.copied = true
			} catch (e) {
				this.copied = false
				console.error(e)
				showError(t('settings', 'Could not copy app password. Please copy it manually.'))
			} finally {
				setTimeout(() => {
					this.copied = false
				}, 4000)
			}
		},
		reset() {
			this.adding = false
			this.loading = false
			this.showQR = false
			this.qrUrl = ''
			this.deviceName = ''
			this.appPassword = ''
			this.loginName = ''
		},
	},
}
</script>

<style lang="scss" scoped>
	.app-password-row {
		display: flex;
		align-items: center;

		.icon {
			background-size: 16px 16px;
			display: inline-block;
			position: relative;
			top: 3px;
			margin-left: 5px;
			margin-right: 8px;
		}

	}

	.app-password-label {
		display: table-cell;
		padding-right: 1em;
		text-align: right;
		vertical-align: middle;
		width: 100px;
	}

	.app-name-text-field {
		height: 44px !important;
		padding-left: 12px;
		margin-right: 12px;
		width: 200px;
	}

	.monospaced {
		width: 245px;
		font-family: monospace;
	}

	.button-vue{
		display:inline-block;
		margin: 3px 3px 3px 3px;
	}

	.row {
		display: flex;
		align-items: center;
	}

	.spacing {
		padding-top: 16px;
	}
</style>
