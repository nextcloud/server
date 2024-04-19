<!--
	- @copyright 2023 Ferdinand Thiessen <opensource@fthiessen.de>
	-
	- @author Ferdinand Thiessen <opensource@fthiessen.de>
	-
	- @license AGPL-3.0-or-later
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->
<template>
	<NcDialog :open.sync="open"
		:name="t('settings', 'New app password')"
		content-classes="token-dialog">
		<p>
			{{ t('settings', 'Use the credentials below to configure your app or device. For security reasons this password will only be shown once.') }}
		</p>
		<div class="token-dialog__name">
			<NcTextField :label="t('settings', 'Login')" :value="loginName" readonly />
			<NcButton type="tertiary"
				:title="copyLoginNameLabel"
				:aria-label="copyLoginNameLabel"
				@click="copyLoginName">
				<template #icon>
					<NcIconSvgWrapper :path="copyNameIcon" />
				</template>
			</NcButton>
		</div>
		<div class="token-dialog__password">
			<NcTextField ref="appPassword"
				:label="t('settings', 'Password')"
				:value="appPassword"
				readonly />
			<NcButton type="tertiary"
				:title="copyPasswordLabel"
				:aria-label="copyPasswordLabel"
				@click="copyPassword">
				<template #icon>
					<NcIconSvgWrapper :path="copyPasswordIcon" />
				</template>
			</NcButton>
		</div>
		<div class="token-dialog__qrcode">
			<NcButton v-if="!showQRCode" @click="showQRCode = true">
				{{ t('settings', 'Show QR code for mobile apps') }}
			</NcButton>
			<QR v-else :value="qrUrl" />
		</div>
	</NcDialog>
</template>

<script lang="ts">
import type { ITokenResponse } from '../store/authtoken'

import { mdiCheck, mdiContentCopy } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { getRootUrl } from '@nextcloud/router'
import { defineComponent, type PropType } from 'vue'

import QR from '@chenfengyuan/vue-qrcode'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import logger from '../logger'

export default defineComponent({
	name: 'AuthTokenSetupDialog',
	components: {
		NcButton,
		NcDialog,
		NcIconSvgWrapper,
		NcTextField,
		QR,
	},
	props: {
		token: {
			type: Object as PropType<ITokenResponse|null>,
			required: false,
			default: null,
		},
	},
	data() {
		return {
			isNameCopied: false,
			isPasswordCopied: false,
			showQRCode: false,
		}
	},
	computed: {
		open: {
			get() {
				return this.token !== null
			},
			set(value: boolean) {
				if (!value) {
					this.$emit('close')
				}
			},
		},
		copyPasswordIcon() {
			return this.isPasswordCopied ? mdiCheck : mdiContentCopy
		},
		copyNameIcon() {
			return this.isNameCopied ? mdiCheck : mdiContentCopy
		},
		appPassword() {
			return this.token?.token ?? ''
		},
		loginName() {
			return this.token?.loginName ?? ''
		},
		qrUrl() {
			const server = window.location.protocol + '//' + window.location.host + getRootUrl()
			return `nc://login/user:${this.loginName}&password:${this.appPassword}&server:${server}`
		},
		copyPasswordLabel() {
			if (this.isPasswordCopied) {
				return t('settings', 'App password copied!')
			}
			return t('settings', 'Copy app password')
		},
		copyLoginNameLabel() {
			if (this.isNameCopied) {
				return t('settings', 'Login name copied!')
			}
			return t('settings', 'Copy login name')
		},
	},
	watch: {
		token() {
			// reset showing the QR code on token change
			this.showQRCode = false
		},
		open() {
			if (this.open) {
				this.$nextTick(() => {
					this.$refs.appPassword!.select()
				})
			}
		},
	},
	methods: {
		t,
		async copyPassword() {
			try {
				await navigator.clipboard.writeText(this.appPassword)
				this.isPasswordCopied = true
			} catch (e) {
				this.isPasswordCopied = false
				logger.error(e as Error)
				showError(t('settings', 'Could not copy app password. Please copy it manually.'))
			} finally {
				setTimeout(() => {
					this.isPasswordCopied = false
				}, 4000)
			}
		},
		async copyLoginName() {
			try {
				await navigator.clipboard.writeText(this.loginName)
				this.isNameCopied = true
			} catch (e) {
				this.isNameCopied = false
				logger.error(e as Error)
				showError(t('settings', 'Could not copy login name. Please copy it manually.'))
			} finally {
				setTimeout(() => {
					this.isNameCopied = false
				}, 4000)
			}
		},
	},
})
</script>

<style scoped lang="scss">
:deep(.token-dialog) {
	display: flex;
	flex-direction: column;
	gap: 12px;

	padding-inline: 22px;
	padding-block-end: 20px;

	> * {
		box-sizing: border-box;
	}
}

.token-dialog {
	&__name, &__password {
		align-items: end;
		display: flex;
		gap: 10px;

		:deep(input) {
			font-family: monospace;
		}
	}

	&__qrcode {
		display: flex;
		justify-content: center;
	}
}
</style>
