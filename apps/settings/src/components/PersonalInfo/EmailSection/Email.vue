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
			<NcInputField :id="inputIdWithDefault"
				ref="email"
				autocapitalize="none"
				autocomplete="email"
				:error="hasError || !!helperText"
				:helper-text="helperText || undefined"
				:label="inputPlaceholder"
				:placeholder="inputPlaceholder"
				spellcheck="false"
				:success="isSuccess"
				type="email"
				:value.sync="emailAddress" />

			<div class="email__actions">
				<NcActions :aria-label="actionsLabel" @close="showFederationSettings = false">
					<template v-if="showFederationSettings">
						<NcActionButton @click="showFederationSettings = false">
							<template #icon>
								<NcIconSvgWrapper :path="mdiArrowLeft" />
							</template>
							{{ t('settings', 'Back') }}
						</NcActionButton>
						<FederationControlActions :readable="propertyReadable"
							:additional="true"
							:additional-value="email"
							:disabled="federationDisabled"
							:handle-additional-scope-change="saveAdditionalEmailScope"
							:scope.sync="localScope"
							@update:scope="onScopeChange" />
					</template>
					<template v-else>
						<NcActionButton v-if="!federationDisabled && !primary"
							@click="showFederationSettings = true">
							<template #icon>
								<NcIconSvgWrapper :path="mdiLock" />
							</template>
							{{ t('settings', 'Change scope level of {property}', { property: propertyReadable.toLocaleLowerCase() }) }}
						</NcActionButton>
						<NcActionCaption v-if="!isConfirmedAddress"
							:name="t('settings', 'This address is not confirmed')" />
						<NcActionButton close-after-click
							:disabled="deleteDisabled"
							@click="deleteEmail">
							<template #icon>
								<NcIconSvgWrapper :path="mdiTrashCan" />
							</template>
							{{ deleteEmailLabel }}
						</NcActionButton>
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
					</template>
				</NcActions>
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
import NcActionCaption from '@nextcloud/vue/dist/Components/NcActionCaption.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcInputField from '@nextcloud/vue/dist/Components/NcInputField.js'
import debounce from 'debounce'

import { mdiArrowLeft, mdiLock, mdiStar, mdiStarOutline, mdiTrashCan } from '@mdi/js'
import FederationControlActions from '../shared/FederationControlActions.vue'
import { handleError } from '../../../utils/handlers.js'

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
		NcActionCaption,
		NcIconSvgWrapper,
		NcInputField,
		FederationControlActions,
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
			this.helperText = this.$refs.email?.$refs.input?.validationMessage || null
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
	display: flex;
	flex-direction: row;
	align-items: start;
	gap: 4px;

	&__actions {
		display: flex;
		gap: 0 2px;
		margin-right: 5px;
		margin-top: 6px;
	}
}
</style>
