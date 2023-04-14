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
	<form class="login-form" @submit.prevent="submit">
		<fieldset class="login-form__fieldset">
			<NcTextField id="user"
				:value.sync="user"
				name="user"
				autocapitalize="off"
				:label="t('core', 'Account name or email')"
				:label-visible="true"
				required
				@change="updateUsername" />
			<LoginButton :value="t('core', 'Reset password')" />

			<NcNoteCard v-if="message === 'send-success'"
				type="success">
				{{ t('core', 'If this account exists, a password reset message has been sent to its email address. If you do not receive it, verify your email address and/or account name, check your spam/junk folders or ask your local administration for help.') }}
			</NcNoteCard>
			<NcNoteCard v-else-if="message === 'send-error'"
				type="error">
				{{ t('core', 'Couldn\'t send reset email. Please contact your administrator.') }}
			</NcNoteCard>
			<NcNoteCard v-else-if="message === 'reset-error'"
				type="error">
				{{ t('core', 'Password cannot be changed. Please contact your administrator.') }}
			</NcNoteCard>

			<a class="login-form__link"
				href="#"
				@click.prevent="$emit('abort')">
				{{ t('core', 'Back to login') }}
			</a>
		</fieldset>
	</form>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import LoginButton from './LoginButton.vue'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

export default {
	name: 'ResetPassword',
	components: {
		LoginButton,
		NcNoteCard,
		NcTextField,
	},
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
			message: undefined,
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
		submit() {
			this.loading = true
			this.error = false
			this.message = ''
			const url = generateUrl('/lostpassword/email')

			const data = {
				user: this.user,
			}

			return axios.post(url, data)
				.then(resp => resp.data)
				.then(data => {
					if (data.status !== 'success') {
						throw new Error(`got status ${data.status}`)
					}

					this.message = 'send-success'
				})
				.catch(e => {
					console.error('could not send reset email request', e)

					this.error = true
					this.message = 'send-error'
				})
				.then(() => { this.loading = false })
		},
	},
}
</script>

<style lang="scss" scoped>
.login-form {
	text-align: left;
	font-size: 1rem;

	&__fieldset {
		width: 100%;
		display: flex;
		flex-direction: column;
		gap: .5rem;
	}

	&__link {
		display: block;
		font-weight: normal !important;
		padding-bottom: 1rem;
		cursor: pointer;
		font-size: var(--default-font-size);
		text-align: center;
		padding: .5rem 1rem 1rem 1rem;
	}
}
</style>
