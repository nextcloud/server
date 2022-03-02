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
-->

<template>
	<div>
		<div class="email-container">
			<input
				ref="email"
				type="email"
				:name="inputName"
				:placeholder="inputPlaceholder"
				:value="email"
				autocapitalize="none"
				autocomplete="on"
				autocorrect="off"
				required="true"
				@input="onEmailChange">

			<div class="email-actions-container">
				<transition name="fade">
					<span v-if="showCheckmarkIcon" class="icon-checkmark" />
					<span v-else-if="showErrorIcon" class="icon-error" />
				</transition>

				<FederationControl v-if="!primary"
					class="federation-control"
					:disabled="federationDisabled"
					:email="email"
					:scope.sync="localScope"
					@update:scope="onScopeChange" />

				<Actions
					class="actions-email"
					:aria-label="t('settings', 'Email options')"
					:disabled="deleteDisabled"
					:force-menu="true">
					<ActionButton
						:aria-label="deleteEmailLabel"
						:close-after-click="true"
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

import FederationControl from './FederationControl'
import { VERIFICATION_ENUM } from '../../../constants/AccountPropertyConstants'
import { savePrimaryEmail, saveAdditionalEmail, saveNotificationEmail, updateAdditionalEmail, removeAdditionalEmail } from '../../../service/PersonalInfoService'

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
		scope: {
			type: String,
			required: true,
		},
		primary: {
			type: Boolean,
			default: false,
		},
		index: {
			type: Number,
			default: 0,
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
			initialEmail: this.email,
			localScope: this.scope,
			showCheckmarkIcon: false,
			showErrorIcon: false,
		}
	},

	computed: {
		inputName() {
			if (this.primary) {
				return 'email'
			}
			return 'additionalEmail[]'
		},

		inputPlaceholder() {
			if (this.primary) {
				return t('settings', 'Your email address')
			}
			return t('settings', 'Additional email address {index}', { index: this.index + 1 })
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
			return t('settings', 'Set as primary mail')
		},

		federationDisabled() {
			return !this.initialEmail
		},

		deleteDisabled() {
			if (this.primary) {
				return this.email === ''
			}
			return this.email !== '' && !this.isValid()
		},

		deleteEmailLabel() {
			if (this.primary) {
				return t('settings', 'Remove primary email')
			}
			return t('settings', 'Delete email')
		},

		isNotificationEmail() {
			return (this.email && this.email === this.activeNotificationEmail)
				|| (this.primary && this.activeNotificationEmail === '')
		},
	},

	mounted() {
		if (!this.primary && this.initialEmail === '') {
			this.$nextTick(() => this.$refs.email?.focus())
		}
	},

	methods: {
		onEmailChange(e) {
			this.$emit('update:email', e.target.value)
			// $nextTick() ensures that references to this.email further down the chain give the correct non-outdated value
			this.$nextTick(() => this.debounceEmailChange())
		},

		debounceEmailChange: debounce(async function() {
			if (this.$refs.email?.checkValidity() || this.email === '') {
				if (this.primary) {
					await this.updatePrimaryEmail()
				} else {
					if (this.email) {
						if (this.initialEmail === '') {
							await this.addAdditionalEmail()
						} else {
							await this.updateAdditionalEmail()
						}
					}
				}
			}
		}, 500),

		async deleteEmail() {
			if (this.primary) {
				this.$emit('update:email', '')
				this.$nextTick(async() => await this.updatePrimaryEmail())
			} else {
				await this.deleteAdditionalEmail()
			}
		},

		async updatePrimaryEmail() {
			try {
				const responseData = await savePrimaryEmail(this.email)
				this.handleResponse(responseData.ocs?.meta?.status)
			} catch (e) {
				if (this.email === '') {
					this.handleResponse('error', 'Unable to delete primary email address', e)
				} else {
					this.handleResponse('error', 'Unable to update primary email address', e)
				}
			}
		},

		async addAdditionalEmail() {
			try {
				const responseData = await saveAdditionalEmail(this.email)
				this.handleResponse(responseData.ocs?.meta?.status)
			} catch (e) {
				this.handleResponse('error', 'Unable to add additional email address', e)
			}
		},

	  async setNotificationMail() {
		  try {
			  const newNotificationMailValue = (this.primary || this.isNotificationEmail) ? '' : this.initialEmail
			  const responseData = await saveNotificationEmail(newNotificationMailValue)
			  this.handleSetNotificationMailResponse({
				  notificationEmail: newNotificationMailValue,
				  status: responseData.ocs?.meta?.status,
			  })
		  } catch (e) {
			  this.handleSetNotificationMailResponse({
				  errorMessage: 'Unable to choose this email for notifications',
				  error: e,
			  })
		  }
	  },

		async updateAdditionalEmail() {
			try {
				const responseData = await updateAdditionalEmail(this.initialEmail, this.email)
				this.handleResponse(responseData.ocs?.meta?.status)
			} catch (e) {
				this.handleResponse('error', 'Unable to update additional email address', e)
			}
		},

		async deleteAdditionalEmail() {
			try {
				const responseData = await removeAdditionalEmail(this.initialEmail)
				this.handleDeleteAdditionalEmail(responseData.ocs?.meta?.status)
			} catch (e) {
				this.handleResponse('error', 'Unable to delete additional email address', e)
			}
		},

		isValid() {
			return /^\S+$/.test(this.email)
		},

		handleDeleteAdditionalEmail(status) {
			if (status === 'ok') {
				this.$emit('deleteAdditionalEmail')
			} else {
				this.handleResponse('error', 'Unable to delete additional email address', {})
			}
		},

		handleSetNotificationMailResponse({ notificationEmail, status, errorMessage, error }) {
			if (status === 'ok') {
				this.$emit('update:notification-email', notificationEmail)
				this.showCheckmarkIcon = true
				setTimeout(() => { this.showCheckmarkIcon = false }, 2000)
			} else {
				showError(t('settings', errorMessage))
				this.logger.error(errorMessage, error)
				this.showErrorIcon = true
				setTimeout(() => { this.showErrorIcon = false }, 2000)
			}
		},

		handleResponse(status, errorMessage, error) {
			if (status === 'ok') {
				// Ensure that local initialEmail state reflects server state
				this.initialEmail = this.email
				this.showCheckmarkIcon = true
				setTimeout(() => { this.showCheckmarkIcon = false }, 2000)
			} else {
				showError(t('settings', errorMessage))
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
	.email-container {
		display: grid;
		align-items: center;

		input[type=email] {
			grid-area: 1 / 1;
		}

		.email-actions-container {
			grid-area: 1 / 1;
			justify-self: flex-end;
			height: 30px;

			display: flex;
			gap: 0 2px;
			margin-right: 5px;

			.actions-email {
				opacity: 0.4 !important;

				&:hover {
					opacity: 0.8 !important;
				}

				&::v-deep button {
					height: 30px !important;
					min-height: 30px !important;
					width: 30px !important;
					min-width: 30px !important;
				}
			}

			.federation-control {
				&::v-deep button {
					// TODO remove this hack
					padding-bottom: 7px;
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

	.fade-enter-active {
		transition: opacity 200ms ease-out;
	}

	.fade-leave-active {
		transition: opacity 300ms ease-out;
	}

	.fade-enter,
	.fade-leave-to {
		opacity: 0;
	}
</style>
