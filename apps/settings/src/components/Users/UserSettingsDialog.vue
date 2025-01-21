<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppSettingsDialog :open.sync="isModalOpen"
		:show-navigation="true"
		:name="t('settings', 'Account management settings')">
		<NcAppSettingsSection id="visibility-settings"
			:name="t('settings', 'Visibility')">
			<NcCheckboxRadioSwitch type="switch"
				data-test="showLanguages"
				:checked.sync="showLanguages">
				{{ t('settings', 'Show language') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch type="switch"
				data-test="showUserBackend"
				:checked.sync="showUserBackend">
				{{ t('settings', 'Show account backend') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch type="switch"
				data-test="showStoragePath"
				:checked.sync="showStoragePath">
				{{ t('settings', 'Show storage path') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch type="switch"
				data-test="showFirstLogin"
				:checked.sync="showFirstLogin">
				{{ t('settings', 'Show first login') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch type="switch"
				data-test="showLastLogin"
				:checked.sync="showLastLogin">
				{{ t('settings', 'Show last login') }}
			</NcCheckboxRadioSwitch>
		</NcAppSettingsSection>

		<NcAppSettingsSection id="groups-sorting"
			:name="t('settings', 'Sorting')">
			<NcNoteCard v-if="isGroupSortingEnforced" type="warning">
				{{ t('settings', 'The system config enforces sorting the groups by name. This also disables showing the member count.') }}
			</NcNoteCard>
			<fieldset>
				<legend>{{ t('settings', 'Group list sorting') }}</legend>
				<NcCheckboxRadioSwitch type="radio"
					:checked.sync="groupSorting"
					data-test="sortGroupsByMemberCount"
					:disabled="isGroupSortingEnforced"
					name="group-sorting-mode"
					value="member-count">
					{{ t('settings', 'By member count') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch type="radio"
					:checked.sync="groupSorting"
					data-test="sortGroupsByName"
					:disabled="isGroupSortingEnforced"
					name="group-sorting-mode"
					value="name">
					{{ t('settings', 'By name') }}
				</NcCheckboxRadioSwitch>
			</fieldset>
		</NcAppSettingsSection>

		<NcAppSettingsSection id="email-settings"
			:name="t('settings', 'Send email')">
			<NcCheckboxRadioSwitch type="switch"
				data-test="sendWelcomeMail"
				:checked.sync="sendWelcomeMail"
				:disabled="loadingSendMail">
				{{ t('settings', 'Send welcome email to new accounts') }}
			</NcCheckboxRadioSwitch>
		</NcAppSettingsSection>

		<NcAppSettingsSection id="default-settings"
			:name="t('settings', 'Defaults')">
			<NcSelect v-model="defaultQuota"
				:clearable="false"
				:create-option="validateQuota"
				:filter-by="filterQuotas"
				:input-label="t('settings', 'Default quota')"
				:options="quotaOptions"
				placement="top"
				:placeholder="t('settings', 'Select default quota')"
				taggable
				@option:selected="setDefaultQuota" />
		</NcAppSettingsSection>
	</NcAppSettingsDialog>
</template>

<script>
import { formatFileSize, parseFileSize } from '@nextcloud/files'
import { generateUrl } from '@nextcloud/router'

import axios from '@nextcloud/axios'
import NcAppSettingsDialog from '@nextcloud/vue/dist/Components/NcAppSettingsDialog.js'
import NcAppSettingsSection from '@nextcloud/vue/dist/Components/NcAppSettingsSection.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'

import { GroupSorting } from '../../constants/GroupManagement.ts'
import { unlimitedQuota } from '../../utils/userUtils.ts'
import logger from '../../logger.ts'

export default {
	name: 'UserSettingsDialog',

	components: {
		NcAppSettingsDialog,
		NcAppSettingsSection,
		NcCheckboxRadioSwitch,
		NcNoteCard,
		NcSelect,
	},

	props: {
		open: {
			type: Boolean,
			required: true,
		},
	},

	data() {
		return {
			selectedQuota: false,
			loadingSendMail: false,
		}
	},

	computed: {
		groupSorting: {
			get() {
				return this.$store.getters.getGroupSorting === GroupSorting.GroupName ? 'name' : 'member-count'
			},
			set(sorting) {
				this.$store.commit('setGroupSorting', sorting === 'name' ? GroupSorting.GroupName : GroupSorting.UserCount)
			},
		},

		/**
		 * Admin has configured `sort_groups_by_name` in the system config
		 */
		isGroupSortingEnforced() {
			return this.$store.getters.getServerData.forceSortGroupByName
		},

		isModalOpen: {
			get() {
				return this.open
			},
			set(open) {
				this.$emit('update:open', open)
			},
		},

		showConfig() {
			return this.$store.getters.getShowConfig
		},

		settings() {
			return this.$store.getters.getServerData
		},

		showLanguages: {
			get() {
				return this.showConfig.showLanguages
			},
			set(status) {
				this.setShowConfig('showLanguages', status)
			},
		},

		showFirstLogin: {
			get() {
				return this.showConfig.showFirstLogin
			},
			set(status) {
				this.setShowConfig('showFirstLogin', status)
			},
		},

		showLastLogin: {
			get() {
				return this.showConfig.showLastLogin
			},
			set(status) {
				this.setShowConfig('showLastLogin', status)
			},
		},

		showUserBackend: {
			get() {
				return this.showConfig.showUserBackend
			},
			set(status) {
				this.setShowConfig('showUserBackend', status)
			},
		},

		showStoragePath: {
			get() {
				return this.showConfig.showStoragePath
			},
			set(status) {
				this.setShowConfig('showStoragePath', status)
			},
		},

		quotaOptions() {
			// convert the preset array into objects
			const quotaPreset = this.settings.quotaPreset.reduce((acc, cur) => acc.concat({ id: cur, label: cur }), [])
			// add default presets
			if (this.settings.allowUnlimitedQuota) {
				quotaPreset.unshift(unlimitedQuota)
			}
			return quotaPreset
		},

		defaultQuota: {
			get() {
				if (this.selectedQuota !== false) {
					return this.selectedQuota
				}
				if (this.settings.defaultQuota !== unlimitedQuota.id && OC.Util.computerFileSize(this.settings.defaultQuota) >= 0) {
					// if value is valid, let's map the quotaOptions or return custom quota
					return { id: this.settings.defaultQuota, label: this.settings.defaultQuota }
				}
				return unlimitedQuota // unlimited
			},
			set(quota) {
				this.selectedQuota = quota
			},
		},

		sendWelcomeMail: {
			get() {
				return this.settings.newUserSendEmail
			},
			async set(value) {
				try {
					this.loadingSendMail = true
					this.$store.commit('setServerData', {
						...this.settings,
						newUserSendEmail: value,
					})
					await axios.post(generateUrl('/settings/users/preferences/newUser.sendEmail'), { value: value ? 'yes' : 'no' })
				} catch (error) {
					logger.error('Could not update newUser.sendEmail preference', { error })
				} finally {
					this.loadingSendMail = false
				}
			},
		},
	},

	methods: {
		/**
		 * Check if a quota matches the current search.
		 * This is a custom filter function to allow to map "1GB" to the label "1 GB" (ignoring whitespaces).
		 *
		 * @param option The quota to check
		 * @param label The label of the quota
		 * @param search The search string
		 */
		filterQuotas(option, label, search) {
			const searchValue = search.toLocaleLowerCase().replaceAll(/\s/g, '')
			return (label || '')
				.toLocaleLowerCase()
				.replaceAll(/\s/g, '')
				.indexOf(searchValue) > -1
		},

		setShowConfig(key, status) {
			this.$store.commit('setShowConfig', { key, value: status })
		},

		/**
		 * Validate quota string to make sure it's a valid human file size
		 *
		 * @param {string | object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
		 * @return {object} The validated quota object or unlimited quota if input is invalid
		 */
		validateQuota(quota) {
			if (typeof quota === 'object') {
				quota = quota?.id || quota.label
			}
			// only used for new presets sent through @Tag
			const validQuota = parseFileSize(quota, true)
			if (validQuota === null) {
				return unlimitedQuota
			}
			// unify format output
			quota = formatFileSize(validQuota)
			return { id: quota, label: quota }
		},

		/**
		 * Dispatch default quota set request
		 *
		 * @param {string | object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
		 */
		setDefaultQuota(quota = 'none') {
			// Make sure correct label is set for unlimited quota
			if (quota === 'none') {
				quota = unlimitedQuota
			}
			this.$store.dispatch('setAppConfig', {
				app: 'files',
				key: 'default_quota',
				// ensure we only send the preset id
				value: quota.id ? quota.id : quota,
			}).then(() => {
				if (typeof quota !== 'object') {
					quota = { id: quota, label: quota }
				}
				this.defaultQuota = quota
			})
		},
	},
}
</script>

<style scoped lang="scss">
fieldset {
	font-weight: bold;
}
</style>
