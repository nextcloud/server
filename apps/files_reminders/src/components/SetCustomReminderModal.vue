<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { INode } from '@nextcloud/files'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { emit as emitEventBus } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import { onBeforeMount, onMounted, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { clearReminder, setReminder } from '../services/reminderService.ts'
import { logger } from '../shared/logger.ts'
import { getInitialCustomDueDate } from '../shared/utils.ts'

const props = defineProps<{
	node: INode
}>()

const emit = defineEmits<{
	close: [void]
}>()

const hasDueDate = ref(false)
const opened = ref(false)
const isValid = ref(true)
const customDueDate = ref<Date>()
const nowDate = ref(new Date())

onBeforeMount(() => {
	const dueDate = props.node.attributes['reminder-due-date']
		? new Date(props.node.attributes['reminder-due-date'])
		: undefined

	hasDueDate.value = Boolean(dueDate)
	isValid.value = true
	opened.value = true
	customDueDate.value = dueDate ?? getInitialCustomDueDate()
	nowDate.value = new Date()
})

onMounted(() => {
	const input = document.getElementById('set-custom-reminder') as HTMLInputElement
	input.focus()
	if (!hasDueDate.value) {
		input.showPicker()
	}
})

/**
 * Set the custom reminder
 */
async function setCustom(): Promise<void> {
	// Handle input cleared or invalid date
	if (!(customDueDate.value instanceof Date) || isNaN(customDueDate.value.getTime())) {
		showError(t('files_reminders', 'Please choose a valid date & time'))
		return
	}

	try {
		await setReminder(props.node.fileid!, customDueDate.value)
		const node = props.node.clone()
		node.attributes['reminder-due-date'] = customDueDate.value.toISOString()
		emitEventBus('files:node:updated', node)
		showSuccess(t('files_reminders', 'Reminder set for "{fileName}"', { fileName: props.node.displayname }))
		onClose()
	} catch (error) {
		logger.error('Failed to set reminder', { error })
		showError(t('files_reminders', 'Failed to set reminder'))
	}
}

/**
 * Clear the reminder
 */
async function clear(): Promise<void> {
	try {
		await clearReminder(props.node.fileid!)
		const node = props.node.clone()
		node.attributes['reminder-due-date'] = ''
		emitEventBus('files:node:updated', node)
		showSuccess(t('files_reminders', 'Reminder cleared for "{fileName}"', { fileName: props.node.displayname }))
		onClose()
	} catch (error) {
		logger.error('Failed to clear reminder', { error })
		showError(t('files_reminders', 'Failed to clear reminder'))
	}
}

/**
 * Close the modal
 */
function onClose(): void {
	opened.value = false
	emit('close')
}

/**
 * Validate the input on change
 */
function onInput(): void {
	const input = document.getElementById('set-custom-reminder') as HTMLInputElement
	isValid.value = input.checkValidity()
}
</script>

<template>
	<NcDialog
		v-if="opened"
		:name="t('files_reminders', `Set reminder for '{fileName}'`, { fileName: node.displayname })"
		out-transition
		size="small"
		close-on-click-outside
		@closing="onClose">
		<form
			id="set-custom-reminder-form"
			class="custom-reminder-modal"
			@submit.prevent="setCustom">
			<NcDateTimePickerNative
				id="set-custom-reminder"
				v-model="customDueDate"
				:label="t('files_reminders', 'Reminder at custom date & time')"
				:min="nowDate"
				:required="true"
				type="datetime-local"
				@input="onInput" />

			<NcNoteCard v-if="isValid && customDueDate" type="info">
				{{ t('files_reminders', 'We will remind you of this file') }}
				<NcDateTime :timestamp="customDueDate" />
			</NcNoteCard>

			<NcNoteCard v-else type="error">
				{{ t('files_reminders', 'Please choose a valid date & time') }}
			</NcNoteCard>
		</form>
		<template #actions>
			<!-- Cancel pick -->
			<NcButton variant="tertiary" @click="onClose">
				{{ t('files_reminders', 'Cancel') }}
			</NcButton>

			<!-- Clear reminder -->
			<NcButton v-if="hasDueDate" @click="clear">
				{{ t('files_reminders', 'Clear reminder') }}
			</NcButton>

			<!-- Set reminder -->
			<NcButton
				:disabled="!isValid"
				variant="primary"
				form="set-custom-reminder-form"
				type="submit">
				{{ t('files_reminders', 'Set reminder') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<style lang="scss" scoped>
.custom-reminder-modal {
	margin: 0 12px;
}
</style>
