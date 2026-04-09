<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import QR from '@chenfengyuan/vue-qrcode'
import { t } from '@nextcloud/l10n'
import { getBaseUrl } from '@nextcloud/router'
import { useFormatRelativeTime } from '@nextcloud/vue/composables/useFormatDateTime'
import { computed } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'

const props = defineProps<{
	data: {
		token?: string
		loginName?: string
		deviceToken?: {
			id: number
			name: string
			lastActivity: number
			type: number
			scope: {
				filesystem: boolean
			}
			canDelete: boolean
			canRename: boolean
		}
	}
}>()

const emit = defineEmits<{
	(event: 'close', value?: unknown): void
}>()

const productName = window.OC.theme.productName as string

const buttons = [{
	label: t('spreed', 'Done'),
	variant: 'primary',
	callback: () => undefined,
}]

const isOneTimeToken = (props.data?.deviceToken?.type ?? 1) === 3

const qrUrl = computed(() => {
	const user = props.data?.loginName ?? ''
	const password = props.data?.token ?? ''
	const path = isOneTimeToken ? 'onetime-login' : 'login'
	const server = getBaseUrl()

	// TODO return different result for error handling (to not provide invalid URL)
	return `nc://${path}/user:${user}&password:${password}&server:${server}`
})

const expirationTimestamp = (props.data?.deviceToken?.lastActivity ? props.data.deviceToken.lastActivity * 1_000 : Date.now()) + 120_000
const expireTimeout = setTimeout(() => {
	onClosing('expired')
}, expirationTimestamp - Date.now())
const timeCountdown = useFormatRelativeTime(expirationTimestamp)

/**
 * Emit result, if any (for spawnDialog callback)
 *
 * @param result callback result
 */
function onClosing(result: unknown) {
	clearTimeout(expireTimeout)
	emit('close', result)
}
</script>

<template>
	<NcDialog
		:name="t('core', 'Scan QR code to log in')"
		:buttons="buttons"
		@closing="onClosing">
		<div class="qr-login__content">
			<p class="qr-login__description">
				{{ t('core', 'Use {productName} mobile client you want to connect to scan the code', { productName }) }}
			</p>
			<QR :value="qrUrl" />
			<template v-if="isOneTimeToken">
				<!-- TRANSLATORS Intl will provide a conjunction, e.g. 'Code will expire in 30 seconds' -->
				{{ t('core', 'Code will expire {timeCountdown} or after use', { timeCountdown }) }}
			</template>
		</div>
	</NcDialog>
</template>

<style lang="scss">
.qr-login__content {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: var(--default-grid-baseline);
}

.qr-login__description {
	text-align: center;
}
</style>
