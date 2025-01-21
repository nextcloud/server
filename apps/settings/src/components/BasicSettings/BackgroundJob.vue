<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection :name="t('settings', 'Background jobs')"
		:description="t('settings', 'For the server to work properly, it\'s important to configure background jobs correctly. Cron is the recommended setting. Please see the documentation for more information.')"
		:doc-url="backgroundJobsDocUrl">
		<template v-if="lastCron !== 0">
			<NcNoteCard v-if="oldExecution" type="error">
				{{ t('settings', 'Last job execution ran {time}. Something seems wrong.', {time: relativeTime}) }}
			</NcNoteCard>

			<NcNoteCard v-else-if="longExecutionCron" type="warning">
				{{ t('settings', "Some jobs have not been executed since {maxAgeRelativeTime}. Please consider increasing the execution frequency.", {maxAgeRelativeTime}) }}
			</NcNoteCard>

			<NcNoteCard v-else-if="longExecutionNotCron" type="warning">
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

		<NcCheckboxRadioSwitch type="radio"
			:disabled="!cliBasedCronPossible"
			:checked.sync="backgroundJobsMode"
			value="cron"
			name="backgroundJobsMode"
			@update:checked="onBackgroundJobModeChanged">
			{{ t('settings', 'Cron (Recommended)') }}
		</NcCheckboxRadioSwitch>
		<!-- eslint-disable-next-line vue/no-v-html The translation is sanitized-->
		<em v-html="cronLabel" />
	</NcSettingsSection>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'
import { generateOcsUrl } from '@nextcloud/router'
import { confirmPassword } from '@nextcloud/password-confirmation'
import axios from '@nextcloud/axios'
import moment from '@nextcloud/moment'

import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

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
			let desc = t('settings', 'Use system cron service to call the cron.php file every 5 minutes.')
			if (this.cliBasedCronPossible) {
				desc += '<br>' + t('settings', 'The cron.php needs to be executed by the system account "{user}".', { user: this.cliBasedCronUser })
			} else {
				desc += '<br>' + t('settings', 'The PHP POSIX extension is required. See {linkstart}PHP documentation{linkend} for more details.', {
					linkstart: '<a target="_blank" rel="noreferrer nofollow" class="external" href="https://www.php.net/manual/en/book.posix.php">',
					linkend: '</a>',
				}, undefined, { escape: false })
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
			return Date.now() / 1000 - this.cronMaxAge > 24 * 3600 && this.backgroundJobsMode === 'cron'
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
	color: var(--color-primary-element-text);
	background-color: var(--color-error);
	width: initial;
}

.warning {
	margin-top: 8px;
	padding: 5px;
	border-radius: var(--border-radius);
	color: var(--color-primary-element-text);
	background-color: var(--color-warning);
	width: initial;
}

.ajaxSwitch {
	margin-top: 1rem;
}
</style>
