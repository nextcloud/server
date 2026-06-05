<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<tr
		class="user-list__row"
		:data-cy-user-row="user.id">
		<td class="row__cell row__cell--avatar" data-cy-user-list-cell-avatar>
			<NcLoadingIcon
				v-if="isLoadingUser"
				:name="t('settings', 'Loading account …')"
				:size="32" />
			<NcAvatar
				v-else-if="visible"
				disableMenu
				hideStatus
				:user="user.id" />
		</td>

		<td class="row__cell row__cell--displayname" data-cy-user-list-cell-displayname>
			<strong
				v-if="!isObfuscated"
				:title="user.displayname?.length > 20 ? user.displayname : undefined">
				{{ user.displayname }}
			</strong>
		</td>

		<td class="row__cell row__cell--username" data-cy-user-list-cell-username>
			<span class="row__subtitle">{{ user.id }}</span>
		</td>

		<td class="row__cell" data-cy-user-list-cell-email>
			<span
				v-if="!isObfuscated"
				:title="user.email && user.email.length > 20 ? user.email : undefined">
				{{ user.email }}
			</span>
		</td>

		<td class="row__cell row__cell--groups row__cell--multiline" data-cy-user-list-cell-groups>
			<span
				v-if="!isObfuscated"
				:title="userGroupsLabels?.length > 40 ? userGroupsLabels : undefined">
				{{ userGroupsLabels }}
			</span>
		</td>

		<td
			v-if="settings.isAdmin || settings.isDelegatedAdmin"
			data-cy-user-list-cell-subadmins
			class="row__cell row__cell--large row__cell--multiline">
			<span
				v-if="!isObfuscated"
				:title="userSubAdminGroupsLabels?.length > 40 ? userSubAdminGroupsLabels : undefined">
				{{ userSubAdminGroupsLabels }}
			</span>
		</td>

		<td class="row__cell" data-cy-user-list-cell-quota>
			<template v-if="!isObfuscated">
				<span :id="'quota-progress' + uniqueId">{{ userQuota }} ({{ usedSpace }})</span>
				<NcProgressBar
					:aria-labelledby="'quota-progress' + uniqueId"
					class="row__progress"
					:class="{
						'row__progress--warn': usedQuota > 80,
					}"
					:value="usedQuota" />
			</template>
		</td>

		<td
			v-if="showConfig.showLanguages"
			class="row__cell row__cell--large"
			data-cy-user-list-cell-language>
			<span v-if="!isObfuscated">
				{{ userLanguage.name }}
			</span>
		</td>

		<td
			v-if="showConfig.showUserBackend || showConfig.showStoragePath"
			data-cy-user-list-cell-storage-location
			class="row__cell row__cell--large">
			<template v-if="!isObfuscated">
				<span v-if="showConfig.showUserBackend">{{ user.backend }}</span>
				<span
					v-if="showConfig.showStoragePath"
					:title="user.storageLocation"
					class="row__subtitle">
					{{ user.storageLocation }}
				</span>
			</template>
		</td>

		<td
			v-if="showConfig.showFirstLogin"
			class="row__cell"
			data-cy-user-list-cell-first-login>
			<span v-if="!isObfuscated">{{ userFirstLogin }}</span>
		</td>

		<td
			v-if="showConfig.showLastLogin"
			:title="userLastLoginTooltip"
			class="row__cell"
			data-cy-user-list-cell-last-login>
			<span v-if="!isObfuscated">{{ userLastLogin }}</span>
		</td>

		<td class="row__cell row__cell--large row__cell--fill" data-cy-user-list-cell-manager>
			<span v-if="!isObfuscated">
				{{ user.manager }}
			</span>
		</td>

		<td class="row__cell row__cell--actions" data-cy-user-list-cell-actions>
			<UserRowActions
				v-if="visible && !isObfuscated && canEdit && !loading.all"
				:actions="userActions"
				:disabled="isLoadingField"
				:user="user"
				@update:edit="toggleEdit" />
		</td>
	</tr>
</template>

<script setup lang="ts">
import type { IUser } from '../../views/user-types.d.ts'
import type { LanguageOption, QuotaOption } from './userFormUtils.ts'

import { getCurrentUser } from '@nextcloud/auth'
import { showSuccess } from '@nextcloud/dialogs'
import { formatFileSize, parseFileSize } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { useFormatTime } from '@nextcloud/vue'
import { computed, reactive } from 'vue'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcProgressBar from '@nextcloud/vue/components/NcProgressBar'
import UserRowActions from './UserRowActions.vue'
import { useStore } from '../../store/index.js'
import { isObfuscated as isObfuscatedUser } from '../../utils/userUtils.ts'

const props = withDefaults(defineProps<{
	user: IUser
	visible: boolean
	users: IUser[]
	quotaOptions: QuotaOption[]
	languages: { languages: LanguageOption[] }[]
	// settings is loose until the store is typed.
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	settings: Record<string, any>
	externalActions?: { icon: string, text: string, action: (...args: unknown[]) => void }[]
	onEditUser?: ((user: IUser) => void) | null
}>(), {
	externalActions: () => [],
	onEditUser: null,
})
const OC = window.OC
const productName = OC.theme.productName

const store = useStore()

const formattedFullTime = useFormatTime(props.user.firstLoginTimestamp * 1000, {
	format: {
		timeStyle: 'short',
		dateStyle: 'short',
	},
})

const rand = Math.random().toString(36).substring(2)
const loading = reactive({
	all: false,
	delete: false,
	disable: false,
	wipe: false,
})

const isObfuscated = computed(() => isObfuscatedUser(props.user))

const usedQuota = computed(() => {
	let quota = props.user.quota.quota
	if (quota > 0) {
		quota = Math.min(100, Math.round(props.user.quota.used / quota * 100))
	} else {
		const usedInGB = props.user.quota.used / (10 * Math.pow(2, 30))
		// asymptotic curve approaching 50% at 10GB to visualize used space with infinite quota
		quota = 95 * (1 - (1 / (usedInGB + 1)))
	}
	return isNaN(quota) ? 0 : quota
})

const userLanguage = computed(() => {
	const availableLanguages = props.languages[0].languages.concat(props.languages[1].languages)
	const userLang = availableLanguages.find((lang) => lang.code === props.user.language)
	if (userLang) {
		return userLang
	}
	// Unknown or unset language: fall back to the raw code (empty string renders nothing)
	return { code: props.user.language, name: props.user.language }
})

const userFirstLogin = computed(() => {
	if (props.user.firstLoginTimestamp > 0) {
		return formattedFullTime.value
	}
	if (props.user.firstLoginTimestamp < 0) {
		return t('settings', 'Unknown')
	}
	return t('settings', 'Never')
})

const userLastLoginTooltip = computed(() => {
	if (props.user.lastLoginTimestamp > 0) {
		return OC.Util.formatDate(props.user.lastLoginTimestamp * 1000)
	}
	return ''
})

const userLastLogin = computed(() => {
	if (props.user.lastLoginTimestamp > 0) {
		return OC.Util.relativeModifiedDate(props.user.lastLoginTimestamp * 1000)
	}
	return t('settings', 'Never')
})

const showConfig = computed(() => store.getters.getShowConfig)

const isLoadingUser = computed(() => loading.delete || loading.disable || loading.wipe)

const isLoadingField = computed(() => loading.delete || loading.disable || loading.all)

const uniqueId = computed(() => encodeURIComponent(props.user.id + rand))

const userGroupsLabels = computed(() => {
	const allGroups = store.getters.getGroups
	return props.user.groups
		.map((id) => {
			const group = allGroups.find((g) => g.id === id)
			return group?.name ?? id
		})
		.join(', ')
})

const userSubAdminGroupsLabels = computed(() => {
	const allGroups = store.getters.getGroups
	return (props.user.subadmin ?? [])
		.map((id) => {
			const group = allGroups.find((g) => g.id === id)
			return group?.name ?? id
		})
		.join(', ')
})

const usedSpace = computed(() => {
	if (props.user.quota?.used) {
		return t('settings', '{size} used', { size: formatFileSize(props.user.quota?.used) })
	}
	return t('settings', '{size} used', { size: formatFileSize(0) })
})

const canEdit = computed(() => getCurrentUser()?.uid !== props.user.id || props.settings.isAdmin || props.settings.isDelegatedAdmin)

const userQuota = computed(() => {
	let quota = props.user.quota?.quota

	if (quota === 'default') {
		quota = props.settings.defaultQuota
		if (quota !== 'none') {
			quota = parseFileSize(quota, true)
		}
	}

	if (quota === 'none' || quota === -3) {
		return t('settings', 'Unlimited')
	} else if (quota >= 0) {
		return formatFileSize(quota)
	}
	return formatFileSize(0)
})

const userActions = computed(() => {
	const actions = [
		{
			icon: 'icon-delete',
			text: t('settings', 'Delete account'),
			action: deleteUser,
		},
		{
			icon: 'icon-delete',
			text: t('settings', 'Disconnect all devices and delete local data'),
			action: wipeUserDevices,
		},
		{
			icon: props.user.enabled ? 'icon-close' : 'icon-add',
			text: props.user.enabled ? t('settings', 'Disable account') : t('settings', 'Enable account'),
			action: enableDisableUser,
		},
	]
	if (props.user.email !== null && props.user.email !== '') {
		actions.push({
			icon: 'icon-mail',
			text: t('settings', 'Resend welcome email'),
			action: sendWelcomeMail,
		})
	}
	return actions.concat(props.externalActions)
})

/**
 * Open the edit dialog via the list-provided callback.
 */
function toggleEdit() {
	if (props.onEditUser) {
		props.onEditUser(props.user)
	}
}

/**
 * Confirm and remotely wipe the account's devices.
 */
async function wipeUserDevices() {
	const userid = props.user.id
	await confirmPassword()
	OC.dialogs.confirmDestructive(
		t(
			'settings',
			'In case of lost device or exiting the organization, this can remotely wipe the {productName} data from all devices associated with {userid}. Only works if the devices are connected to the internet.',
			{ userid, productName },
		),
		t('settings', 'Remote wipe of devices'),
		{
			type: OC.dialogs.YES_NO_BUTTONS,
			confirm: t('settings', 'Wipe {userid}\'s devices', { userid }),
			confirmClasses: 'error',
			cancel: t('settings', 'Cancel'),
		},
		(result) => {
			if (result) {
				loading.wipe = true
				loading.all = true
				store.dispatch('wipeUserDevices', userid)
					.then(() => showSuccess(t('settings', 'Wiped {userid}\'s devices', { userid })), { timeout: 2000 })
					.finally(() => {
						loading.wipe = false
						loading.all = false
					})
			}
		},
		true,
	)
}

/**
 * Confirm and fully delete the account and its data.
 */
async function deleteUser() {
	const userid = props.user.id
	await confirmPassword()
	OC.dialogs.confirmDestructive(
		t('settings', 'Fully delete {userid}\'s account including all their personal files, app data, etc.', { userid }),
		t('settings', 'Account deletion'),
		{
			type: OC.dialogs.YES_NO_BUTTONS,
			confirm: t('settings', 'Delete {userid}\'s account', { userid }),
			confirmClasses: 'error',
			cancel: t('settings', 'Cancel'),
		},
		(result) => {
			if (result) {
				loading.delete = true
				loading.all = true
				return store.dispatch('deleteUser', userid)
					.then(() => {
						loading.delete = false
						loading.all = false
					})
			}
		},
		true,
	)
}

/**
 * Toggle the account's enabled state.
 */
function enableDisableUser() {
	loading.delete = true
	loading.all = true
	const userid = props.user.id
	const enabled = !props.user.enabled
	return store.dispatch('enableDisableUser', {
		userid,
		enabled,
	})
		.then(() => {
			loading.delete = false
			loading.all = false
		})
}

/**
 * Resend the welcome email to the account.
 */
function sendWelcomeMail() {
	loading.all = true
	store.dispatch('sendWelcomeMail', props.user.id)
		.then(() => showSuccess(t('settings', 'Welcome mail sent!'), { timeout: 2000 }))
		.finally(() => {
			loading.all = false
		})
}
</script>

<style lang="scss" scoped>
@use './shared/styles.scss';

.user-list__row {
	@include styles.row;

	&:hover {
		background-color: var(--color-background-hover);

		.row__cell:not(.row__cell--actions) {
			background-color: var(--color-background-hover);
		}
	}
}

.row {
	@include styles.cell;

	&__cell {
		border-bottom: 1px solid var(--color-border);
	}

	&__progress {
		margin-top: 4px;

		&--warn {
			&::-moz-progress-bar {
				background: var(--color-warning) !important;
			}
			&::-webkit-progress-value {
				background: var(--color-warning) !important;
			}
		}
	}
}
</style>
