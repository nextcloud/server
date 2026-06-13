<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<form class="reset-password-form" @submit.prevent="submit">
		<h2>{{ t('core', 'Reset password') }}</h2>

		<NcTextField
			id="user"
			v-model="user"
			name="user"
			:maxlength="255"
			autocapitalize="off"
			:label="t('core', 'Login or email')"
			:error="userNameInputLengthIs255"
			:helper-text="userInputHelperText"
			required
			@change="updateUsername" />

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
		<NcNoteCard
			v-else-if="message === 'reset-error'"
			type="error">
			{{ t('core', 'Password cannot be changed. Please contact your administrator.') }}
		</NcNoteCard>
	</form>
</template>

<script lang="ts">
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { defineComponent } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import LoginButton from './LoginButton.vue'
import logger from '../../logger.js'
import AuthMixin from '../../mixins/auth.js'

export default defineComponent({
	name: 'ResetPassword',
	components: {
		LoginButton,
		NcButton,
		NcNoteCard,
		NcTextField,
	},

	mixins: [AuthMixin],

	props: {
		username: {
			type: String,
			required: true,
		},

		resetPasswordLink: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			error: false,
			loading: false,
			message: '',
			user: this.username,
		}
	},

	watch: {
		username(value) {
			this.user = value
		},
	},

	methods: {
		updateUsername() {
			this.$emit('update:username', this.user)
		},

		async submit() {
			this.loading = true
			this.error = false
			this.message = ''
			const url = generateUrl('/lostpassword/email')

			try {
				const { data } = await axios.post(url, { user: this.user })
				if (data.status !== 'success') {
					throw new Error(`got status ${data.status}`)
				}

				this.message = 'send-success'
			} catch (error) {
				logger.error('could not send reset email request', { error })

				this.error = true
				this.message = 'send-error'
			} finally {
				this.loading = false
			}
		},
	},
})
</script>

<style lang="scss" scoped>
.reset-password-form {
	display: flex;
	flex-direction: column;
	gap: .5rem;
	width: 100%;
}
</style>
