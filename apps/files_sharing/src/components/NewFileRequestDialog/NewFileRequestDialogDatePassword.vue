<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<!-- Password and expiration summary -->
		<NcNoteCard v-if="passwordAndExpirationSummary" type="success">
			{{ passwordAndExpirationSummary }}
		</NcNoteCard>

		<!-- Expiration date -->
		<fieldset class="file-request-dialog__expiration" data-cy-file-request-dialog-fieldset="expiration">
			<!-- Enable expiration -->
			<legend>{{ t('files_sharing', 'When should the request expire?') }}</legend>
			<NcCheckboxRadioSwitch v-show="!defaultExpireDateEnforced"
				:checked="defaultExpireDateEnforced || expirationDate !== null"
				:disabled="disabled || defaultExpireDateEnforced"
				@update:checked="onToggleDeadline">
				{{ t('files_sharing', 'Set a submission expiration date') }}
			</NcCheckboxRadioSwitch>

			<!-- Date picker -->
			<NcDateTimePickerNative v-if="expirationDate !== null"
				id="file-request-dialog-expirationDate"
				:disabled="disabled"
				:hide-label="true"
				:label="t('files_sharing', 'Expiration date')"
				:max="maxDate"
				:min="minDate"
				:placeholder="t('files_sharing', 'Select a date')"
				:required="defaultExpireDateEnforced"
				:value="expirationDate"
				name="expirationDate"
				type="date"
				@input="$emit('update:expirationDate', $event)" />

			<p v-if="defaultExpireDateEnforced" class="file-request-dialog__info">
				<IconInfo :size="18" class="file-request-dialog__info-icon" />
				{{ t('files_sharing', 'Your administrator has enforced a {count} days expiration policy.', { count: defaultExpireDate }) }}
			</p>
		</fieldset>

		<!-- Password -->
		<fieldset class="file-request-dialog__password" data-cy-file-request-dialog-fieldset="password">
			<!-- Enable password -->
			<legend>{{ t('files_sharing', 'What password should be used for the request?') }}</legend>
			<NcCheckboxRadioSwitch v-show="!enforcePasswordForPublicLink"
				:checked="enforcePasswordForPublicLink || password !== null"
				:disabled="disabled || enforcePasswordForPublicLink"
				@update:checked="onTogglePassword">
				{{ t('files_sharing', 'Set a password') }}
			</NcCheckboxRadioSwitch>

			<div v-if="password !== null" class="file-request-dialog__password-field">
				<NcPasswordField ref="passwordField"
					:check-password-strength="true"
					:disabled="disabled"
					:label="t('files_sharing', 'Password')"
					:placeholder="t('files_sharing', 'Enter a valid password')"
					:required="false"
					:value="password"
					name="password"
					@update:value="$emit('update:password', $event)" />
				<NcButton :aria-label="t('files_sharing', 'Generate a new password')"
					:title="t('files_sharing', 'Generate a new password')"
					type="tertiary-no-background"
					@click="onGeneratePassword">
					<template #icon>
						<IconPasswordGen :size="20" />
					</template>
				</NcButton>
			</div>

			<p v-if="enforcePasswordForPublicLink" class="file-request-dialog__info">
				<IconInfo :size="18" class="file-request-dialog__info-icon" />
				{{ t('files_sharing', 'Your administrator has enforced a password protection.') }}
			</p>
		</fieldset>
	</div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import { t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcDateTimePickerNative from '@nextcloud/vue/dist/Components/NcDateTimePickerNative.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'

import IconInfo from 'vue-material-design-icons/Information.vue'
import IconPasswordGen from 'vue-material-design-icons/AutoFix.vue'

import Config from '../../services/ConfigService'
import GeneratePassword from '../../utils/GeneratePassword'

const sharingConfig = new Config()

export default defineComponent({
	name: 'NewFileRequestDialogDatePassword',

	components: {
		IconInfo,
		IconPasswordGen,
		NcButton,
		NcCheckboxRadioSwitch,
		NcDateTimePickerNative,
		NcNoteCard,
		NcPasswordField,
	},

	props: {
		disabled: {
			type: Boolean,
			required: false,
			default: false,
		},
		expirationDate: {
			type: Date as PropType<Date | null>,
			required: false,
			default: null,
		},
		password: {
			type: String as PropType<string | null>,
			required: false,
			default: null,
		},
	},

	emits: [
		'update:expirationDate',
		'update:password',
	],

	setup() {
		return {
			t,

			// Default expiration date if defaultExpireDateEnabled is true
			defaultExpireDate: sharingConfig.defaultExpireDate,
			// Default expiration date is enabled for public links (can be disabled)
			defaultExpireDateEnabled: sharingConfig.isDefaultExpireDateEnabled,
			// Default expiration date is enforced for public links (can't be disabled)
			defaultExpireDateEnforced: sharingConfig.isDefaultExpireDateEnforced,

			// Default password protection is enabled for public links (can be disabled)
			enableLinkPasswordByDefault: sharingConfig.enableLinkPasswordByDefault,
			// Password protection is enforced for public links (can't be disabled)
			enforcePasswordForPublicLink: sharingConfig.enforcePasswordForPublicLink,
		}
	},

	data() {
		return {
			maxDate: null as Date | null,
			minDate: new Date(new Date().setDate(new Date().getDate() + 1)),
		}
	},

	computed: {
		passwordAndExpirationSummary(): string {
			if (this.expirationDate && this.password) {
				return t('files_sharing', 'The request will expire on {date} at midnight and will be password protected.', {
					date: this.expirationDate.toLocaleDateString(),
				})
			}

			if (this.expirationDate) {
				return t('files_sharing', 'The request will expire on {date} at midnight.', {
					date: this.expirationDate.toLocaleDateString(),
				})
			}

			if (this.password) {
				return t('files_sharing', 'The request will be password protected.')
			}

			return ''
		},
	},

	mounted() {
		// If defined, we set the default expiration date
		if (this.defaultExpireDate) {
			this.$emit('update:expirationDate', sharingConfig.defaultExpirationDate)
		}

		// If enforced, we cannot set a date before the default expiration days (see admin settings)
		if (this.defaultExpireDateEnforced) {
			this.maxDate = sharingConfig.defaultExpirationDate
		}

		// If enabled by default, we generate a valid password
		if (this.enableLinkPasswordByDefault) {
			this.generatePassword()
		}
	},

	methods: {
		onToggleDeadline(checked: boolean) {
			this.$emit('update:expirationDate', checked ? (this.maxDate || this.minDate) : null)
		},

		async onTogglePassword(checked: boolean) {
			if (checked) {
				this.generatePassword()
				return
			}
			this.$emit('update:password', null)
		},

		async onGeneratePassword() {
			await this.generatePassword()
			this.showPassword()
		},

		async generatePassword() {
			await GeneratePassword().then(password => {
				this.$emit('update:password', password)
			})
		},

		showPassword() {
			// @ts-expect-error isPasswordHidden is private
			this.$refs.passwordField.isPasswordHidden = false
		},
	},
})
</script>

<style scoped lang="scss">
.file-request-dialog__password-field {
	display: flex;
	align-items: flex-start;
	gap: 8px;
	// Compensate label gab with legend
	margin-top: 12px;
	> div {
		// Force margin to 0 as we handle it above
		margin: 0;
	}
}
</style>
