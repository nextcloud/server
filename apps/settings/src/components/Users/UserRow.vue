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
				disable-menu
				hide-status
				:user="user.id" />
		</td>

		<td class="row__cell row__cell--displayname" data-cy-user-list-cell-displayname>
			<strong
				v-if="!isObfuscated"
				:title="user.displayname?.length > 20 ? user.displayname : null">
				{{ user.displayname }}
			</strong>
		</td>

		<td class="row__cell row__cell--username" data-cy-user-list-cell-username>
			<span class="row__subtitle">{{ user.id }}</span>
		</td>

		<td class="row__cell" data-cy-user-list-cell-email>
			<span
				v-if="!isObfuscated"
				:title="user.email?.length > 20 ? user.email : null">
				{{ user.email }}
			</span>
		</td>

		<td class="row__cell row__cell--groups row__cell--multiline" data-cy-user-list-cell-groups>
			<span
				v-if="!isObfuscated"
				:title="userGroupsLabels?.length > 40 ? userGroupsLabels : null">
				{{ userGroupsLabels }}
			</span>
		</td>

		<td
			v-if="settings.isAdmin || settings.isDelegatedAdmin"
			data-cy-user-list-cell-subadmins
			class="row__cell row__cell--large row__cell--multiline">
			<span
				v-if="!isObfuscated"
				:title="userSubAdminGroupsLabels?.length > 40 ? userSubAdminGroupsLabels : null">
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

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { showSuccess } from '@nextcloud/dialogs'
import { formatFileSize, parseFileSize } from '@nextcloud/files'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { useFormatDateTime } from '@nextcloud/vue'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcProgressBar from '@nextcloud/vue/components/NcProgressBar'
import UserRowActions from './UserRowActions.vue'
import { isObfuscated } from '../../utils/userUtils.ts'

const productName = window.OC.theme.productName

export default {
	name: 'UserRow',

	components: {
		NcAvatar,
		NcLoadingIcon,
		NcProgressBar,
		UserRowActions,
	},

	props: {
		user: {
			type: Object,
			required: true,
		},

		visible: {
			type: Boolean,
			required: true,
		},

		users: {
			type: Array,
			required: true,
		},

		quotaOptions: {
			type: Array,
			required: true,
		},

		languages: {
			type: Array,
			required: true,
		},

		settings: {
			type: Object,
			required: true,
		},

		externalActions: {
			type: Array,
			default: () => [],
		},

		/** Callback from UserList to open the edit dialog */
		onEditUser: {
			type: Function,
			default: null,
		},
	},

	setup(props) {
		const { formattedFullTime } = useFormatDateTime(props.user.firstLoginTimestamp * 1000, {
			relativeTime: false,
			format: {
				timeStyle: 'short',
				dateStyle: 'short',
			},
		})
		return {
			formattedFullTime,
		}
	},

	data() {
		return {
			rand: Math.random().toString(36).substring(2),
			loading: {
				all: false,
				delete: false,
				disable: false,
				wipe: false,
			},
		}
	},

	computed: {
		isObfuscated() {
			return isObfuscated(this.user)
		},

		usedQuota() {
			let quota = this.user.quota.quota
			if (quota > 0) {
				quota = Math.min(100, Math.round(this.user.quota.used / quota * 100))
			} else {
				const usedInGB = this.user.quota.used / (10 * Math.pow(2, 30))
				// asymptotic curve approaching 50% at 10GB to visualize used space with infinite quota
				quota = 95 * (1 - (1 / (usedInGB + 1)))
			}
			return isNaN(quota) ? 0 : quota
		},

		userLanguage() {
			const availableLanguages = this.languages[0].languages.concat(this.languages[1].languages)
			const userLang = availableLanguages.find((lang) => lang.code === this.user.language)
			if (typeof userLang !== 'object' && this.user.language !== '') {
				return {
					code: this.user.language,
					name: this.user.language,
				}
			} else if (this.user.language === '') {
				return false
			}
			return userLang
		},

		userFirstLogin() {
			if (this.user.firstLoginTimestamp > 0) {
				return this.formattedFullTime
			}
			if (this.user.firstLoginTimestamp < 0) {
				return t('settings', 'Unknown')
			}
			return t('settings', 'Never')
		},

		userLastLoginTooltip() {
			if (this.user.lastLoginTimestamp > 0) {
				return OC.Util.formatDate(this.user.lastLoginTimestamp * 1000)
			}
			return ''
		},

		userLastLogin() {
			if (this.user.lastLoginTimestamp > 0) {
				return OC.Util.relativeModifiedDate(this.user.lastLoginTimestamp * 1000)
			}
			return t('settings', 'Never')
		},

		showConfig() {
			return this.$store.getters.getShowConfig
		},

		isLoadingUser() {
			return this.loading.delete || this.loading.disable || this.loading.wipe
		},

		isLoadingField() {
			return this.loading.delete || this.loading.disable || this.loading.all
		},

		uniqueId() {
			return encodeURIComponent(this.user.id + this.rand)
		},

		userGroupsLabels() {
			const allGroups = this.$store.getters.getGroups
			return this.user.groups
				.map((id) => {
					const group = allGroups.find((g) => g.id === id)
					return group?.name ?? id
				})
				.join(', ')
		},

		userSubAdminGroupsLabels() {
			const allGroups = this.$store.getters.getGroups
			return (this.user.subadmin ?? [])
				.map((id) => {
					const group = allGroups.find((g) => g.id === id)
					return group?.name ?? id
				})
				.join(', ')
		},

		usedSpace() {
			if (this.user.quota?.used) {
				return t('settings', '{size} used', { size: formatFileSize(this.user.quota?.used) })
			}
			return t('settings', '{size} used', { size: formatFileSize(0) })
		},

		canEdit() {
			return getCurrentUser().uid !== this.user.id || this.settings.isAdmin || this.settings.isDelegatedAdmin
		},

		userQuota() {
			let quota = this.user.quota?.quota

			if (quota === 'default') {
				quota = this.settings.defaultQuota
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
		},

		userActions() {
			const actions = [
				{
					icon: 'icon-delete',
					text: t('settings', 'Delete account'),
					action: this.deleteUser,
				},
				{
					icon: 'icon-delete',
					text: t('settings', 'Disconnect all devices and delete local data'),
					action: this.wipeUserDevices,
				},
				{
					icon: this.user.enabled ? 'icon-close' : 'icon-add',
					text: this.user.enabled ? t('settings', 'Disable account') : t('settings', 'Enable account'),
					action: this.enableDisableUser,
				},
			]
			if (this.user.email !== null && this.user.email !== '') {
				actions.push({
					icon: 'icon-mail',
					text: t('settings', 'Resend welcome email'),
					action: this.sendWelcomeMail,
				})
			}
			return actions.concat(this.externalActions)
		},
	},

	methods: {
		toggleEdit() {
			if (this.onEditUser) {
				this.onEditUser(this.user)
			}
		},

		async wipeUserDevices() {
			const userid = this.user.id
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
						this.loading.wipe = true
						this.loading.all = true
						this.$store.dispatch('wipeUserDevices', userid)
							.then(() => showSuccess(t('settings', 'Wiped {userid}\'s devices', { userid })), { timeout: 2000 })
							.finally(() => {
								this.loading.wipe = false
								this.loading.all = false
							})
					}
				},
				true,
			)
		},

		async deleteUser() {
			const userid = this.user.id
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
						this.loading.delete = true
						this.loading.all = true
						return this.$store.dispatch('deleteUser', userid)
							.then(() => {
								this.loading.delete = false
								this.loading.all = false
							})
					}
				},
				true,
			)
		},

		enableDisableUser() {
			this.loading.delete = true
			this.loading.all = true
			const userid = this.user.id
			const enabled = !this.user.enabled
			return this.$store.dispatch('enableDisableUser', {
				userid,
				enabled,
			})
				.then(() => {
					this.loading.delete = false
					this.loading.all = false
				})
		},

		sendWelcomeMail() {
			this.loading.all = true
			this.$store.dispatch('sendWelcomeMail', this.user.id)
				.then(() => showSuccess(t('settings', 'Welcome mail sent!'), { timeout: 2000 }))
				.finally(() => {
					this.loading.all = false
				})
		},
	},
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
