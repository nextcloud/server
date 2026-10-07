<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import LoginButton from './LoginButton.vue'
import LoginNameInput from './LoginNameInput.vue'
import { logger } from '../../utils/logger.ts'

const username = defineModel<string>('username', { required: true })

defineEmits<{
	abort: []
}>()

const loading = ref(false)
const message = ref<'' | 'send-success' | 'send-error'>('')

/**
 * Request a password reset email for the entered account.
 */
async function submit() {
	loading.value = true
	message.value = ''

	try {
		const { data } = await axios.post(generateUrl('/lostpassword/email'), { user: username.value })
		if (data.status !== 'success') {
			throw new Error(`got status ${data.status}`)
		}
		message.value = 'send-success'
	} catch (error) {
		logger.error('could not send reset email request', { error })
		message.value = 'send-error'
	} finally {
		loading.value = false
	}
}
</script>

<template>
	<form :class="$style.resetPasswordForm" @submit.prevent="submit">
		<h2>{{ t('core', 'Reset password') }}</h2>

		<LoginNameInput id="user" v-model:user="username" />

		<LoginButton :loading="loading" :value="t('core', 'Reset password')" />

		<NcButton variant="tertiary" wide @click="$emit('abort')">
			{{ t('core', 'Back to login') }}
		</NcButton>

		<NcNoteCard
			v-if="message === 'send-success'"
			type="success">
			{{ t('core', 'If this account exists, a password reset message has been sent to its email address. If you do not receive it, verify your email address and/or Login, check your spam/junk folders or ask your local administration for help.') }}
		</NcNoteCard>
		<NcNoteCard
			v-else-if="message === 'send-error'"
			type="error">
			{{ t('core', 'Couldn\'t send reset email. Please contact your administrator.') }}
		</NcNoteCard>
	</form>
</template>

<style module>
.resetPasswordForm {
	display: flex;
	flex-direction: column;
	gap: .5rem;
	width: 100%;
}
</style>
