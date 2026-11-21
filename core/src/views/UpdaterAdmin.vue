<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import {
	mdiAlertCircleOutline,
	mdiCheckCircleOutline,
	mdiChevronDown,
	mdiChevronUp,
	mdiCloseCircleOutline,
	mdiInformationOutline,
} from '@mdi/js'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcGuestContent from '@nextcloud/vue/components/NcGuestContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import OCEventSource from '../OC/eventsource.js'

const updateInfo = loadState<{
	appsToUpgrade: { id: string, name: string, version: string, oldVersion: string }[]
	incompatibleAppsList: { id: string, name: string }[]
	isAppsOnlyUpgrade: boolean
	oldTheme: string | null
	productName: string
	version: string
}>('core', 'updateInfo')

const isShowingDetails = ref(false)
const isUpdateRunning = ref(false)
const isUpdateDone = ref(false)
const isOutcomeUnknown = ref(false)

const messages = ref<{ message: string, type: string }[]>([])
const wasSuccessfull = computed(() => messages.value.every((msg) => msg.type === 'success' || msg.type === 'notice'))
const hasErrors = computed(() => messages.value.some((msg) => msg.type === 'error' || msg.type === 'failure'))
const resultIcon = computed(() => wasSuccessfull.value ? mdiCheckCircleOutline : (hasErrors.value ? mdiCloseCircleOutline : mdiAlertCircleOutline))

const statusMessage = computed(() => {
	if (isOutcomeUnknown.value) {
		return t('core', 'The connection was lost and the update status is unknown. The update may still be running. Check the server logs and process status before reloading or retrying.')
	}
	if (isUpdateDone.value) {
		if (hasErrors.value) {
			return t('core', 'The update ended with errors. Please check the details for more information.')
		} else if (!wasSuccessfull.value) {
			return t('core', 'The update completed with warnings. Please check the details for more information.')
		} else {
			return t('core', 'The update completed successfully.')
		}
	}
	return messages.value.at(-1)?.message || t('core', 'Preparing update…')
})

const redirectCountdown = ref(6)
const redirectMessage = computed(() => {
	if (!isUpdateDone.value || !wasSuccessfull.value) {
		return ''
	}

	return t('core', 'You will be redirected to {productName} in {count} seconds.', { productName: updateInfo.productName, count: redirectCountdown.value })
})

onMounted(() => window.addEventListener('beforeunload', onUnload))
onUnmounted(() => window.removeEventListener('beforeunload', onUnload))

/**
 * Get the status icon for a given severity
 *
 * @param type - The severity
 */
function getSeverityIcon(type: string) {
	switch (type) {
		case 'success':
			return mdiCheckCircleOutline
		case 'notice':
			return mdiInformationOutline
		case 'warning':
			return mdiAlertCircleOutline
		case 'error':
		case 'failure':
			return mdiCloseCircleOutline
		default:
			return mdiInformationOutline
	}
}

/**
 * Start the update process
 */
async function onStartUpdate() {
	if (isUpdateRunning.value || isUpdateDone.value || isOutcomeUnknown.value) {
		return
	}

	isUpdateRunning.value = true
	const eventSource = new OCEventSource(generateOcsUrl('/core/update'))
	eventSource.listen('success', (message) => {
		messages.value.push({ message, type: 'success' })
	})
	eventSource.listen('notice', (message) => {
		messages.value.push({ message, type: 'notice' })
	})
	eventSource.listen('warning', (message) => {
		messages.value.push({ message, type: 'warning' })
	})
	eventSource.source.addEventListener('error', (event) => {
		if (event instanceof MessageEvent) {
			messages.value.push({ message: JSON.parse(event.data), type: 'error' })
			return
		}
		eventSource.close()
		isUpdateRunning.value = false
		isOutcomeUnknown.value = true
		isShowingDetails.value = true
	})
	eventSource.listen('failure', (message) => {
		messages.value.push({ message, type: 'failure' })
	})
	eventSource.listen('__internal__', (message) => {
		if (message === 'close' && !isUpdateDone.value && !isOutcomeUnknown.value) {
			isUpdateRunning.value = false
			if (hasErrors.value) {
				isUpdateDone.value = true
			} else {
				isOutcomeUnknown.value = true
			}
			isShowingDetails.value = true
		}
	})
	eventSource.listen('done', () => {
		isUpdateRunning.value = false
		isUpdateDone.value = true
		eventSource.close()
		if (!wasSuccessfull.value) {
			isShowingDetails.value = true
		}
		updateCountdown()
	})
}

/**
 * Update the countdown for the redirect
 */
function updateCountdown() {
	if (hasErrors.value || !wasSuccessfull.value) {
		return
	}

	if (--redirectCountdown.value > 0) {
		window.setTimeout(updateCountdown, 1000)
	} else {
		reloadPage()
	}
}

/**
 * Handle the beforeunload event to warn the user if an update is running.
 *
 * @param event - The beforeunload event object.
 */
function onUnload(event: BeforeUnloadEvent) {
	if (isUpdateRunning.value || isOutcomeUnknown.value) {
		event.preventDefault()
		event.returnValue = t('core', 'The update is in progress, leaving this page might interrupt the process in some environments.')
	}
}

/**
 * Reload the page
 */
function reloadPage() {
	window.location.reload()
}
</script>

<template>
	<NcGuestContent>
		<h2>
			{{ updateInfo.isAppsOnlyUpgrade
				? t('core', 'App update required')
				: t('core', '{productName} will be updated to version {version}', { productName: updateInfo.productName, version: updateInfo.version }) }}
		</h2>

		<NcNoteCard v-if="!!updateInfo.oldTheme" type="info">
			{{ t('core', 'The theme {oldTheme} has been disabled.', { oldTheme: updateInfo.oldTheme }) }}
		</NcNoteCard>

		<NcNoteCard v-if="updateInfo.incompatibleAppsList.length" type="warning">
			{{ t('core', 'The current versions of these apps are incompatible with the new server version and may be disabled during the upgrade:') }}
			<ul :aria-label="t('core', 'Incompatible apps')" :class="$style.updater__appsList">
				<li v-for="app of updateInfo.incompatibleAppsList" :key="'app-disable-' + app.id">
					{{ app.name }} ({{ app.id }})
				</li>
			</ul>
			{{ t('core', 'Compatible updates will be checked later in the upgrade. Apps that cannot be made compatible may remain disabled.') }}
		</NcNoteCard>

		<NcNoteCard v-if="updateInfo.appsToUpgrade.length" type="info">
			{{ t('core', 'These apps will be updated:') }}
			<ul :aria-label="t('core', 'Apps to update')" :class="$style.updater__appsList">
				<li v-for="app of updateInfo.appsToUpgrade" :key="'app-update-' + app.id">
					{{ t('core', '{app} from {oldVersion} to {version}', { app: `${app.name} (${app.id})`, oldVersion: app.oldVersion, version: app.version }) }}
				</li>
			</ul>
		</NcNoteCard>

		<p>
			<strong>{{ t('core', 'Please make sure that the database, the config folder and the data folder have been backed up before proceeding.') }}</strong>
			<br>
			{{ t('core', 'To avoid timeouts with larger installations, you can instead run the following command from your installation directory:') }}
			<pre>./occ upgrade</pre>
		</p>

		<NcButton
			v-if="!isUpdateRunning && !isUpdateDone && !isOutcomeUnknown"
			:class="$style.updater__updateButton"
			variant="primary"
			@click="onStartUpdate">
			{{ t('core', 'Start update') }}
		</NcButton>
		<NcButton
			v-else
			:class="$style.updater__updateButton"
			:disabled="isUpdateRunning || isOutcomeUnknown"
			variant="primary"
			@click="reloadPage">
			{{ t('core', 'Continue to {productName}', { productName: updateInfo.productName }) }}
		</NcButton>

		<div v-if="isUpdateRunning || isUpdateDone || isOutcomeUnknown">
			<h2>{{ t('core', 'Update to {version}', { version: updateInfo.version }) }}</h2>

			<NcLoadingIcon v-if="isUpdateRunning" />
			<NcIconSvgWrapper
				v-else
				:path="isOutcomeUnknown ? mdiAlertCircleOutline : resultIcon"
				:class="{
					[$style.updater__messageIcon_success]: !isOutcomeUnknown && wasSuccessfull,
					[$style.updater__messageIcon_error]: !isOutcomeUnknown && hasErrors && !wasSuccessfull,
					[$style.updater__messageIcon_warning]: isOutcomeUnknown || (!hasErrors && !wasSuccessfull),
				}" />
			<div aria-live="polite">
				<em>{{ statusMessage }}</em><br>
				<span v-if="redirectMessage">{{ redirectMessage }}</span>
			</div>

			<NcButton
				aria-controls="core-update-details"
				:aria-expanded="isShowingDetails"
				variant="tertiary"
				@click="isShowingDetails = !isShowingDetails">
				<template #icon>
					<NcIconSvgWrapper
						:path="isShowingDetails ? mdiChevronUp : mdiChevronDown" />
				</template>
				{{ isShowingDetails ? t('core', 'Hide details') : t('core', 'Show details') }}
			</NcButton>
			<Transition
				:enterActiveClass="$style.updater__transition_active"
				:leaveActiveClass="$style.updater__transition_active"
				:leaveToClass="$style.updater__transition_collapsed"
				:enterFromClass="$style.updater__transition_collapsed">
				<ul
					v-show="isShowingDetails"
					id="core-update-details"
					:aria-label="t('core', 'Update details')"
					:class="$style.updater__messageList">
					<li
						v-for="{ message, type } of messages"
						:key="message"
						:class="$style.updater__message">
						<NcIconSvgWrapper
							:class="{
								[$style.updater__messageIcon_error]: type === 'error' || type === 'failure',
								[$style.updater__messageIcon_info]: type === 'notice',
								[$style.updater__messageIcon_success]: type === 'success',
								[$style.updater__messageIcon_warning]: type === 'warning',
							}"
							:path="getSeverityIcon(type)" />
						<span :class="$style.updater__messageText">{{ message }}</span>
					</li>
				</ul>
			</Transition>
		</div>
	</NcGuestContent>
</template>

<style module>
.updater__appsList {
	list-style-type: disc;
	margin-inline-start: var(--default-clickable-area);
}

.updater__updateButton {
	margin-inline: auto;
	margin-block: 1rem;
}

.updater__messageList {
	max-height: 50vh;
	overflow: visible scroll;
	padding-inline-start: var(--default-grid-baseline);
}

.updater__message {
	display: flex;
	align-items: center;
	justify-content: start;
	gap: var(--default-grid-baseline);
}

.updater__messageText {
	text-align: start;
}

.updater__messageIcon_success {
	color: var(--color-element-success);
}

.updater__messageIcon_info {
	color: var(--color-element-info);
}

.updater__messageIcon_error {
	color: var(--color-element-error);
}

.updater__messageIcon_warning {
	color: var(--color-element-warning);
}

.updater__transition_active {
	transition: all var(--animation-slow);
}

.updater__transition_collapsed {
	opacity: 0;
	max-height: 0px;
}
</style>
