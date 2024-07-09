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
			<NcNoteCard v-if="defaultExpireDateEnforced" type="info">
				{{ t('files_sharing', 'Your administrator has enforced a default expiration date with a maximum {days} days.', { days: defaultExpireDate }) }}
			</NcNoteCard>

			<!-- Enable expiration -->
			<legend>{{ t('files_sharing', 'When should the request expire ?') }}</legend>
			<NcCheckboxRadioSwitch v-show="!defaultExpireDateEnforced"
				:checked="defaultExpireDateEnforced || expirationDate !== null"
				:disabled="disabled || defaultExpireDateEnforced"
				@update:checked="onToggleDeadline">
				{{ t('files_sharing', 'Set a submission expirationDate') }}
			</NcCheckboxRadioSwitch>

			<!-- Date picker -->
			<NcDateTimePickerNative v-if="expirationDate !== null"
				id="file-request-dialog-expirationDate"
				:disabled="disabled"
				:hide-label="true"
				:max="maxDate"
				:min="minDate"
				:placeholder="t('files_sharing', 'Select a date')"
				:required="defaultExpireDateEnforced"
				:value="expirationDate"
				name="expirationDate"
				type="date"
				@update:value="$emit('update:expirationDate', $event)" />
		</fieldset>

		<!-- Password -->
		<fieldset class="file-request-dialog__password" data-cy-file-request-dialog-fieldset="password">
			<NcNoteCard v-if="enforcePasswordForPublicLink" type="info">
				{{ t('files_sharing', 'Your administrator has enforced a password protection.') }}
			</NcNoteCard>

			<!-- Enable password -->
			<legend>{{ t('files_sharing', 'What password should be used for the request ?') }}</legend>
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
					:label-outside="true"
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
		</fieldset>
	</div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import { translate } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcDateTimePickerNative from '@nextcloud/vue/dist/Components/NcDateTimePickerNative.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'

import IconPasswordGen from 'vue-material-design-icons/AutoFix.vue'

import Config from '../../services/ConfigService'
import GeneratePassword from '../../utils/GeneratePassword'

const sharingConfig = new Config()

export default defineComponent({
	name: 'NewFileRequestDialogDatePassword',

	components: {
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
			t: translate,

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
				return this.t('files_sharing', 'The request will expire on {date} at midnight and will be password protected.', {
					date: this.expirationDate.toLocaleDateString(),
				})
			}

			if (this.expirationDate) {
				return this.t('files_sharing', 'The request will expire on {date} at midnight.', {
					date: this.expirationDate.toLocaleDateString(),
				})
			}

			if (this.password) {
				return this.t('files_sharing', 'The request will be password protected.')
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
			this.$emit('update:expirationDate', checked ? new Date() : null)
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
}
</style>
