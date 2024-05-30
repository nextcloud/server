<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<form @submit.prevent="submit">
		<fieldset>
			<p>
				<label for="password" class="infield">{{ t('core', 'New password') }}</label>
				<input id="password"
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
				<input id="encrypted-continue"
					v-model="proceed"
					type="checkbox"
					class="checkbox">
				<label for="encrypted-continue">
					{{ t('core', 'I know what I\'m doing') }}
				</label>
			</div>

			<LoginButton :loading="loading"
				:value="t('core', 'Reset password')"
				:value-loading="t('core', 'Resetting password')" />

			<p v-if="error && message" :class="{warning: error}">
				{{ message }}
			</p>
		</fieldset>
	</form>
</template>

<script>
import Axios from '@nextcloud/axios'
import LoginButton from './LoginButton.vue'

export default {
	name: 'UpdatePassword',
	components: {
		LoginButton,
	},
	props: {
		username: {
			type: String,
			required: true,
		},
		resetPasswordTarget: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			error: false,
			loading: false,
			message: undefined,
			user: this.username,
			password: '',
			encrypted: false,
			proceed: false,
		}
	},
	watch: {
		username(value) {
			this.user = value
		},
	},
	methods: {
		async submit() {
			this.loading = true
			this.error = false
			this.message = ''

			try {
				const { data } = await Axios.post(this.resetPasswordTarget, {
					password: this.password,
					proceed: this.proceed,
				})
				if (data && data.status === 'success') {
					this.message = 'send-success'
					this.$emit('update:username', this.user)
					this.$emit('done')
				} else if (data && data.encryption) {
					this.encrypted = true
				} else if (data && data.msg) {
					throw new Error(data.msg)
				} else {
					throw new Error()
				}
			} catch (e) {
				this.error = true
				this.message = e.message ? e.message : t('core', 'Password cannot be changed. Please contact your administrator.')
			} finally {
				this.loading = false
			}
		},
	},
}
</script>

<style scoped>
	fieldset {
		text-align: center;
	}

	input[type=submit] {
		margin-top: 20px;
	}
</style>
