<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
			<NcCheckboxRadioSwitch data-cy-files-settings-setting="folder_tree"
				:checked="userConfig.folder_tree"
				@update:checked="setConfig('folder_tree', $event)">
				{{ t('files', 'Enable folder tree') }}
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
			<em>
				<a class="setting-link" :href="appPasswordUrl">
					{{ t('files', 'If you have enabled 2FA, you must create and use a new app password by clicking here.') }} ↗
				</a>
			</em>
		</NcAppSettingsSection>

		<NcAppSettingsSection id="shortcuts"
			:name="t('files', 'Keyboard shortcuts')">
			<em>{{ t('files', 'Speed up your Files experience with these quick shortcuts.') }}</em>

			<h3>{{ t('files', 'Actions') }}</h3>
			<dl>
				<div>
					<dt class="shortcut-key">
						<kbd>a</kbd>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Open the actions menu for a file') }}
					</dd>
				</div>
				<div>
					<dt class="shortcut-key">
						<kbd>F2</kbd>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Rename a file') }}
					</dd>
				</div>
				<div>
					<dt class="shortcut-key">
						<kbd>Del</kbd>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Delete a file') }}
					</dd>
				</div>
				<div>
					<dt class="shortcut-key">
						<kbd>s</kbd>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Favorite or remove a file from favorites') }}
					</dd>
				</div>
				<div v-if="isSystemtagsEnabled">
					<dt class="shortcut-key">
						<kbd>t</kbd>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Manage tags for a file') }}
					</dd>
				</div>
			</dl>

			<h3>{{ t('files', 'Selection') }}</h3>
			<dl>
				<div>
					<dt class="shortcut-key">
						<kbd>Ctrl</kbd> + <kbd>A</kbd>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Select all files') }}
					</dd>
				</div>
				<div>
					<dt class="shortcut-key">
						<kbd>ESC</kbd>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Deselect all files') }}
					</dd>
				</div>
				<div>
					<dt class="shortcut-key">
						<kbd>Ctrl</kbd> + <kbd>Space</kbd>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Select or deselect a file') }}
					</dd>
				</div>
				<div>
					<dt class="shortcut-key">
						<kbd>Ctrl</kbd> + <kbd>Shift</kbd> <span>+ <kbd>Space</kbd></span>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Select a range of files') }}
					</dd>
				</div>
			</dl>

			<h3>{{ t('files', 'Navigation') }}</h3>
			<dl>
				<div>
					<dt class="shortcut-key">
						<kbd>Alt</kbd> + <kbd>↑</kbd>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Navigate to the parent folder') }}
					</dd>
				</div>
				<div>
					<dt class="shortcut-key">
						<kbd>↑</kbd>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Navigate to the file above') }}
					</dd>
				</div>
				<div>
					<dt class="shortcut-key">
						<kbd>↓</kbd>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Navigate to the file below') }}
					</dd>
				</div>
				<div>
					<dt class="shortcut-key">
						<kbd>←</kbd>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Navigate to the file on the left (in grid mode)') }}
					</dd>
				</div>
				<div>
					<dt class="shortcut-key">
						<kbd>→</kbd>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Navigate to the file on the right (in grid mode)') }}
					</dd>
				</div>
			</dl>

			<h3>{{ t('files', 'View') }}</h3>
			<dl>
				<div>
					<dt class="shortcut-key">
						<kbd>V</kbd>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Toggle the grid view') }}
					</dd>
				</div>
				<div>
					<dt class="shortcut-key">
						<kbd>D</kbd>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Open the sidebar for a file') }}
					</dd>
				</div>
				<div>
					<dt class="shortcut-key">
						<kbd>?</kbd>
					</dt>
					<dd class="shortcut-description">
						{{ t('files', 'Show those shortcuts') }}
					</dd>
				</div>
			</dl>
		</NcAppSettingsSection>
	</NcAppSettingsDialog>
</template>

<script>
import { getCapabilities } from '@nextcloud/capabilities'
import Clipboard from 'vue-material-design-icons/ContentCopy.vue'
import NcAppSettingsDialog from '@nextcloud/vue/dist/Components/NcAppSettingsDialog.js'
import NcAppSettingsSection from '@nextcloud/vue/dist/Components/NcAppSettingsSection.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcInputField from '@nextcloud/vue/dist/Components/NcInputField.js'

import { generateRemoteUrl, generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { useHotKey } from '@nextcloud/vue/dist/Composables/useHotKey.js'

import { useUserConfigStore } from '../store/userconfig.ts'
import Setting from '../components/Setting.vue'

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
		const isSystemtagsEnabled = getCapabilities()?.systemtags?.enabled === true
		return {
			isSystemtagsEnabled,
			userConfigStore,
			t,
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

	created() {
		// ? opens the settings dialog on the keyboard shortcuts section
		useHotKey('?', this.showKeyboardShortcuts, {
			stop: true,
			prevent: true,
		})
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

		async showKeyboardShortcuts() {
			this.$emit('update:open', true)

			await this.$nextTick()
			document.getElementById('settings-section_shortcuts').scrollIntoView({
				behavior: 'smooth',
				inline: 'nearest',
			})
		},
	},
}
</script>

<style lang="scss" scoped>
.setting-link:hover {
	text-decoration: underline;
}

.shortcut-key {
	width: 160px;
	// some shortcuts are too long to fit in one line
	white-space: normal;
	span {
		// force portion of a shortcut on a new line for nicer display
		white-space: nowrap;
	}
}
</style>
