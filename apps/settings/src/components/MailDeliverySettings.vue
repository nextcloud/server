<!--
	- @copyright 2022 Carl Schwan <carl@carlschwan.eu>
	-
	- @author Carl Schwan <carl@carlschwan.eu>
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
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<NcSettingsSection :title="t('settings', 'Email server')"
		:description="t('settings', 'It is important to set up this server to be able to send emails, like for password reset and notifications.')"
		:doc-url="adminDocUrl"
		class="spacing">

		<div>
			<div class="form-control">
				<label for="mail_smtpmode">{{ t('settings', 'Send mode') }}</label>
				<select name="mail_smtpmode" id="mail_smtpmode">
					<option v-for="smtpMode in smtpModes"
						:key="smtpMode[0]"
						:value="smtpMode[0]"
						:selected="smtpMode[0] === mailSmtpMode">
							{{ smtpMode[1] }}
					</option>
				</select>
			</div>

			<div v-if="mailSmtpMode === 'smtp'" class="form-control">
				<label id="mail_smtpsecure_label" for="mail_smtpsecure">
					{{ t('settings', 'Encryption') }}
				</label>
				<select name="mail_smtpsecure" id="mail_smtpsecure">
					<option v-for="(secure, name) in smtpSecure"
						:key="secure"
						:value="secure"
						:selected="secure === mailSmtpSecure">
							{{ smtpSecure[name] }}
					</option>
				</select>
			</div>

			<div v-if="mailSmtpMode === 'sendmail'" class="form-control">
				<label for="mail_sendmailmode">
					{{ t('settings', 'Sendmail mode') }}
				</label>
				<select name="mail_sendmailmode" id="mail_sendmailmode">
					<option v-for="(sendmailmodeValue, sendmailmodeLabel) in sendmailModes"
						:value="sendmailmodeValue"
						:selected="sendmailmodeValue === 'mailSendmailMode'">
						{{ sendmailModes[sendmailmodeLabel] }}
					</option>
				</select>
			</div>

			<p class="form-control">
				<label for="mail_from_address">{{ t('settings', 'From address') }}</label>
				<input type="text" name="mail_from_address" id="mail_from_address" placeholder="noreply"
					   v-model="mailFromAddress" />
				@
				<input type="text" name="mail_domain" id="mail_domain" placeholder="example.com"
					   v-model="mailDomain" />
			</p>

			<p v-if="mailSmtpMode === 'smtp'" class="form-control">
				<label for="mail_smtpauthtype">{{ t('settings', 'Authentication method') }}</label>
				<select name="mail_smtpauthtype" id="mail_smtpauthtype">
					<option v-for="(name, authType) in smtpAuthType"
						:key="authType"
						:value="authType"
						:selected="authType === mailSmtpAuthType">
						{{ name }}
					</option>
				</select>

				<NcCheckboxRadioSwitch type="checkbox"
					:checked.sync="mailSmtpAuth">
					{{ t('settings', 'Authentication required') }}
				</NcCheckboxRadioSwitch>
			</p>

			<p v-if="mailSmtpMode === 'smtp'" class="form-control">
				<label for="mail_smtphost">{{ t('settings', 'Server address') }}</label>
				<input type="text"
					name="mail_smtphost"
					id="mail_smtphost"
					placeholder="smtp.example.com"
					v-model="mailSmtpHost" />
				:
				<input type="text"
					inputmode="numeric"
					name="mail_smtpport"
					id="mail_smtpport"
					:placeholder="t('settings', 'Port')"
					v-model="mailSmtpPort" />
			</p>
		</div>

		<form v-if="mailSmtpAuth && mailSmtpMode === 'smtp'" class="topMargin">
			<h3>{{ t('settings', 'Authentication') }}</h3>
			<p>
				<label for="mail_smtpname">{{ t('settings', 'SMTP username') }}</label>
				<input type="text"
					name="mail_smtpname"
					id="mail_smtpname"
					:value="mailSmtpName" />
			</p>
			<p>
				<label for="mailSmtpPassword">{{ t('settings', 'SMTP password') }}</label>
				<input type="text"
					id="mail_smtppassword"
					autocomplete="off"
					:value="mailSmtpPassword" />
				<NcButton nativeType="submit">
					{{ t('settings', 'Save') }}
				</NcButton>
			</p>
		</form>

		<div class="topMargin">
			<p>{{ t('settings', 'Test and verify email settings') }}</p>
			<NcButton @click="sendMail">
				{{ t('settings', 'Send email') }}
			</NcButton>
			<p v-id="sendMailMessage">{{ sendMailMessage }}</p>
		</div>
	</NcSettingsSection>
</template>

<script>

import axios from '@nextcloud/axios'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch'
import NcButton from '@nextcloud/vue/dist/Components/NcButton'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection'
import { loadState } from '@nextcloud/initial-state'
import { getLoggerBuilder } from '@nextcloud/logger'
import { generateOcsUrl } from '@nextcloud/router'
import { confirmPassword } from '@nextcloud/password-confirmation'
import '@nextcloud/password-confirmation/dist/style.css'
import { showError } from '@nextcloud/dialogs'

const smtpAuthType = {
	'': t('settings', 'None'),
	'LOGIN': t('settings', 'Login'),
	'PLAIN': t('settings', 'Plain'),
	'NTLM': t('settings', 'NT LAN Manager'),
}

const smtpSecure = {
	'': t('settings', 'None'),
	'ssl': t('settings', 'SSL/TLS'),
	'tls': t('settings', 'STARTTLS'),
}

const smtpModes = [
	['smtp', 'SMTP'],
]

if (loadState('settings', 'sendmail_is_available')) {
	smtpModes.push(['sendmail', 'Sendmail'])
}
if (loadState('settings', 'mail_smtpmode') === 'qmail') {
	// legacy only show it if it was previously enabled
	smtpModes.push(['qmail', 'qmail'])
}

const sendmailModes = {
	'smtp': 'smtp (-bs)',
	'pipe': 'pipe (-t)'
}

const logger = getLoggerBuilder()
	.setApp('settings')
	.detectUser()
	.build()

export default {
	name: 'MailDeliverySettings',
	components: {
		NcCheckboxRadioSwitch,
		NcSettingsSection,
		NcButton,
	},
	data() {
		return {
			smtpAuthType,
			smtpSecure,
			smtpModes,
			sendmailModes,

			mailFromAddress: loadState('settings', 'mail_from_address'),
			mailDomain: loadState('settings', 'mail_domain'),
			mailSmtpMode: loadState('settings', 'mail_smtpmode'),
			mailSmtpSecure: loadState('settings', 'mail_smtpsecure'),
			mailSmtpHost: loadState('settings', 'mail_smtphost'),
			mailSmtpPort: loadState('settings', 'mail_smtpport'),
			mailSmtpAuthType: loadState('settings', 'mail_smtpauthtype'),
			mailSmtpAuth: loadState('settings', 'mail_smtpauth'),
			mailSmtpName: loadState('settings', 'mail_smtpname'),
			mailSmtpPassword: loadState('settings', 'mail_smtppassword'),
			mailSendmailMode: loadState('settings', 'mail_sendmailmode'),
			adminDocUrl: loadState('settings', 'emailAdminDocUrl'),
		}
	},
	methods: {
		async update(key, value) {
			await confirmPassword()

			const url = generateOcsUrl('/apps/provisioning_api/api/v1/config/apps/{appId}/{key}', {
				appId: 'core',
				key,
			})

			const stringValue = value ? 'yes' : 'no'
			try {
				const { data } = await axios.post(url, {
					value: stringValue,
				})
				this.handleResponse({
					status: data.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update server side encryption config'),
					error: e,
				})
			}
		},
		async handleResponse({ status, errorMessage, error }) {
			if (status !== 'ok') {
				showError(errorMessage)
				logger.error(errorMessage, { error })
			}
		},
	},
}
</script>

<style lang="scss" scopped>
.topMargin {
	margin-top: 2rem;
}
.form-control {
	label {
		display: block;
		padding: 4px 0;
	}
}
</style>
