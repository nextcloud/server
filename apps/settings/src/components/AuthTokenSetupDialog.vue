<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog
		:open.sync="open"
		:name="t('settings', 'New app password')"
		content-classes="token-dialog">
		<p>
			{{ t('settings', 'Use the credentials below to configure your app or device. For security reasons this password will only be shown once.') }}
		</p>
		<div class="token-dialog__name">
			<NcTextField :label="t('settings', 'Login')" :model-value="loginName" readonly />
			<NcButton
				variant="tertiary"
				:title="copyLoginNameLabel"
				:aria-label="copyLoginNameLabel"
				@click="copyLoginName">
				<template #icon>
					<NcIconSvgWrapper :path="copyNameIcon" />
				</template>
			</NcButton>
		</div>
		<div class="token-dialog__password">
			<NcTextField
				ref="appPassword"
				:label="t('settings', 'Password')"
				:model-value="appPassword"
				readonly />
			<NcButton
				variant="tertiary"
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
import type { PropType } from 'vue'
import type { ITokenResponse } from '../store/authtoken.ts'

import QR from '@chenfengyuan/vue-qrcode'
import { mdiCheck, mdiContentCopy } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { getRootUrl } from '@nextcloud/router'
import { defineComponent } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import logger from '../logger.ts'

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
			type: Object as PropType<ITokenResponse | null>,
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
