<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { QuotaOption } from '../../../../components/Users/userFormUtils.ts'

import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { parseFileSize } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { useHotKey } from '@nextcloud/vue/composables/useHotKey'
import { computed, nextTick, ref } from 'vue'
import NcAppSettingsDialog from '@nextcloud/vue/components/NcAppSettingsDialog'
import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'
import NcAppSettingsShortcutsSection from '@nextcloud/vue/components/NcAppSettingsShortcutsSection'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcHotkey from '@nextcloud/vue/components/NcHotkey'
import NcHotkeyList from '@nextcloud/vue/components/NcHotkeyList'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { validateQuota } from '../../../../components/Users/userFormUtils.ts'
import { GroupSorting } from '../../../../constants/GroupManagement.ts'
import logger from '../../../../logger.ts'
import { unlimitedQuota } from '../../../../utils/userUtils.ts'
import { useUsersStore } from '../../store/users.ts'

type ColumnKey = 'showLanguages' | 'showFirstLogin' | 'showLastLogin' | 'showUserBackend' | 'showStoragePath'

/** Whether the dialog is open */
const open = defineModel<boolean>('open', { required: true })

const usersStore = useUsersStore()

// ? opens the settings dialog on the keyboard shortcuts section
useHotKey('?', async () => {
	open.value = true
	await nextTick()
	document.getElementById('settings-section_keyboard-shortcuts')?.scrollIntoView({
		behavior: 'smooth',
		inline: 'nearest',
	})
}, { stop: true, prevent: true })

const loadingSendMail = ref(false)

const settings = computed(() => usersStore.serverData)

const groupSorting = computed({
	get: () => usersStore.orderBy === GroupSorting.GroupName ? 'name' : 'member-count',
	set: (sorting: string) => usersStore.setGroupSorting(sorting === 'name' ? GroupSorting.GroupName : GroupSorting.UserCount),
})

/** Admin has configured `sort_groups_by_name` in the system config */
const isGroupSortingEnforced = computed(() => settings.value.forceSortGroupByName)

/**
 * @param key The column to show or hide
 */
function columnModel(key: ColumnKey) {
	return computed({
		get: () => usersStore.showConfig[key],
		set: (value: boolean) => usersStore.setShowConfig({ key, value }),
	})
}

const showLanguages = columnModel('showLanguages')
const showFirstLogin = columnModel('showFirstLogin')
const showLastLogin = columnModel('showLastLogin')
const showUserBackend = columnModel('showUserBackend')
const showStoragePath = columnModel('showStoragePath')

const quotaOptions = computed(() => {
	// convert the preset array into objects
	const quotaPreset: QuotaOption[] = settings.value.quotaPreset.map((quota: string) => ({ id: quota, label: quota }))
	// add default presets
	if (settings.value.allowUnlimitedQuota) {
		quotaPreset.unshift(unlimitedQuota)
	}
	return quotaPreset
})

/**
 * The default quota stored on the server
 */
function serverDefaultQuota(): QuotaOption {
	const quota = settings.value.defaultQuota
	const size = parseFileSize(quota, true)
	if (quota !== unlimitedQuota.id && size !== null && size >= 0) {
		// if value is valid, let's map the quotaOptions or return custom quota
		return { id: quota, label: quota }
	}
	return unlimitedQuota
}

const defaultQuota = ref<QuotaOption>(serverDefaultQuota())
let savedDefaultQuota = defaultQuota.value

const sendWelcomeMail = computed({
	get: () => settings.value.newUserSendEmail,
	set: (value: boolean) => setSendWelcomeMail(value),
})

/**
 * @param value Whether new accounts get a welcome email
 */
async function setSendWelcomeMail(value: boolean) {
	loadingSendMail.value = true
	usersStore.serverData = {
		...settings.value,
		newUserSendEmail: value,
	}
	try {
		await axios.post(generateUrl('/settings/users/preferences/newUser.sendEmail'), { value: value ? 'yes' : 'no' })
	} catch (error) {
		logger.error('Could not update newUser.sendEmail preference', { error })
		usersStore.serverData = {
			...settings.value,
			newUserSendEmail: !value,
		}
		showError(t('settings', 'Could not update the welcome email setting'))
	} finally {
		loadingSendMail.value = false
	}
}

/**
 * Check if a quota matches the current search.
 * This is a custom filter function to allow to map "1GB" to the label "1 GB" (ignoring whitespaces).
 *
 * @param option The quota to check
 * @param label The label of the quota
 * @param search The search string
 */
function filterQuotas(option: QuotaOption, label: string, search: string) {
	const searchValue = search.toLocaleLowerCase().replaceAll(/\s/g, '')
	return (label || '')
		.toLocaleLowerCase()
		.replaceAll(/\s/g, '')
		.indexOf(searchValue) > -1
}

/**
 * Save the picked default quota, or go back to the saved one if that fails
 *
 * @param option The picked quota
 */
async function setDefaultQuota(option: QuotaOption) {
	// the store shows the error and resolves without a response if saving fails
	const response = await usersStore.setAppConfig({
		app: 'files',
		key: 'default_quota',
		value: option.id,
	})
	if (response) {
		savedDefaultQuota = option
		usersStore.serverData = { ...settings.value, defaultQuota: option.id }
	}
	defaultQuota.value = savedDefaultQuota
}
</script>

<template>
	<NcAppSettingsDialog
		v-model:open="open"
		showNavigation
		:name="t('settings', 'Account management settings')">
		<NcAppSettingsSection
			id="visibility-settings"
			:name="t('settings', 'Visibility')">
			<NcCheckboxRadioSwitch
				v-model="showLanguages"
				type="switch"
				data-test="showLanguages">
				{{ t('settings', 'Show language') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch
				v-model="showUserBackend"
				type="switch"
				data-test="showUserBackend">
				{{ t('settings', 'Show account backend') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch
				v-model="showStoragePath"
				type="switch"
				data-test="showStoragePath">
				{{ t('settings', 'Show storage path') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch
				v-model="showFirstLogin"
				type="switch"
				data-test="showFirstLogin">
				{{ t('settings', 'Show first login') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch
				v-model="showLastLogin"
				type="switch"
				data-test="showLastLogin">
				{{ t('settings', 'Show last login') }}
			</NcCheckboxRadioSwitch>
		</NcAppSettingsSection>

		<NcAppSettingsSection
			id="groups-sorting"
			:name="t('settings', 'Sorting')">
			<NcNoteCard v-if="isGroupSortingEnforced" type="warning">
				{{ t('settings', 'The system config enforces sorting the groups by name. This also disables showing the member count.') }}
			</NcNoteCard>
			<fieldset :class="$style.fieldset">
				<legend>{{ t('settings', 'Group list sorting') }}</legend>
				<NcNoteCard
					:class="$style.note"
					type="info"
					:text="t('settings', 'Sorting only applies to the currently loaded groups for performance reasons. Groups will be loaded as you navigate or search through the list.')" />
				<NcCheckboxRadioSwitch
					v-model="groupSorting"
					type="radio"
					data-test="sortGroupsByMemberCount"
					:disabled="isGroupSortingEnforced"
					name="group-sorting-mode"
					value="member-count">
					{{ t('settings', 'By member count') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch
					v-model="groupSorting"
					type="radio"
					data-test="sortGroupsByName"
					:disabled="isGroupSortingEnforced"
					name="group-sorting-mode"
					value="name">
					{{ t('settings', 'By name') }}
				</NcCheckboxRadioSwitch>
			</fieldset>
		</NcAppSettingsSection>

		<NcAppSettingsSection
			id="email-settings"
			:name="t('settings', 'Send email')">
			<NcCheckboxRadioSwitch
				v-model="sendWelcomeMail"
				type="switch"
				data-test="sendWelcomeMail"
				:disabled="loadingSendMail">
				{{ t('settings', 'Send welcome email to new accounts') }}
			</NcCheckboxRadioSwitch>
		</NcAppSettingsSection>

		<NcAppSettingsSection
			id="default-settings"
			:name="t('settings', 'Defaults')">
			<NcSelect
				v-model="defaultQuota"
				:clearable="false"
				:createOption="(quota: string) => validateQuota(quota, unlimitedQuota)"
				:filterBy="filterQuotas"
				:inputLabel="t('settings', 'Default quota')"
				:options="quotaOptions"
				placement="top"
				:placeholder="t('settings', 'Select default quota')"
				taggable
				@option:selected="setDefaultQuota" />
		</NcAppSettingsSection>

		<NcAppSettingsShortcutsSection>
			<NcHotkeyList :label="t('settings', 'Search')">
				<NcHotkey :label="t('settings', 'Focus search')" hotkey="Control F" />
			</NcHotkeyList>
			<NcHotkeyList :label="t('settings', 'Help')">
				<NcHotkey :label="t('settings', 'Show those shortcuts')" hotkey="?" />
			</NcHotkeyList>
		</NcAppSettingsShortcutsSection>
	</NcAppSettingsDialog>
</template>

<style module>
.fieldset {
	font-weight: bold;
}

.note {
	font-weight: normal;
}
</style>
