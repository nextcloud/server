<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<div class="email" :class="{ 'email--additional': !primary }">
			<div v-if="!primary" class="email__label-container">
				<label :for="inputIdWithDefault">{{ inputPlaceholder }}</label>
				<FederationControl v-if="!federationDisabled && !primary"
					:readable="propertyReadable"
					:additional="true"
					:additional-value="email"
					:disabled="federationDisabled"
					:handle-additional-scope-change="saveAdditionalEmailScope"
					:scope.sync="localScope"
					@update:scope="onScopeChange" />
			</div>
			<div class="email__input-container">
				<NcTextField :id="inputIdWithDefault"
					ref="email"
					class="email__input"
					autocapitalize="none"
					autocomplete="email"
					:error="hasError || !!helperText"
					:helper-text="helperTextWithNonConfirmed"
					label-outside
					:placeholder="inputPlaceholder"
					spellcheck="false"
					:success="isSuccess"
					type="email"
					:value.sync="emailAddress" />

				<div class="email__actions">
					<NcActions :aria-label="actionsLabel">
						<NcActionButton v-if="!primary || !isNotificationEmail"
							close-after-click
							:disabled="!isConfirmedAddress"
							@click="setNotificationMail">
							<template #icon>
								<NcIconSvgWrapper v-if="isNotificationEmail" :path="mdiStar" />
								<NcIconSvgWrapper v-else :path="mdiStarOutline" />
							</template>
							{{ setNotificationMailLabel }}
						</NcActionButton>
						<NcActionButton close-after-click
							:disabled="deleteDisabled"
							@click="deleteEmail">
							<template #icon>
								<NcIconSvgWrapper :path="mdiTrashCan" />
							</template>
							{{ deleteEmailLabel }}
						</NcActionButton>
					</NcActions>
				</div>
			</div>
		</div>

		<em v-if="isNotificationEmail">
			{{ t('settings', 'Primary email for password reset and notifications') }}
		</em>
	</div>
</template>

<script>
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import debounce from 'debounce'

import { mdiArrowLeft, mdiLock, mdiStar, mdiStarOutline, mdiTrashCan } from '@mdi/js'

import FederationControl from '../shared/FederationControl.vue'
import { handleError } from '../../../utils/handlers.ts'

import { ACCOUNT_PROPERTY_READABLE_ENUM, VERIFICATION_ENUM } from '../../../constants/AccountPropertyConstants.js'
import {
	removeAdditionalEmail,
	saveAdditionalEmail,
	saveAdditionalEmailScope,
	saveNotificationEmail,
	savePrimaryEmail,
	updateAdditionalEmail,
} from '../../../service/PersonalInfo/EmailService.js'
import { validateEmail } from '../../../utils/validate.js'

export default {
	name: 'Email',

	components: {
		NcActions,
		NcActionButton,
		NcIconSvgWrapper,
		NcTextField,
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
		inputId: {
			type: String,
			required: false,
			default: '',
		},
	},

	setup() {
		return {
			mdiArrowLeft,
			mdiLock,
			mdiStar,
			mdiStarOutline,
			mdiTrashCan,
			saveAdditionalEmailScope,
		}
	},

	data() {
		return {
			hasError: false,
			helperText: null,
			initialEmail: this.email,
			isSuccess: false,
			localScope: this.scope,
			propertyReadable: ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL,
			showFederationSettings: false,
		}
	},

	computed: {
		actionsLabel() {
			if (this.primary) {
				return t('settings', 'Email options')
			} else {
				return t('settings', 'Options for additional email address {index}', { index: this.index + 1 })
			}
		},

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

		isConfirmedAddress() {
			return this.primary || this.localVerificationState === VERIFICATION_ENUM.VERIFIED
		},

		isNotConfirmedHelperText() {
			if (!this.isConfirmedAddress) {
				return t('settings', 'This address is not confirmed')
			}
			return ''
		},

		helperTextWithNonConfirmed() {
			if (this.helperText || this.hasError || this.isSuccess) {
				return this.helperText || ''
			}
			return this.isNotConfirmedHelperText
		},

		setNotificationMailLabel() {
			if (this.isNotificationEmail) {
				return t('settings', 'Unset as primary email')
			}
			return t('settings', 'Set as primary email')
		},

		federationDisabled() {
			return !this.initialEmail
		},

		inputIdWithDefault() {
			return this.inputId || `account-property-email--${this.index}`
		},

		inputPlaceholder() {
			// Primary email has implicit linked <label>
			return !this.primary ? t('settings', 'Additional email address {index}', { index: this.index + 1 }) : undefined
		},

		isNotificationEmail() {
			return (this.email && this.email === this.activeNotificationEmail)
				|| (this.primary && this.activeNotificationEmail === '')
		},

		emailAddress: {
			get() {
				return this.email
			},
			set(value) {
				this.$emit('update:email', value)
				this.debounceEmailChange(value.trim())
			},
		},
	},

	mounted() {
		if (!this.primary && this.initialEmail === '') {
			// $nextTick is needed here, otherwise it may not always work
			// https://stackoverflow.com/questions/51922767/autofocus-input-on-mount-vue-ios/63485725#63485725
			this.$nextTick(() => this.$refs.email?.focus())
		}
	},

	methods: {
		debounceEmailChange: debounce(async function(email) {
			// TODO: provide method to get native input in NcTextField
			this.helperText = this.$refs.email.$refs.inputField.$refs.input.validationMessage || null
			if (this.helperText !== null) {
				return
			}
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
		}, 1000),

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
				if (this.isNotificationEmail) {
					this.$emit('update:notification-email', '')
				}
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
				this.isSuccess = true
				setTimeout(() => { this.isSuccess = false }, 2000)
			} else {
				handleError(error, errorMessage)
				this.hasError = true
				setTimeout(() => { this.hasError = false }, 2000)
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
	&__label-container {
		height: var(--default-clickable-area);
		display: flex;
		flex-direction: row;
		align-items: center;
		gap: calc(var(--default-grid-baseline) * 2);
	}

	&__input-container {
		position: relative;
	}

	&__input {
		// TODO: provide a way to hide status icon or combine it with trailing button in NcInputField
		:deep(.input-field__icon--trailing) {
			display: none;
		}
	}

	&__actions {
		position: absolute;
		inset-block-start: 0;
		inset-inline-end: 0;
	}
}
</style>
