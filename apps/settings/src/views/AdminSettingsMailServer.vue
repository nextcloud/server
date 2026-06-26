<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import axios, { isAxiosError } from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcCheckboxRadioSwitch, NcLoadingIcon, NcPasswordField } from '@nextcloud/vue'
import { computed, ref } from 'vue'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormGroup from '@nextcloud/vue/components/NcFormGroup'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import logger from '../logger.ts'

const settingsAdminMail = loadState<{
	configIsReadonly: boolean
	docUrl: string
	smtpModeOptions: { label: string, id: string }[]
	smtpEncryptionOptions: { label: string, id: string }[]
	smtpSendmailModeOptions: { label: string, id: string }[]
}>('settings', 'settingsAdminMail')

const initialConfig = loadState<{
	mail_domain: string
	mail_from_address: string
	mail_smtpmode: string
	mail_smtpsecure: string
	mail_smtphost: string
	mail_smtpport: string
	mail_smtpauth: boolean
	mail_smtpname: string
	mail_smtppassword: string
	mail_sendmailmode: string

	mail_noverify: boolean
}>('settings', 'settingsAdminMailConfig')
const mailConfig = ref({ ...initialConfig })

const smtpMode = computed({
	get() {
		return settingsAdminMail.smtpModeOptions.find((option) => option.id === mailConfig.value.mail_smtpmode)
	},
	set(value) {
		mailConfig.value.mail_smtpmode = value?.id ?? ''
	},
})
const smtpEncryption = computed({
	get() {
		return settingsAdminMail.smtpEncryptionOptions.find((option) => option.id === mailConfig.value.mail_smtpsecure)
	},
	set(value) {
		mailConfig.value.mail_smtpsecure = value?.id ?? ''
	},
})
const smtpSendmailMode = computed({
	get() {
		return settingsAdminMail.smtpSendmailModeOptions.find((option) => option.id === mailConfig.value.mail_sendmailmode)
	},
	set(value) {
		mailConfig.value.mail_sendmailmode = value?.id ?? ''
	},
})

const hasPasswordChanges = computed(() => mailConfig.value.mail_smtppassword !== '********')
const hasCredentialChanges = computed(() => hasPasswordChanges.value || mailConfig.value.mail_smtpname !== initialConfig.mail_smtpname)

const isSaving = ref(false)
const isSendingTestEmail = ref(false)
const testEmailError = ref('')

/**
 * Send a test email to verify the email settings
 */
async function testEmail() {
	testEmailError.value = ''
	isSendingTestEmail.value = true
	try {
		await axios.post(generateUrl('/settings/admin/mailtest'))
		showSuccess(t('settings', 'Email sent successfully'))
	} catch (error) {
		logger.error('Error sending test email', { error })
		showError(t('settings', 'Failed to send email'))

		if (isAxiosError(error) && typeof error.response?.data === 'string') {
			testEmailError.value = error.response.data
		}
	} finally {
		isSendingTestEmail.value = false
	}
}

/**
 * Submit the mail settings form
 */
async function onSubmit() {
	await confirmPassword()

	isSaving.value = true
	try {
		if (mailConfig.value.mail_smtpauth && hasCredentialChanges.value) {
			await axios.post(generateUrl('/settings/admin/mailsettings/credentials'), {
				mail_smtppassword: hasPasswordChanges.value ? mailConfig.value.mail_smtppassword : undefined,
				mail_smtpname: mailConfig.value.mail_smtpname,
			})
		}

		const config: Record<string, string | boolean> = { ...mailConfig.value }
		delete config.mail_smtppassword
		delete config.mail_smtpname
		await axios.post(generateUrl('/settings/admin/mailsettings'), config)

		testEmailError.value = ''
	} catch (error) {
		logger.error('Error saving email settings', { error })
		showError(t('settings', 'Failed to save email settings'))
		return
	} finally {
		isSaving.value = false
	}
}
</script>

<template>
	<NcSettingsSection
		:doc-url="settingsAdminMail.docUrl"
		:name="t('settings', 'Email server')"
		:description="t('settings', 'It is important to set up this server to be able to send emails, like for password reset and notifications.')">
		<NcNoteCard v-if="settingsAdminMail.configIsReadonly" type="info">
			{{ t('settings', 'The server configuration is read-only so the mail settings cannot be changed using the web interface.') }}
		</NcNoteCard>

		<NcNoteCard v-if="smtpMode?.id === 'null'" type="info">
			{{ t('settings', 'Mail delivery is disabled by instance config "{config}".', { config: 'mail_smtpmode' }) }}
		</NcNoteCard>

		<form v-else :class="$style.adminSettingsMailServer__form" @submit.prevent="onSubmit">
			<NcFormBox>
				<NcSelect
					v-model="smtpMode"
					:input-label="t('settings', 'Send mode')"
					:options="settingsAdminMail.smtpModeOptions"
					required />

				<NcSelect
					v-if="smtpMode?.id === 'smtp'"
					v-model="smtpEncryption"
					:input-label="t('settings', 'Encryption')"
					:options="settingsAdminMail.smtpEncryptionOptions"
					required />
				<NcSelect
					v-else-if="smtpMode?.id === 'sendmail'"
					v-model="smtpSendmailMode"
					:input-label="t('settings', 'Sendmail mode')"
					:options="settingsAdminMail.smtpSendmailModeOptions"
					required />

				<NcCheckboxRadioSwitch v-model="mailConfig.mail_noverify" type="switch">
					{{ t('settings', 'Disable certificate verification (insecure)') }}
				</NcCheckboxRadioSwitch>
			</NcFormBox>

			<NcFormGroup :label="t('settings', 'From address')">
				<NcFormBox row>
					<NcTextField v-model="mailConfig.mail_from_address" :label="t('settings', 'Email')" />
					<NcTextField v-model="mailConfig.mail_domain" :label="t('settings', 'Domain')">
						<template #icon>
							<div style="line-height: 1;">
								@
							</div>
						</template>
					</NcTextField>
				</NcFormBox>
			</NcFormGroup>

			<NcFormGroup v-show="smtpMode?.id === 'smtp'" :label="t('settings', 'Server address')">
				<NcFormBox row>
					<NcTextField
						v-model="mailConfig.mail_smtphost"
						:label="t('settings', 'Host')"
						name="mail_smtphost" />
					<NcTextField
						v-model="mailConfig.mail_smtpport"
						:label="t('settings', 'Port')"
						type="number"
						max="65535"
						min="1"
						name="mail_smtpport">
						<template #icon>
							<div style="line-height: 1;">
								:
							</div>
						</template>
					</NcTextField>
				</NcFormBox>
			</NcFormGroup>

			<NcFormGroup v-show="smtpMode?.id === 'smtp'" :label="t('settings', 'Authentication')">
				<NcCheckboxRadioSwitch v-model="mailConfig.mail_smtpauth" type="switch">
					{{ t('settings', 'Authentication required') }}
				</NcCheckboxRadioSwitch>

				<NcFormBox v-show="mailConfig.mail_smtpauth">
					<NcTextField
						v-model="mailConfig.mail_smtpname"
						:label="t('settings', 'Login')"
						name="mail_smtpname" />
					<NcPasswordField
						v-model="mailConfig.mail_smtppassword"
						:label="t('settings', 'Password')"
						:show-trailing-button="hasPasswordChanges"
						name="mail_smtppassword" />
				</NcFormBox>
			</NcFormGroup>

			<div :class="$style.adminSettingsMailServer__formAction">
				<NcButton
					:disabled="isSendingTestEmail"
					variant="success"
					@click="testEmail">
					<template v-if="isSendingTestEmail" #icon>
						<NcLoadingIcon />
					</template>
					{{ isSendingTestEmail ? t('settings', 'Sending test email…') : t('settings', 'Send test email') }}
				</NcButton>
				<NcButton
					:disabled="isSaving"
					type="submit"
					variant="primary">
					<template v-if="isSaving" #icon>
						<NcLoadingIcon />
					</template>
					{{ isSaving ? t('settings', 'Saving…') : t('settings', 'Save settings') }}
				</NcButton>
			</div>
		</form>

		<NcNoteCard v-if="testEmailError" type="error">
			{{ testEmailError }}
		</NcNoteCard>
	</NcSettingsSection>
</template>

<style module>
.adminSettingsMailServer__form {
	display: flex;
	flex-direction: column;
	gap: calc(2.5 * var(--default-grid-baseline));

	max-width: 600px !important;
}

.adminSettingsMailServer__formAction {
	display: flex;
	justify-content: end;
	gap: var(--default-grid-baseline);
}
</style>
