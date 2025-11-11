<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcAppSettingsDialog
		:open="open"
		:show-navigation="true"
		:name="t('files', 'Files settings')"
		@update:open="onClose">
		<!-- Settings API-->
		<NcAppSettingsSection id="settings" :name="t('files', 'General')">
			<fieldset
				class="files-settings__default-view"
				data-cy-files-settings-setting="default_view">
				<legend>
					{{ t('files', 'Default view') }}
				</legend>
				<NcCheckboxRadioSwitch
					:model-value="userConfig.default_view"
					name="default_view"
					type="radio"
					value="files"
					@update:model-value="setConfig('default_view', $event)">
					{{ t('files', 'All files') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch
					:model-value="userConfig.default_view"
					name="default_view"
					type="radio"
					value="personal"
					@update:model-value="setConfig('default_view', $event)">
					{{ t('files', 'Personal files') }}
				</NcCheckboxRadioSwitch>
			</fieldset>
			<NcCheckboxRadioSwitch
				data-cy-files-settings-setting="sort_favorites_first"
				:checked="userConfig.sort_favorites_first"
				@update:checked="setConfig('sort_favorites_first', $event)">
				{{ t('files', 'Sort favorites first') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch
				data-cy-files-settings-setting="sort_folders_first"
				:checked="userConfig.sort_folders_first"
				@update:checked="setConfig('sort_folders_first', $event)">
				{{ t('files', 'Sort folders before files') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch
				data-cy-files-settings-setting="folder_tree"
				:checked="userConfig.folder_tree"
				@update:checked="setConfig('folder_tree', $event)">
				{{ t('files', 'Folder tree') }}
			</NcCheckboxRadioSwitch>
		</NcAppSettingsSection>

		<!-- Appearance -->
		<NcAppSettingsSection id="appearance" :name="t('files', 'Appearance')">
			<NcCheckboxRadioSwitch
				data-cy-files-settings-setting="show_hidden"
				:checked="userConfig.show_hidden"
				@update:checked="setConfig('show_hidden', $event)">
				{{ t('files', 'Show hidden files') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch
				data-cy-files-settings-setting="show_mime_column"
				:checked="userConfig.show_mime_column"
				@update:checked="setConfig('show_mime_column', $event)">
				{{ t('files', 'Show file type column') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch
				data-cy-files-settings-setting="show_files_extensions"
				:checked="userConfig.show_files_extensions"
				@update:checked="setConfig('show_files_extensions', $event)">
				{{ t('files', 'Show file extensions') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch
				data-cy-files-settings-setting="crop_image_previews"
				:checked="userConfig.crop_image_previews"
				@update:checked="setConfig('crop_image_previews', $event)">
				{{ t('files', 'Crop image previews') }}
			</NcCheckboxRadioSwitch>
		</NcAppSettingsSection>

		<!-- Settings API-->
		<NcAppSettingsSection
			v-if="settings.length !== 0"
			id="more-settings"
			:name="t('files', 'Additional settings')">
			<FilesAppSettingsEntry v-for="setting in settings" :key="setting.name" :el="setting.el" />
		</NcAppSettingsSection>

		<!-- Webdav URL-->
		<NcAppSettingsSection id="webdav" :name="t('files', 'WebDAV')">
			<NcInputField
				id="webdav-url-input"
				:label="t('files', 'WebDAV URL')"
				:show-trailing-button="true"
				:success="webdavUrlCopied"
				:trailing-button-label="t('files', 'Copy')"
				:value="webdavUrl"
				class="webdav-url-input"
				readonly="readonly"
				type="url"
				@focus="$event.target.select()"
				@trailing-button-click="copyCloudId">
				<template #trailing-button-icon>
					<Clipboard :size="20" />
				</template>
			</NcInputField>
			<em>
				<a
					class="setting-link"
					:href="webdavDocs"
					target="_blank"
					rel="noreferrer noopener">
					{{ t('files', 'How to access files using WebDAV') }} ↗
				</a>
			</em>
			<br>
			<em v-if="isTwoFactorEnabled">
				<a class="setting-link" :href="appPasswordUrl">
					{{ t('files', 'Two-Factor Authentication is enabled for your account, and therefore you need to use an app password to connect an external WebDAV client.') }} ↗
				</a>
			</em>
		</NcAppSettingsSection>

		<NcAppSettingsSection id="warning" :name="t('files', 'Warnings')">
			<NcCheckboxRadioSwitch
				type="switch"
				:checked="userConfig.show_dialog_file_extension"
				@update:checked="setConfig('show_dialog_file_extension', $event)">
				{{ t('files', 'Warn before changing a file extension') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch
				type="switch"
				:checked="userConfig.show_dialog_deletion"
				@update:checked="setConfig('show_dialog_deletion', $event)">
				{{ t('files', 'Warn before deleting files') }}
			</NcCheckboxRadioSwitch>
		</NcAppSettingsSection>

		<FilesAppSettingsShortcuts />
	</NcAppSettingsDialog>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { getCapabilities } from '@nextcloud/capabilities'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateRemoteUrl, generateUrl } from '@nextcloud/router'
import { useHotKey } from '@nextcloud/vue/composables/useHotKey'
import NcAppSettingsDialog from '@nextcloud/vue/components/NcAppSettingsDialog'
import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import Clipboard from 'vue-material-design-icons/ContentCopy.vue'
import FilesAppSettingsEntry from '../components/FilesAppSettings/FilesAppSettingsEntry.vue'
import FilesAppSettingsShortcuts from '../components/FilesAppSettings/FilesAppSettingsShortcuts.vue'
import { useUserConfigStore } from '../store/userconfig.ts'

export default {
	name: 'FilesAppSettings',
	components: {
		Clipboard,
		FilesAppSettingsEntry,
		FilesAppSettingsShortcuts,
		NcAppSettingsDialog,
		NcAppSettingsSection,
		NcCheckboxRadioSwitch,
		NcInputField,
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
			isTwoFactorEnabled: (loadState('files', 'isTwoFactorEnabled', false)),
		}
	},

	computed: {
		userConfig() {
			return this.userConfigStore.userConfig
		},

		sortedSettings() {
			// Sort settings by name
			return [...this.settings].sort((a, b) => {
				if (a.order && b.order) {
					return a.order - b.order
				}
				return a.name.localeCompare(b.name)
			})
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
		this.settings.forEach((setting) => setting.open())
	},

	beforeUnmount() {
		// Update the settings API entries state
		this.settings.forEach((setting) => setting.close())
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
			showSuccess(t('files', 'WebDAV URL copied'))
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
.files-settings {
	&__default-view {
		margin-bottom: 0.5rem;
	}
}

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

.webdav-url-input {
	margin-block-end: 0.5rem;
}
</style>
