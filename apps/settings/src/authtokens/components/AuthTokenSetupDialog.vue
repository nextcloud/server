<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ITokenResponse } from '../store/authtoken.ts'

import QR from '@chenfengyuan/vue-qrcode'
import { mdiCheck, mdiContentCopy } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { getRootUrl } from '@nextcloud/router'
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import logger from '../../logger.ts'

const props = withDefaults(defineProps<{
	/** The newly created token, `null` while the dialog is closed */
	token?: ITokenResponse | null
}>(), {
	token: null,
})

const emit = defineEmits<{
	/** The dialog was closed */
	close: []
}>()

const passwordField = useTemplateRef('passwordField')

const isNameCopied = ref(false)
const isPasswordCopied = ref(false)
const showQRCode = ref(false)

const open = computed({
	get() {
		return props.token !== null
	},

	set(value: boolean) {
		if (!value) {
			emit('close')
		}
	},
})

const copyPasswordIcon = computed(() => isPasswordCopied.value ? mdiCheck : mdiContentCopy)

const copyNameIcon = computed(() => isNameCopied.value ? mdiCheck : mdiContentCopy)

const appPassword = computed(() => props.token?.token ?? '')

const loginName = computed(() => props.token?.loginName ?? '')

const qrUrl = computed(() => {
	const server = window.location.protocol + '//' + window.location.host + getRootUrl()
	return `nc://login/user:${loginName.value}&password:${appPassword.value}&server:${server}`
})

const copyPasswordLabel = computed(() => {
	if (isPasswordCopied.value) {
		return t('settings', 'App password copied!')
	}
	return t('settings', 'Copy app password')
})

const copyLoginNameLabel = computed(() => {
	if (isNameCopied.value) {
		return t('settings', 'Login name copied!')
	}
	return t('settings', 'Copy login name')
})

watch(() => props.token, () => {
	// reset showing the QR code on token change
	showQRCode.value = false
})

watch(open, () => {
	if (open.value) {
		nextTick(() => {
			passwordField.value!.select()
		})
	}
})

/**
 * Copy the app password to the clipboard
 */
async function copyPassword() {
	try {
		await navigator.clipboard.writeText(appPassword.value)
		isPasswordCopied.value = true
	} catch (e) {
		isPasswordCopied.value = false
		logger.error(e as Error)
		showError(t('settings', 'Could not copy app password. Please copy it manually.'))
	} finally {
		setTimeout(() => {
			isPasswordCopied.value = false
		}, 4000)
	}
}

/**
 * Copy the login name to the clipboard
 */
async function copyLoginName() {
	try {
		await navigator.clipboard.writeText(loginName.value)
		isNameCopied.value = true
	} catch (e) {
		isNameCopied.value = false
		logger.error(e as Error)
		showError(t('settings', 'Could not copy login name. Please copy it manually.'))
	} finally {
		setTimeout(() => {
			isNameCopied.value = false
		}, 4000)
	}
}
</script>

<template>
	<NcDialog
		v-model:open="open"
		:name="t('settings', 'New app password')"
		contentClasses="token-dialog">
		<p>
			{{ t('settings', 'Use the credentials below to configure your app or device. For security reasons this password will only be shown once.') }}
		</p>
		<div class="token-dialog__name">
			<NcTextField :label="t('settings', 'Login')" :modelValue="loginName" readonly />
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
				ref="passwordField"
				:label="t('settings', 'Password')"
				:modelValue="appPassword"
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
			<QR v-else :value="qrUrl" aria-hidden="true" />
		</div>
	</NcDialog>
</template>

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
