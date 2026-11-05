<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { formatRelativeTime, t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'
import { computed, ref } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import logger from '../../logger.ts'

const lastCron = loadState<number>('settings', 'lastCron')
const cronMaxAge = loadState<number>('settings', 'cronMaxAge', 0)
const cliBasedCronPossible = loadState('settings', 'cliBasedCronPossible', true)
const cliBasedCronUser = loadState('settings', 'cliBasedCronUser', 'www-data')
const backgroundJobsDocUrl = loadState<string>('settings', 'backgroundJobsDocUrl')

const backgroundJobsMode = ref(loadState('settings', 'backgroundJobsMode', 'cron'))
let savedMode = backgroundJobsMode.value
const relativeTime = formatRelativeTime(lastCron * 1000)
const maxAgeRelativeTime = formatRelativeTime(cronMaxAge * 1000)

const cronLabel = t('settings', 'Use system cron service to call the cron.php file every 5 minutes.') + '<br>' + (cliBasedCronPossible
	? t('settings', 'The cron.php needs to be executed by the system account "{user}".', { user: cliBasedCronUser })
	: t('settings', 'The PHP POSIX extension is required. See {linkstart}PHP documentation{linkend} for more details.', {
			linkstart: '<a target="_blank" rel="noreferrer nofollow" class="external" href="https://www.php.net/manual/en/book.posix.php">',
			linkend: '</a>',
		}, undefined, { escape: false }))

const oldExecution = Date.now() / 1000 - lastCron > 600
const longExecutionNotCron = computed(() => Date.now() / 1000 - cronMaxAge > 12 * 3600 && backgroundJobsMode.value !== 'cron')
const longExecutionCron = computed(() => Date.now() / 1000 - cronMaxAge > 24 * 3600 && backgroundJobsMode.value === 'cron')

/**
 * @param key Core app config key
 */
function coreConfigUrl(key: string) {
	return generateOcsUrl('/apps/provisioning_api/api/v1/config/apps/{appId}/{key}', { appId: 'core', key })
}

/**
 * @param mode The background jobs mode picked by the admin
 */
async function onBackgroundJobModeChanged(mode: string) {
	const url = coreConfigUrl('backgroundjobs_mode')

	try {
		await confirmPassword()
		const { data } = await axios.post(url, {
			value: mode,
		})
		if (data.ocs?.meta?.status === 'ok') {
			savedMode = mode
			await deleteError()
			return
		}
	} catch (error) {
		logger.error('Unable to update background job mode', { error })
	}
	backgroundJobsMode.value = savedMode
	showError(t('settings', 'Unable to update background job mode'))
}

/**
 * Clear the cron errors, they belong to the previous mode
 */
async function deleteError() {
	// clear cron errors on background job mode change
	const url = coreConfigUrl('cronErrors')

	try {
		await confirmPassword()
		await axios.delete(url)
	} catch (error) {
		logger.error(error)
	}
}
</script>

<template>
	<NcSettingsSection
		:name="t('settings', 'Background jobs')"
		:description="t('settings', 'For the server to work properly, it\'s important to configure background jobs correctly. Cron is the recommended setting. Please see the documentation for more information.')"
		:docUrl="backgroundJobsDocUrl">
		<template v-if="lastCron !== 0">
			<NcNoteCard v-if="oldExecution" type="error">
				{{ t('settings', 'Last job execution ran {time}. Something seems wrong.', { time: relativeTime }) }}
			</NcNoteCard>

			<NcNoteCard v-else-if="longExecutionCron" type="warning">
				{{ t('settings', "Some jobs have not been executed since {maxAgeRelativeTime}. Please consider increasing the execution frequency.", { maxAgeRelativeTime }) }}
			</NcNoteCard>

			<NcNoteCard v-else-if="longExecutionNotCron" type="warning">
				{{ t('settings', "Some jobs have not been executed since {maxAgeRelativeTime}. Please consider switching to system cron.", { maxAgeRelativeTime }) }}
			</NcNoteCard>

			<NcNoteCard v-else type="success">
				{{ t('settings', 'Last job ran {relativeTime}.', { relativeTime }) }}
			</NcNoteCard>
		</template>

		<NcNoteCard v-else type="error">
			{{ t('settings', 'Background job did not run yet!') }}
		</NcNoteCard>

		<NcCheckboxRadioSwitch
			v-model="backgroundJobsMode"
			type="radio"
			name="backgroundJobsMode"
			value="ajax"
			:class="$style.ajaxSwitch"
			@update:modelValue="onBackgroundJobModeChanged">
			{{ t('settings', 'AJAX') }}
		</NcCheckboxRadioSwitch>
		<em>{{ t('settings', 'Execute one task with each page loaded. Use case: Single account instance.') }}</em>

		<NcCheckboxRadioSwitch
			v-model="backgroundJobsMode"
			type="radio"
			name="backgroundJobsMode"
			value="webcron"
			@update:modelValue="onBackgroundJobModeChanged">
			{{ t('settings', 'Webcron') }}
		</NcCheckboxRadioSwitch>
		<em>{{ t('settings', 'cron.php is registered at a webcron service to call cron.php every 5 minutes over HTTP. Use case: Very small instance (1–5 accounts depending on the usage).') }}</em>

		<NcCheckboxRadioSwitch
			v-model="backgroundJobsMode"
			type="radio"
			:disabled="!cliBasedCronPossible"
			value="cron"
			name="backgroundJobsMode"
			@update:modelValue="onBackgroundJobModeChanged">
			{{ t('settings', 'Cron (Recommended)') }}
		</NcCheckboxRadioSwitch>
		<!-- eslint-disable-next-line vue/no-v-html The translation is sanitized-->
		<em v-html="cronLabel" />
	</NcSettingsSection>
</template>

<style module>
.ajaxSwitch {
	margin-top: 1rem;
}
</style>
