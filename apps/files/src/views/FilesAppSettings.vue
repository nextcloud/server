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
			<NcFormBox>
				<NcFormBoxSwitch
					v-model="userConfig.sort_favorites_first"
					:label="t('files', 'Sort favorites first')"
					data-cy-files-settings-setting="sort_favorites_first"
					@update:modelValue="setConfig('sort_favorites_first', $event)" />
				<NcFormBoxSwitch
					v-model="userConfig.sort_folders_first"
					:label="t('files', 'Sort folders before files')"
					data-cy-files-settings-setting="sort_folders_first"
					@update:modelValue="setConfig('sort_folders_first', $event)" />
				<NcFormBoxSwitch
					v-model="userConfig.folder_tree"
					:label="t('files', 'Folder tree')"
					data-cy-files-settings-setting="folder_tree"
					@update:modelValue="setConfig('folder_tree', $event)" />
			</NcFormBox>

			<NcRadioGroup
				v-model="userConfig.default_view"
				:label="t('files', 'Default view')"
				class="files-settings__default-view"
				data-cy-files-settings-setting="default_view"
				@update:modelValue="setConfig('default_view', $event)">
				<NcRadioGroupButton
					value="files"
					:label="t('files', 'All files')" />
				<NcRadioGroupButton
					value="personal"
					:label="t('files', 'Personal files')" />
			</NcRadioGroup>
		</NcAppSettingsSection>

		<!-- Appearance -->
		<NcAppSettingsSection id="appearance" :name="t('files', 'Appearance')">
			<NcFormBox>
				<NcFormBoxSwitch
					v-model="userConfig.show_hidden"
					:label="t('files', 'Show hidden files')"
					data-cy-files-settings-setting="show_hidden"
					@update:modelValue="setConfig('show_hidden', $event)" />
				<NcFormBoxSwitch
					v-model="userConfig.show_mime_column"
					:label="t('files', 'Show file type column')"
					data-cy-files-settings-setting="show_mime_column"
					@update:modelValue="setConfig('show_mime_column', $event)" />
				<NcFormBoxSwitch
					v-model="userConfig.show_files_extensions"
					:label="t('files', 'Show file extensions')"
					data-cy-files-settings-setting="show_files_extensions"
					@update:modelValue="setConfig('show_files_extensions', $event)" />
				<NcFormBoxSwitch
					v-model="userConfig.crop_image_previews"
					:label="t('files', 'Crop image previews')"
					data-cy-files-settings-setting="crop_image_previews"
					@update:modelValue="setConfig('crop_image_previews', $event)" />
			</NcFormBox>
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

			<NcFormBoxButton
				v-if="isTwoFactorEnabled"
				:label="t('files', 'Create an app password')"
				:description="t('files', 'Required for WebDAV because Two-Factor Authentication is enabled for this account')"
				:href="appPasswordUrl"
				target="_blank">
				<template #icon>
					<OpenInNew :size="20" />
				</template>
			</NcFormBoxButton>

			<NcFormBoxButton
				:label="t('files', 'How to access files using WebDAV')"
				:href="webdavDocs"
				target="_blank">
				<template #icon>
					<OpenInNew :size="20" />
				</template>
			</NcFormBoxButton>
		</NcAppSettingsSection>

		<NcAppSettingsSection id="warning" :name="t('files', 'Warnings')">
			<NcFormBox>
				<NcFormBoxSwitch
					v-model="userConfig.show_dialog_file_extension"
					:label="t('files', 'Warn before changing a file extension')"
					@update:modelValue="setConfig('show_dialog_file_extension', $event)" />
				<NcFormBoxSwitch
					v-model="userConfig.show_dialog_deletion"
					:label="t('files', 'Warn before deleting files')"
					@update:modelValue="setConfig('show_dialog_deletion', $event)" />
			</NcFormBox>
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
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcFormBoxSwitch from '@nextcloud/vue/components/NcFormBoxSwitch'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcRadioGroup from '@nextcloud/vue/components/NcRadioGroup'
import NcRadioGroupButton from '@nextcloud/vue/components/NcRadioGroupButton'
import Clipboard from 'vue-material-design-icons/ContentCopy.vue'
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue'
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
		NcFormBox,
		NcFormBoxButton,
		NcFormBoxSwitch,
		NcInputField,
		NcRadioGroup,
		NcRadioGroupButton,
		OpenInNew,
	},

	props: {
		open: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['close', 'update:open'],

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

		async setConfig(key, value) {
			try {
				await this.userConfigStore.update(key, value)
				showSuccess(t('files', 'Setting saved'))
			} catch {
				showError(t('files', 'Failed to save setting'))
			}
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
.files-settings__default-view {
	margin-block-start: calc(var(--default-grid-baseline) * 4);

	:deep(.radio-group__button-group) {
		font-size: 1.05em;

		button {
			padding-block: calc(var(--default-grid-baseline) * 2);
		}
	}
}

.webdav-url-input {
	margin-block-end: 0.5rem;
}
</style>
