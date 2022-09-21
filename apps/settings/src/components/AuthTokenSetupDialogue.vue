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
	<div v-if="!adding" class="row spacing">
		<!-- Port to TextField component when available -->
		<input v-model="deviceName"
			type="text"
			:maxlength="120"
			:disabled="loading"
			:placeholder="t('settings', 'App name')"
			@keydown.enter="submit">
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
			<label for="app-username" class="app-password-label">{{ t('settings', 'Account name') }}</label>
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
			<a ref="clipboardButton"
				v-tooltip="copyTooltipOptions"
				v-clipboard:copy="appPassword"
				v-clipboard:success="onCopyPassword"
				v-clipboard:error="onCopyPasswordFailed"
				class="icon icon-clippy"
				@mouseover="hoveringCopyButton = true"
				@mouseleave="hoveringCopyButton = false" />
			<NcButton @click="reset">
				{{ t('settings', 'Done') }}
			</NcButton>
		</div>
		<div class="app-password-row">
			<span class="app-password-label" />
			<a v-if="!showQR"
				@click="showQR = true">
				{{ t('settings', 'Show QR code for mobile apps') }}
			</a>
			<QR v-else
				:value="qrUrl" />
		</div>
	</div>
</template>

<script>
import QR from '@chenfengyuan/vue-qrcode'
import { confirmPassword } from '@nextcloud/password-confirmation'
import '@nextcloud/password-confirmation/dist/style.css'
import { getRootUrl } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/dist/Components/NcButton'

export default {
	name: 'AuthTokenSetupDialogue',
	components: {
		QR,
		NcButton,
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
			passwordCopied: false,
			showQR: false,
			qrUrl: '',
			hoveringCopyButton: false,
		}
	},
	computed: {
		copyTooltipOptions() {
			const base = {
				hideOnTargetClick: false,
				trigger: 'manual',
			}

			if (this.passwordCopied) {
				return {
					...base,
					content: t('settings', 'Copied!'),
					show: true,
				}
			} else {
				return {
					...base,
					content: t('settings', 'Copy'),
					show: this.hoveringCopyButton,
				}
			}
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
		onCopyPassword() {
			this.passwordCopied = true
			this.$refs.clipboardButton.blur()
			setTimeout(() => { this.passwordCopied = false }, 3000)
		},
		onCopyPasswordFailed() {
			OC.Notification.showTemporary(t('settings', 'Could not copy app password. Please copy it manually.'))
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

	.row input {
		height: 44px !important;
		padding: 7px 12px;
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
