<!--
  - @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
  -	@copyright Copyright (c) 2024 Jed Boulahya <jed.boulahya@medtech.tn>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<NcAppSettingsDialog :open="open"
		:show-navigation="true"
		:name="t('files', 'Files settings')"
		@update:open="onClose">
		<!-- Settings API-->
		<NcAppSettingsSection id="settings" :name="t('files', 'Files settings')">
			<NcCheckboxRadioSwitch data-cy-files-settings-setting="sort_favorites_first"
				:checked="userConfig.sort_favorites_first"
				@update:checked="setConfig('sort_favorites_first', $event)">
				{{ t('files', 'Sort favorites first') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch data-cy-files-settings-setting="sort_folders_first"
				:checked="userConfig.sort_folders_first"
				@update:checked="setConfig('sort_folders_first', $event)">
				{{ t('files', 'Sort folders before files') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch data-cy-files-settings-setting="show_hidden"
				:checked="userConfig.show_hidden"
				@update:checked="setConfig('show_hidden', $event)">
				{{ t('files', 'Show hidden files') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch data-cy-files-settings-setting="crop_image_previews"
				:checked="userConfig.crop_image_previews"
				@update:checked="setConfig('crop_image_previews', $event)">
				{{ t('files', 'Crop image previews') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch v-if="enableGridView"
				data-cy-files-settings-setting="grid_view"
				:checked="userConfig.grid_view"
				@update:checked="setConfig('grid_view', $event)">
				{{ t('files', 'Enable the grid view') }}
			</NcCheckboxRadioSwitch>
		</NcAppSettingsSection>

		<!-- Settings API-->
		<NcAppSettingsSection v-if="settings.length !== 0"
			id="more-settings"
			:name="t('files', 'Additional settings')">
			<template v-for="setting in settings">
				<Setting :key="setting.name" :el="setting.el" />
			</template>
		</NcAppSettingsSection>

		<!-- Webdav URL-->
		<NcAppSettingsSection id="webdav" :name="t('files', 'WebDAV')">
			<NcInputField id="webdav-url-input"
				:label="t('files', 'WebDAV URL')"
				:show-trailing-button="true"
				:success="webdavUrlCopied"
				:trailing-button-label="t('files', 'Copy to clipboard')"
				:value="webdavUrl"
				readonly="readonly"
				type="url"
				@focus="$event.target.select()"
				@trailing-button-click="copyCloudId">
				<template #trailing-button-icon>
					<Clipboard :size="20" />
				</template>
			</NcInputField>
			<em>
				<a class="setting-link"
					:href="webdavDocs"
					target="_blank"
					rel="noreferrer noopener">
					{{ t('files', 'Use this address to access your Files via WebDAV') }} ↗
				</a>
			</em>
			<br>
			<!-- Conditional rendering based on 2FA -->
			<template v-if="isTwoFactorEnabled">
				<em>
					<a class="setting-link" :href="appPasswordUrl">
						{{ t('files', 'If you have enabled 2FA, you must create and use a new app password by clicking here.') }} ↗
					</a>
				</em>
			</template>
		</NcAppSettingsSection>
	</NcAppSettingsDialog>
</template>

<script>
import NcAppSettingsDialog from '@nextcloud/vue/dist/Components/NcAppSettingsDialog.js'
import NcAppSettingsSection from '@nextcloud/vue/dist/Components/NcAppSettingsSection.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import Clipboard from 'vue-material-design-icons/Clipboard.vue'
import NcInputField from '@nextcloud/vue/dist/Components/NcInputField.js'
import Setting from '../components/Setting.vue'

import { generateRemoteUrl, generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate } from '@nextcloud/l10n'
import { loadState } from '@nextcloud/initial-state'
import { useUserConfigStore } from '../store/userconfig.ts'

export default {
	name: 'Settings',
	components: {
		Clipboard,
		NcAppSettingsDialog,
		NcAppSettingsSection,
		NcCheckboxRadioSwitch,
		NcInputField,
		Setting,
	},

	props: {
		open: {
			type: Boolean,
			default: false,
		},
	},

	setup() {
		const userConfigStore = useUserConfigStore()
		return {
			userConfigStore,
		}
	},

	data() {
		return {
			// Settings API
			settings: window.OCA?.Files?.Settings?.settings || [],

			// Webdav infos
			webdavUrl: generateRemoteUrl('dav/files/' + encodeURIComponent(getCurrentUser()?.uid)),
			webdavDocs: 'https://docs.nextcloud.com/server/stable/go.php?to=user-webdav',
			appPasswordUrl: generateUrl('/settings/user/security#generate-app-token-section'),
			webdavUrlCopied: false,
			enableGridView: (loadState('core', 'config', [])['enable_non-accessible_features'] ?? true),
		}
	},

	computed: {
		userConfig() {
			return this.userConfigStore.userConfig
		},
	},

	beforeMount() {
		// Update the settings API entries state
		this.settings.forEach(setting => setting.open())
	},

	beforeDestroy() {
		// Update the settings API entries state
		this.settings.forEach(setting => setting.close())
	},

	methods: {
		onClose() {
			this.$emit('close')
		},

		setConfig(key, value) {
			this.userConfigStore.update(key, value)
		},

		async copyCloudId() {
			document.querySelector('input#webdav-url-input').select()

			if (!navigator.clipboard) {
				// Clipboard API not available
				showError(t('files', 'Clipboard is not available'))
				return
			}

			await navigator.clipboard.writeText(this.webdavUrl)
			this.webdavUrlCopied = true
			showSuccess(t('files', 'WebDAV URL copied to clipboard'))
			setTimeout(() => {
				this.webdavUrlCopied = false
			}, 5000)
		},

		t: translate,
	},
}
</script>

<style lang="scss" scoped>
.setting-link:hover {
	text-decoration: underline;
}
</style>
