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
	<NcSettingsSection :title="t('settings', 'Background jobs')"
		:description="t('settings', 'For the server to work properly, it\'s important to configure background jobs correctly. Cron is the recommended setting. Please see the documentation for more information.')"
		:doc-url="backgroundJobsDocUrl">
		<template v-if="lastCron !== 0">
			<NcNoteCard v-if="oldExecution" type="error">
				{{ t('settings', 'Last job execution ran {time}. Something seems wrong.', {time: relativeTime}) }}
			</NcNoteCard>

			<NcNoteCard v-else-if="longExecutionNotCron" type="warning">
				{{ t('settings', "Some jobs have not been executed since {maxAgeRelativeTime}. Please consider increasing the execution frequency.", {maxAgeRelativeTime}) }}
			</NcNoteCard>

			<NcNoteCard v-else-if="longExecutionCron" type="warning">
				{{ t('settings', "Some jobs have not been executed since {maxAgeRelativeTime}. Please consider switching to system cron.", {maxAgeRelativeTime}) }}
			</NcNoteCard>

			<NcNoteCard v-else type="success">
				{{ t('settings', 'Last job ran {relativeTime}.', {relativeTime}) }}
			</NcNoteCard>
		</template>

		<NcNoteCard v-else type="error">
			{{ t('settings', 'Background job did not run yet!') }}
		</NcNoteCard>

		<NcCheckboxRadioSwitch type="radio"
			:checked.sync="backgroundJobsMode"
			name="backgroundJobsMode"
			value="ajax"
			class="ajaxSwitch"
			@update:checked="onBackgroundJobModeChanged">
			{{ t('settings', 'AJAX') }}
		</NcCheckboxRadioSwitch>
		<em>{{ t('settings', 'Execute one task with each page loaded. Use case: Single account instance.') }}</em>

		<NcCheckboxRadioSwitch type="radio"
			:checked.sync="backgroundJobsMode"
			name="backgroundJobsMode"
			value="webcron"
			@update:checked="onBackgroundJobModeChanged">
			{{ t('settings', 'Webcron') }}
		</NcCheckboxRadioSwitch>
		<em>{{ t('settings', 'cron.php is registered at a webcron service to call cron.php every 5 minutes over HTTP. Use case: Very small instance (1–5 accounts depending on the usage).') }}</em>

		<NcCheckboxRadioSwitch v-if="cliBasedCronPossible"
			type="radio"
			:checked.sync="backgroundJobsMode"
			value="cron"
			name="backgroundJobsMode"
			@update:checked="onBackgroundJobModeChanged">
			{{ t('settings', 'Cron (Recommended)') }}
		</NcCheckboxRadioSwitch>
		<em v-if="cliBasedCronPossible">{{ cronLabel }}</em>
		<em v-else>
			{{ t('settings', 'To run this you need the PHP POSIX extension. See {linkstart}PHP documentation{linkend} for more details.', {
				linkstart: '<a href="https://www.php.net/manual/en/book.posix.php">',
				linkend: '</a>',
			}) }}
		</em>
	</NcSettingsSection>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import moment from '@nextcloud/moment'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { confirmPassword } from '@nextcloud/password-confirmation'
import '@nextcloud/password-confirmation/dist/style.css'

const lastCron = loadState('settings', 'lastCron')
const cronMaxAge = loadState('settings', 'cronMaxAge', '')
const backgroundJobsMode = loadState('settings', 'backgroundJobsMode', 'cron')
const cliBasedCronPossible = loadState('settings', 'cliBasedCronPossible', true)
const cliBasedCronUser = loadState('settings', 'cliBasedCronUser', 'www-data')
const backgroundJobsDocUrl = loadState('settings', 'backgroundJobsDocUrl')

export default {
	name: 'BackgroundJob',

	components: {
		NcCheckboxRadioSwitch,
		NcSettingsSection,
		NcNoteCard,
	},

	data() {
		return {
			lastCron,
			cronMaxAge,
			backgroundJobsMode,
			cliBasedCronPossible,
			cliBasedCronUser,
			backgroundJobsDocUrl,
			relativeTime: moment(lastCron * 1000).fromNow(),
			maxAgeRelativeTime: moment(cronMaxAge * 1000).fromNow(),
		}
	},
	computed: {
		cronLabel() {
			let desc = t('settings', 'Use system cron service to call the cron.php file every 5 minutes. Recommended for all instances.')
			if (this.cliBasedCronPossible) {
				desc += ' ' + t('settings', 'The cron.php needs to be executed by the system account "{user}".', { user: this.cliBasedCronUser })
			}
			return desc
		},
		oldExecution() {
			return Date.now() / 1000 - this.lastCron > 600
		},
		longExecutionNotCron() {
			return Date.now() / 1000 - this.cronMaxAge > 12 * 3600 && this.backgroundJobsMode !== 'cron'
		},
		longExecutionCron() {
			return Date.now() / 1000 - this.cronMaxAge > 12 * 3600 && this.backgroundJobsMode === 'cron'
		},
	},
	methods: {
		async onBackgroundJobModeChanged(backgroundJobsMode) {
			const url = generateOcsUrl('/apps/provisioning_api/api/v1/config/apps/{appId}/{key}', {
				appId: 'core',
				key: 'backgroundjobs_mode',
			})

			await confirmPassword()

			try {
				const { data } = await axios.post(url, {
					value: backgroundJobsMode,
				})
				this.handleResponse({
					status: data.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update background job mode'),
					error: e,
				})
			}
		},
		async handleResponse({ status, errorMessage, error }) {
			if (status === 'ok') {
				await this.deleteError()
			} else {
				showError(errorMessage)
				console.error(errorMessage, error)
			}
		},
		async deleteError() {
			// clear cron errors on background job mode change
			const url = generateOcsUrl('/apps/provisioning_api/api/v1/config/apps/{appId}/{key}', {
				appId: 'core',
				key: 'cronErrors',
			})

			await confirmPassword()

			try {
				await axios.delete(url)
			} catch (error) {
				console.error(error)
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.error {
	margin-top: 8px;
	padding: 5px;
	border-radius: var(--border-radius);
	color: var(--color-primary-text);
	background-color: var(--color-error);
	width: initial;
}
.warning {
	margin-top: 8px;
	padding: 5px;
	border-radius: var(--border-radius);
	color: var(--color-primary-text);
	background-color: var(--color-warning);
	width: initial;
}
.ajaxSwitch {
	margin-top: 1rem;
}
</style>
