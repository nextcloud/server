<!--
	- @copyright 2021, Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
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
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<div>
		<div class="email">
			<input
				:id="inputId"
				ref="email"
				type="email"
				:placeholder="inputPlaceholder"
				:value="email"
				autocapitalize="none"
				autocomplete="on"
				autocorrect="off"
				@input="onEmailChange">

			<div class="email__actions-container">
				<transition name="fade">
					<span v-if="showCheckmarkIcon" class="icon-checkmark" />
					<span v-else-if="showErrorIcon" class="icon-error" />
				</transition>

				<template v-if="!primary">
					<FederationControl
						:account-property="accountProperty"
						:additional="true"
						:additional-value="email"
						:disabled="federationDisabled"
						:handle-additional-scope-change="saveAdditionalEmailScope"
						:scope.sync="localScope"
						@update:scope="onScopeChange" />
				</template>

				<Actions
					class="email__actions"
					:aria-label="t('settings', 'Email options')"
					:disabled="deleteDisabled"
					:force-menu="true">
					<ActionButton
						:aria-label="deleteEmailLabel"
						:close-after-click="true"
						:disabled="deleteDisabled"
						icon="icon-delete"
						@click.stop.prevent="deleteEmail">
						{{ deleteEmailLabel }}
					</ActionButton>
					<ActionButton v-if="!primary || !isNotificationEmail"
						:aria-label="setNotificationMailLabel"
						:close-after-click="true"
						:disabled="setNotificationMailDisabled"
						icon="icon-favorite"
						@click.stop.prevent="setNotificationMail">
						{{ setNotificationMailLabel }}
					</ActionButton>
				</Actions>
			</div>
		</div>

		<em v-if="isNotificationEmail">
			{{ t('settings', 'Primary email for password reset and notifications') }}
		</em>
	</div>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import { showError } from '@nextcloud/dialogs'
import debounce from 'debounce'

import FederationControl from '../shared/FederationControl'

import { ACCOUNT_PROPERTY_READABLE_ENUM, VERIFICATION_ENUM } from '../../../constants/AccountPropertyConstants'
import {
	removeAdditionalEmail,
	saveAdditionalEmail,
	saveAdditionalEmailScope,
	saveNotificationEmail,
	savePrimaryEmail,
	updateAdditionalEmail,
} from '../../../service/PersonalInfo/EmailService'
import { validateEmail } from '../../../utils/validate'

export default {
	name: 'Email',

	components: {
		Actions,
		ActionButton,
		FederationControl,
	},

	props: {
		email: {
			type: String,
			required: true,
		},
		index: {
			type: Number,
			default: 0,
		},
		primary: {
			type: Boolean,
			default: false,
		},
		scope: {
			type: String,
			required: true,
		},
		activeNotificationEmail: {
			type: String,
			default: '',
		},
		localVerificationState: {
			type: Number,
			default: VERIFICATION_ENUM.NOT_VERIFIED,
		},
	},

	data() {
		return {
			accountProperty: ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL,
			initialEmail: this.email,
			localScope: this.scope,
			saveAdditionalEmailScope,
			showCheckmarkIcon: false,
			showErrorIcon: false,
		}
	},

	computed: {
		deleteDisabled() {
			if (this.primary) {
				// Disable for empty primary email as there is nothing to delete
				// OR when initialEmail (reflects server state) and email (current input) are not the same
				return this.email === '' || this.initialEmail !== this.email
			} else if (this.initialEmail !== '') {
				return this.initialEmail !== this.email
			}
			return false
		},

		deleteEmailLabel() {
			if (this.primary) {
				return t('settings', 'Remove primary email')
			}
			return t('settings', 'Delete email')
		},

	  setNotificationMailDisabled() {
			return !this.primary && this.localVerificationState !== VERIFICATION_ENUM.VERIFIED
		},

	  setNotificationMailLabel() {
			if (this.isNotificationEmail) {
				return t('settings', 'Unset as primary email')
			} else if (!this.primary && this.localVerificationState !== VERIFICATION_ENUM.VERIFIED) {
				return t('settings', 'This address is not confirmed')
			}
			return t('settings', 'Set as primary email')
		},

		federationDisabled() {
			return !this.initialEmail
		},

		inputId() {
			if (this.primary) {
				return 'email'
			}
			return `email-${this.index}`
		},

		inputPlaceholder() {
			if (this.primary) {
				return t('settings', 'Your email address')
			}
			return t('settings', 'Additional email address {index}', { index: this.index + 1 })
		},

		  isNotificationEmail() {
			  return (this.email === this.activeNotificationEmail)
						|| (this.primary && this.activeNotificationEmail === '')
		  },
	},

	mounted() {
		if (!this.primary && this.initialEmail === '') {
			// $nextTick is needed here, otherwise it may not always work https://stackoverflow.com/questions/51922767/autofocus-input-on-mount-vue-ios/63485725#63485725
			this.$nextTick(() => this.$refs.email?.focus())
		}
	},

	methods: {
		onEmailChange(e) {
			this.$emit('update:email', e.target.value)
			this.debounceEmailChange(e.target.value.trim())
		},

		debounceEmailChange: debounce(async function(email) {
			if (validateEmail(email) || email === '') {
				if (this.primary) {
					await this.updatePrimaryEmail(email)
				} else {
					if (email) {
						if (this.initialEmail === '') {
							await this.addAdditionalEmail(email)
						} else {
							await this.updateAdditionalEmail(email)
						}
					}
				}
			}
		}, 500),

		async deleteEmail() {
			if (this.primary) {
				this.$emit('update:email', '')
				await this.updatePrimaryEmail('')
			} else {
				await this.deleteAdditionalEmail()
			}
		},

		async updatePrimaryEmail(email) {
			try {
				const responseData = await savePrimaryEmail(email)
				this.handleResponse({
					email,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				if (email === '') {
					this.handleResponse({
						errorMessage: t('settings', 'Unable to delete primary email address'),
						error: e,
					})
				} else {
					this.handleResponse({
						errorMessage: t('settings', 'Unable to update primary email address'),
						error: e,
					})
				}
			}
		},

		async addAdditionalEmail(email) {
			try {
				const responseData = await saveAdditionalEmail(email)
				this.handleResponse({
					email,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to add additional email address'),
					error: e,
				})
			}
		},

		async setNotificationMail() {
		  try {
			  const newNotificationMailValue = (this.primary || this.isNotificationEmail) ? '' : this.initialEmail
			  const responseData = await saveNotificationEmail(newNotificationMailValue)
			  this.handleResponse({
				  notificationEmail: newNotificationMailValue,
				  status: responseData.ocs?.meta?.status,
			  })
		  } catch (e) {
			  this.handleResponse({
				  errorMessage: 'Unable to choose this email for notifications',
				  error: e,
			  })
		  }
		},

		async updateAdditionalEmail(email) {
			try {
				const responseData = await updateAdditionalEmail(this.initialEmail, email)
				this.handleResponse({
					email,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update additional email address'),
					error: e,
				})
			}
		},

		async deleteAdditionalEmail() {
			try {
				const responseData = await removeAdditionalEmail(this.initialEmail)
				this.handleDeleteAdditionalEmail(responseData.ocs?.meta?.status)
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to delete additional email address'),
					error: e,
				})
			}
		},

		handleDeleteAdditionalEmail(status) {
			if (status === 'ok') {
				this.$emit('delete-additional-email')
			} else {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to delete additional email address'),
				})
			}
		},

		handleResponse({ email, notificationEmail, status, errorMessage, error }) {
			if (status === 'ok') {
				// Ensure that local state reflects server state
				if (email) {
					this.initialEmail = email
				} else if (notificationEmail !== undefined) {
					this.$emit('update:notification-email', notificationEmail)
				}
				this.showCheckmarkIcon = true
				setTimeout(() => { this.showCheckmarkIcon = false }, 2000)
			} else {
				showError(errorMessage)
				this.logger.error(errorMessage, error)
				this.showErrorIcon = true
				setTimeout(() => { this.showErrorIcon = false }, 2000)
			}
		},

		onScopeChange(scope) {
			this.$emit('update:scope', scope)
		},
	},
}
</script>

<style lang="scss" scoped>
.email {
	display: grid;
	align-items: center;

	input {
		grid-area: 1 / 1;
		width: 100%;
		height: 34px;
		margin: 3px 3px 3px 0;
		padding: 7px 6px;
		color: var(--color-main-text);
		border: 1px solid var(--color-border-dark);
		border-radius: var(--border-radius);
		background-color: var(--color-main-background);
		font-family: var(--font-face);
		cursor: text;
	}

	.email__actions-container {
		grid-area: 1 / 1;
		justify-self: flex-end;
		height: 30px;

		display: flex;
		gap: 0 2px;
		margin-right: 5px;

		.email__actions {
			opacity: 0.4 !important;

			&:hover,
			&:focus,
			&:active {
				opacity: 0.8 !important;
			}

			&::v-deep button {
				height: 30px !important;
				min-height: 30px !important;
				width: 30px !important;
				min-width: 30px !important;
			}
		}

		.icon-checkmark,
		.icon-error {
			height: 30px !important;
			min-height: 30px !important;
			width: 30px !important;
			min-width: 30px !important;
			top: 0;
			right: 0;
			float: none;
		}
	}
}

.fade-enter,
.fade-leave-to {
	opacity: 0;
}

.fade-enter-active {
	transition: opacity 200ms ease-out;
}

.fade-leave-active {
	transition: opacity 300ms ease-out;
}
</style>
