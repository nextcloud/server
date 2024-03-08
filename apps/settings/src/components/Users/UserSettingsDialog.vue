<!--
	- @copyright 2023 Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
	-
	- @license AGPL-3.0-or-later
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
				data-test="showLastLogin"
				:checked.sync="showLastLogin">
				{{ t('settings', 'Show last login') }}
			</NcCheckboxRadioSwitch>
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
				:input-label="t('settings', 'Default quota')"
				placement="top"
				:taggable="true"
				:options="quotaOptions"
				:create-option="validateQuota"
				:placeholder="t('settings', 'Select default quota')"
				:clearable="false"
				@option:selected="setDefaultQuota" />
		</NcAppSettingsSection>
	</NcAppSettingsDialog>
</template>

<script>
import { getBuilder } from '@nextcloud/browser-storage'
import { formatFileSize, parseFileSize } from '@nextcloud/files'
import { generateUrl } from '@nextcloud/router'

import axios from '@nextcloud/axios'
import NcAppSettingsDialog from '@nextcloud/vue/dist/Components/NcAppSettingsDialog.js'
import NcAppSettingsSection from '@nextcloud/vue/dist/Components/NcAppSettingsSection.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'

import { unlimitedQuota } from '../../utils/userUtils.ts'

export default {
	name: 'UserSettingsDialog',

	components: {
		NcAppSettingsDialog,
		NcAppSettingsSection,
		NcCheckboxRadioSwitch,
		NcSelect,
	},

	props: {
		open: {
			type: Boolean,
			required: true,
		},
	},

	setup() {
		const localStorage = getBuilder('settings')
			.persist(true)
			.clearOnLogout(true)
			.build()

		return { localStorage }
	},

	data() {
		return {
			selectedQuota: false,
			loadingSendMail: false,
		}
	},

	computed: {
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
				return this.getLocalstorage('showLanguages')
			},
			set(status) {
				this.setLocalStorage('showLanguages', status)
			},
		},

		showLastLogin: {
			get() {
				return this.getLocalstorage('showLastLogin')
			},
			set(status) {
				this.setLocalStorage('showLastLogin', status)
			},
		},

		showUserBackend: {
			get() {
				return this.getLocalstorage('showUserBackend')
			},
			set(status) {
				this.setLocalStorage('showUserBackend', status)
			},
		},

		showStoragePath: {
			get() {
				return this.getLocalstorage('showStoragePath')
			},
			set(status) {
				this.setLocalStorage('showStoragePath', status)
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
				} catch (e) {
					console.error('could not update newUser.sendEmail preference: ' + e.message, e)
				} finally {
					this.loadingSendMail = false
				}
			},
		},
	},

	methods: {
		getLocalstorage(key) {
			// force initialization
			const localConfig = JSON.parse(this.localStorage.getItem(key) ?? 'null')
			// if localstorage is null, fallback to original values
			this.$store.commit('setShowConfig', { key, value: localConfig !== null ? localConfig === 'true' : this.showConfig[key] })
			return this.showConfig[key]
		},

		setLocalStorage(key, status) {
			this.$store.commit('setShowConfig', { key, value: status })
			this.localStorage.setItem(key, JSON.stringify(status))
			return status
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
			const validQuota = parseFileSize(quota)
			if (validQuota === null) {
				return unlimitedQuota
			} else {
				// unify format output
				quota = formatFileSize(parseFileSize(quota))
				return { id: quota, label: quota }
			}
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
