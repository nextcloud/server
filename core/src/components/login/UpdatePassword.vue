<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import LoginButton from './LoginButton.vue'

const props = defineProps<{
	resetPasswordTarget: string
}>()

const emit = defineEmits<{
	done: []
}>()

const loading = ref(false)
const message = ref('')
const password = ref('')
const encrypted = ref(false)
const proceed = ref(false)

/**
 * Set the new password, asking for confirmation first if the files are encrypted.
 */
async function submit() {
	loading.value = true
	message.value = ''

	try {
		const { data } = await axios.post(props.resetPasswordTarget, {
			password: password.value,
			proceed: proceed.value,
		})
		if (data?.status === 'success') {
			emit('done')
		} else if (data?.encryption) {
			encrypted.value = true
		} else {
			throw new Error(data?.msg)
		}
	} catch (error) {
		message.value = (error instanceof Error && error.message) || t('core', 'Password cannot be changed. Please contact your administrator.')
	} finally {
		loading.value = false
	}
}
</script>

<template>
	<form @submit.prevent="submit">
		<fieldset :class="$style.updatePassword">
			<p>
				<label for="password" class="infield">{{ t('core', 'New password') }}</label>
				<input
					id="password"
					v-model="password"
					type="password"
					name="password"
					autocomplete="new-password"
					autocapitalize="none"
					spellcheck="false"
					required
					:placeholder="t('core', 'New password')">
			</p>

			<div v-if="encrypted" class="update">
				<p>
					{{ t('core', 'Your files are encrypted. There will be no way to get your data back after your password is reset. If you are not sure what to do, please contact your administrator before you continue. Do you really want to continue?') }}
				</p>
				<input
					id="encrypted-continue"
					v-model="proceed"
					type="checkbox"
					class="checkbox">
				<label for="encrypted-continue">
					{{ t('core', 'I know what I\'m doing') }}
				</label>
			</div>

			<LoginButton
				:loading="loading"
				:value="t('core', 'Reset password')"
				:valueLoading="t('core', 'Resetting password')" />

			<p v-if="message" class="warning">
				{{ message }}
			</p>
		</fieldset>
	</form>
</template>

<style module>
.updatePassword {
	text-align: center;
}
</style>
