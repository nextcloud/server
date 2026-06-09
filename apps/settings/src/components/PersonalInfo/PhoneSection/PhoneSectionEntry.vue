<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<div class="phone" :class="{ 'phone--additional': !primary }">
			<div v-if="!primary" class="phone__label-container">
				<label :for="inputIdWithDefault">{{ inputPlaceholder }}</label>
				<FederationControl
					v-if="!federationDisabled && !primary"
					:readable="propertyReadable"
					:additional="true"
					:additional-value="phone"
					:disabled="federationDisabled"
					:handle-additional-scope-change="saveAdditionalPhoneScope"
					:scope.sync="localScope"
					@update:scope="onScopeChange" />
			</div>
			<div class="phone__input-container">
				<NcTextField
					:id="inputIdWithDefault"
					ref="phone"
					v-model="phoneNumber"
					class="phone__input"
					autocomplete="tel"
					:error="hasError || !!helperText"
					:helper-text="helperTextWithNonConfirmed"
					label-outside
					:placeholder="inputPlaceholder"
					spellcheck="false"
					:success="isSuccess"
					type="tel" />

				<div class="phone__actions">
					<NcActions :aria-label="actionsLabel">
						<NcActionButton
							close-after-click
							:disabled="deleteDisabled"
							@click="deletePhone">
							<template #icon>
								<NcIconSvgWrapper :path="mdiTrashCanOutline" />
							</template>
							{{ deletePhoneLabel }}
						</NcActionButton>
					</NcActions>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { mdiTrashCanOutline } from '@mdi/js'
import { loadState } from '@nextcloud/initial-state'
import debounce from 'debounce'
import { isValidPhoneNumber } from 'libphonenumber-js'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import FederationControl from '../shared/FederationControl.vue'
import { ACCOUNT_PROPERTY_READABLE_ENUM } from '../../../constants/AccountPropertyConstants.js'
import {
	removeAdditionalPhone,
	saveAdditionalPhone,
	saveAdditionalPhoneScope,
	savePrimaryPhone,
	updateAdditionalPhone,
} from '../../../service/PersonalInfo/PhoneService.js'
import { handleError } from '../../../utils/handlers.ts'

const { defaultPhoneRegion } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'PhoneSectionEntry',

	components: {
		NcActions,
		NcActionButton,
		NcIconSvgWrapper,
		NcTextField,
		FederationControl,
	},

	props: {
		phone: {
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

		inputId: {
			type: String,
			required: false,
			default: '',
		},
	},

	setup() {
		return {
			mdiTrashCanOutline,
			saveAdditionalPhoneScope,
		}
	},

	data() {
		return {
			hasError: false,
			helperText: '',
			initialPhone: this.phone,
			isSuccess: false,
			localScope: this.scope,
			propertyReadable: ACCOUNT_PROPERTY_READABLE_ENUM.PHONE_COLLECTION,
		}
	},

	computed: {
		actionsLabel() {
			if (this.primary) {
				return t('settings', 'Phone number options')
			}
			return t('settings', 'Options for additional phone number {index}', { index: this.index + 1 })
		},

		deleteDisabled() {
			if (this.primary) {
				return this.phone === '' || this.initialPhone !== this.phone
			} else if (this.initialPhone !== '') {
				return this.initialPhone !== this.phone
			}
			return false
		},

		deletePhoneLabel() {
			if (this.primary) {
				return t('settings', 'Remove primary phone number')
			}
			return t('settings', 'Delete phone number')
		},

		helperTextWithNonConfirmed() {
			if (this.helperText || this.hasError || this.isSuccess) {
				return this.helperText || ''
			}
			return ''
		},

		federationDisabled() {
			return !this.initialPhone
		},

		inputIdWithDefault() {
			return this.inputId || `account-property-phone--${this.index}`
		},

		inputPlaceholder() {
			return !this.primary ? t('settings', 'Additional phone number {index}', { index: this.index + 1 }) : undefined
		},

		phoneNumber: {
			get() {
				return this.phone
			},

			set(value) {
				this.$emit('update:phone', value)
				this.debouncePhoneChange(value.trim())
			},
		},
	},

	mounted() {
		if (!this.primary && this.initialPhone === '') {
			this.$nextTick(() => this.$refs.phone?.focus())
		}
	},

	methods: {
		isValidPhone(value) {
			if (value === '') {
				return true
			}
			if (defaultPhoneRegion) {
				return isValidPhoneNumber(value, defaultPhoneRegion)
			}
			return isValidPhoneNumber(value)
		},

		debouncePhoneChange: debounce(async function(phone) {
			if (this.isValidPhone(phone)) {
				if (this.primary) {
					await this.updatePrimaryPhone(phone)
				} else {
					if (phone) {
						if (this.initialPhone === '') {
							await this.addAdditionalPhone(phone)
						} else {
							await this.updateAdditionalPhone(phone)
						}
					}
				}
			}
		}, 1000),

		async deletePhone() {
			if (this.primary) {
				this.$emit('update:phone', '')
				await this.updatePrimaryPhone('')
			} else {
				await this.deleteAdditionalPhone()
			}
		},

		async updatePrimaryPhone(phone) {
			try {
				const responseData = await savePrimaryPhone(phone)
				this.handleResponse({
					phone,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				if (phone === '') {
					this.handleResponse({
						errorMessage: t('settings', 'Unable to delete primary phone number'),
						error: e,
					})
				} else {
					this.handleResponse({
						errorMessage: t('settings', 'Unable to update primary phone number'),
						error: e,
					})
				}
			}
		},

		async addAdditionalPhone(phone) {
			try {
				const responseData = await saveAdditionalPhone(phone)
				this.handleResponse({
					phone,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to add additional phone number'),
					error: e,
				})
			}
		},

		async updateAdditionalPhone(phone) {
			try {
				const responseData = await updateAdditionalPhone(this.initialPhone, phone)
				this.handleResponse({
					phone,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update additional phone number'),
					error: e,
				})
			}
		},

		async deleteAdditionalPhone() {
			try {
				const responseData = await removeAdditionalPhone(this.initialPhone)
				this.handleDeleteAdditionalPhone(responseData.ocs?.meta?.status)
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to delete additional phone number'),
					error: e,
				})
			}
		},

		handleDeleteAdditionalPhone(status) {
			if (status === 'ok') {
				this.$emit('delete-additional-phone')
			} else {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to delete additional phone number'),
				})
			}
		},

		handleResponse({ phone, status, errorMessage, error }) {
			if (status === 'ok') {
				if (phone) {
					this.initialPhone = phone
				}
				this.isSuccess = true
				setTimeout(() => {
					this.isSuccess = false
				}, 2000)
			} else {
				handleError(error, errorMessage)
				this.hasError = true
				setTimeout(() => {
					this.hasError = false
				}, 2000)
			}
		},

		onScopeChange(scope) {
			this.$emit('update:scope', scope)
		},
	},
}
</script>

<style lang="scss" scoped>
.phone {
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
