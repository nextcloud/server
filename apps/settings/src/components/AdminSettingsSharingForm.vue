<!--
	- @copyright 2023 Ferdinand Thiessen <opensource@fthiessen.de>
	-
	- @author Ferdinand Thiessen <opensource@fthiessen.de>
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
	<form class="sharing">
		<NcCheckboxRadioSwitch aria-controls="settings-sharing-api settings-sharing-api-settings settings-sharing-default-permissions settings-sharing-privary-related"
			type="switch"
			:checked.sync="settings.enabled">
			{{ t('settings', 'Allow apps to use the Share API') }}
		</NcCheckboxRadioSwitch>

		<div v-show="settings.enabled" id="settings-sharing-api-settings" class="sharing__sub-section">
			<NcCheckboxRadioSwitch :checked.sync="settings.allowResharing">
				{{ t('settings', 'Allow resharing') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch :checked.sync="settings.allowGroupSharing">
				{{ t('settings', 'Allow sharing with groups') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch :checked.sync="settings.onlyShareWithGroupMembers">
				{{ t('settings', 'Restrict users to only share with users in their groups') }}
			</NcCheckboxRadioSwitch>
			<div v-show="settings.onlyShareWithGroupMembers" id="settings-sharing-api-excluded-groups" class="sharing__labeled-entry sharing__input">
				<label for="settings-sharing-only-group-members-excluded-groups">{{ t('settings', 'Ignore the following groups when checking group membership') }}</label>
				<NcSettingsSelectGroup id="settings-sharing-only-group-members-excluded-groups"
					v-model="settings.onlyShareWithGroupMembersExcludeGroupList"
					:label="t('settings', 'Ignore the following groups when checking group membership')"
					style="width: 100%" />
			</div>
		</div>

		<div v-show="settings.enabled" id="settings-sharing-api" class="sharing__section">
			<NcCheckboxRadioSwitch type="switch"
				aria-controls="settings-sharing-api-public-link"
				:checked.sync="settings.allowLinks">
				{{ t('settings', 'Allow users to share via link and emails') }}
			</NcCheckboxRadioSwitch>
			<fieldset v-show="settings.allowLinks" id="settings-sharing-api-public-link" class="sharing__sub-section">
				<NcCheckboxRadioSwitch :checked.sync="settings.allowPublicUpload">
					{{ t('settings', 'Allow public uploads') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch :checked.sync="settings.enableLinkPasswordByDefault">
					{{ t('settings', 'Always ask for a password') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch :checked.sync="settings.enforceLinksPassword" :disabled="!settings.enableLinkPasswordByDefault">
					{{ t('settings', 'Enforce password protection') }}
				</NcCheckboxRadioSwitch>
				<label v-if="settings.passwordExcludedGroupsFeatureEnabled" class="sharing__labeled-entry sharing__input">
					<span>{{ t('settings', 'Exclude groups from password requirements') }}</span>
					<NcSettingsSelectGroup v-model="settings.passwordExcludedGroups"
						style="width: 100%"
						:disabled="!settings.enforceLinksPassword || !settings.enableLinkPasswordByDefault" />
				</label>
				<label class="sharing__labeled-entry sharing__input">
					<span>{{ t('settings', 'Exclude groups from creating link shares') }}</span>
					<NcSettingsSelectGroup v-model="settings.allowLinksExcludeGroups"
						:label="t('settings', 'Exclude groups from creating link shares')"
						style="width: 100%" />
				</label>
			</fieldset>

			<NcCheckboxRadioSwitch type="switch" :checked.sync="settings.excludeGroups">
				{{ t('settings', 'Exclude groups from sharing') }}
			</NcCheckboxRadioSwitch>
			<div v-show="settings.excludeGroups" class="sharing__sub-section">
				<div class="sharing__labeled-entry sharing__input">
					<label for="settings-sharing-excluded-groups">{{ t('settings', 'Groups excluded from sharing') }}</label>
					<NcSettingsSelectGroup id="settings-sharing-excluded-groups"
						v-model="settings.excludeGroupsList"
						aria-describedby="settings-sharing-excluded-groups-desc"
						:label="t('settings', 'Groups excluded from sharing')"
						:disabled="!settings.excludeGroups"
						style="width: 100%" />
					<em id="settings-sharing-excluded-groups-desc">{{ t('settings', 'These groups will still be able to receive shares, but not to initiate them.') }}</em>
				</div>
			</div>

			<NcCheckboxRadioSwitch type="switch"
				aria-controls="settings-sharing-api-expiration"
				:checked.sync="settings.defaultInternalExpireDate">
				{{ t('settings', 'Set default expiration date for shares') }}
			</NcCheckboxRadioSwitch>
			<fieldset v-show="settings.defaultInternalExpireDate" id="settings-sharing-api-expiration" class="sharing__sub-section">
				<NcCheckboxRadioSwitch :checked.sync="settings.enforceInternalExpireDate">
					{{ t('settings', 'Enforce expiration date') }}
				</NcCheckboxRadioSwitch>
				<NcTextField type="number"
					class="sharing__input"
					:label="t('settings', 'Default expiration time of new shares in days')"
					:placeholder="t('settings', 'Expire shares after x days')"
					:value.sync="settings.internalExpireAfterNDays" />
			</fieldset>

			<NcCheckboxRadioSwitch type="switch"
				aria-controls="settings-sharing-remote-api-expiration"
				:checked.sync="settings.defaultRemoteExpireDate">
				{{ t('settings', 'Set default expiration date for shares to other servers') }}
			</NcCheckboxRadioSwitch>
			<fieldset v-show="settings.defaultRemoteExpireDate" id="settings-sharing-remote-api-expiration" class="sharing__sub-section">
				<NcCheckboxRadioSwitch :checked.sync="settings.enforceRemoteExpireDate">
					{{ t('settings', 'Enforce expiration date for remote shares') }}
				</NcCheckboxRadioSwitch>
				<NcTextField type="number"
					class="sharing__input"
					:label="t('settings', 'Default expiration time of remote shares in days')"
					:placeholder="t('settings', 'Expire remote shares after x days')"
					:value.sync="settings.remoteExpireAfterNDays" />
			</fieldset>

			<NcCheckboxRadioSwitch type="switch"
				aria-controls="settings-sharing-api-api-expiration"
				:checked.sync="settings.defaultExpireDate"
				:disabled="!settings.allowLinks">
				{{ t('settings', 'Set default expiration date for shares via link or mail') }}
			</NcCheckboxRadioSwitch>
			<fieldset v-show="settings.allowLinks && settings.defaultExpireDate" id="settings-sharing-api-api-expiration" class="sharing__sub-section">
				<NcCheckboxRadioSwitch :checked.sync="settings.enforceExpireDate">
					{{ t('settings', 'Enforce expiration date for remote shares') }}
				</NcCheckboxRadioSwitch>
				<NcTextField type="number"
					class="sharing__input"
					:label="t('settings', 'Default expiration time of shares in days')"
					:placeholder="t('settings', 'Expire shares after x days')"
					:value.sync="settings.expireAfterNDays" />
			</fieldset>
		</div>

		<div v-show="settings.enabled" id="settings-sharing-privary-related" class="sharing__section">
			<h3>{{ t('settings', 'Privacy settings for sharing') }}</h3>

			<NcCheckboxRadioSwitch type="switch"
				aria-controls="settings-sharing-privacy-user-enumeration"
				:checked.sync="settings.allowShareDialogUserEnumeration">
				{{ t('settings', 'Allow username autocompletion in share dialog and allow access to the system address book') }}
			</NcCheckboxRadioSwitch>
			<fieldset v-show="settings.allowShareDialogUserEnumeration" id="settings-sharing-privacy-user-enumeration" class="sharing__sub-section">
				<em>
					{{ t('settings', 'If autocompletion "same group" and "phone number integration" are enabled a match in either is enough to show the user.') }}
				</em>
				<NcCheckboxRadioSwitch :checked.sync="settings.restrictUserEnumerationToGroup">
					{{ t('settings', 'Allow username autocompletion to users within the same groups and limit system address books to users in the same groups') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch :checked.sync="settings.restrictUserEnumerationToPhone">
					{{ t('settings', 'Allow username autocompletion to users based on phone number integration') }}
				</NcCheckboxRadioSwitch>
			</fieldset>

			<NcCheckboxRadioSwitch type="switch" :checked.sync="settings.restrictUserEnumerationFullMatch">
				{{ t('settings', 'Allow autocompletion when entering the full name or email address (ignoring missing phonebook match and being in the same group)') }}
			</NcCheckboxRadioSwitch>

			<NcCheckboxRadioSwitch type="switch" :checked.sync="publicShareDisclaimerEnabled">
				{{ t('settings', 'Show disclaimer text on the public link upload page (only shown when the file list is hidden)') }}
			</NcCheckboxRadioSwitch>
			<div v-if="typeof settings.publicShareDisclaimerText === 'string'"
				aria-describedby="settings-sharing-privary-related-disclaimer-hint"
				class="sharing__sub-section">
				<NcTextArea class="sharing__input"
					:label="t('settings', 'Disclaimer text')"
					aria-describedby="settings-sharing-privary-related-disclaimer-hint"
					:value="settings.publicShareDisclaimerText"
					@update:value="onUpdateDisclaimer" />
				<em id="settings-sharing-privary-related-disclaimer-hint" class="sharing__input">
					{{ t('settings', 'This text will be shown on the public link upload page when the file list is hidden.') }}
				</em>
			</div>
		</div>

		<div id="settings-sharing-default-permissions" class="sharing__section">
			<h3>{{ t('settings', 'Default share permissions') }}</h3>
			<SelectSharingPermissions :value.sync="settings.defaultPermissions" />
		</div>
	</form>
</template>

<script lang="ts">
import {
	NcCheckboxRadioSwitch,
	NcSettingsSelectGroup,
	NcTextArea,
	NcTextField,
} from '@nextcloud/vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { loadState } from '@nextcloud/initial-state'
import { defineComponent } from 'vue'

import SelectSharingPermissions from './SelectSharingPermissions.vue'
import { snakeCase, debounce } from 'lodash'

interface IShareSettings {
	enabled: boolean
	allowGroupSharing: boolean
	allowLinks: boolean
	allowLinksExcludeGroups: unknown
	allowPublicUpload: boolean
	allowResharing: boolean
	allowShareDialogUserEnumeration: boolean
	restrictUserEnumerationToGroup: boolean
	restrictUserEnumerationToPhone: boolean
	restrictUserEnumerationFullMatch: boolean
	restrictUserEnumerationFullMatchUserId: boolean
	restrictUserEnumerationFullMatchEmail: boolean
	restrictUserEnumerationFullMatchIgnoreSecondDN: boolean
	enforceLinksPassword: boolean
	passwordExcludedGroups: string[]
	passwordExcludedGroupsFeatureEnabled: boolean
	onlyShareWithGroupMembers: boolean
	onlyShareWithGroupMembersExcludeGroupList: string[]
	defaultExpireDate: boolean
	expireAfterNDays: string
	enforceExpireDate: boolean
	excludeGroups: boolean
	excludeGroupsList: string[]
	publicShareDisclaimerText?: string
	enableLinkPasswordByDefault: boolean
	defaultPermissions: number
	defaultInternalExpireDate: boolean
	internalExpireAfterNDays: string
	enforceInternalExpireDate: boolean
	defaultRemoteExpireDate: boolean
	remoteExpireAfterNDays: string
	enforceRemoteExpireDate: boolean
}

export default defineComponent({
	name: 'AdminSettingsSharingForm',
	components: {
		NcCheckboxRadioSwitch,
		NcSettingsSelectGroup,
		NcTextArea,
		NcTextField,
		SelectSharingPermissions,
	},
	data() {
		return {
			settingsData: loadState<IShareSettings>('settings', 'sharingSettings'),
		}
	},
	computed: {
		settings() {
			console.warn('new proxy')
			return new Proxy(this.settingsData, {
				get(target, property) {
					return target[property]
				},
				set(target, property: string, newValue) {
					const configName = `shareapi_${snakeCase(property)}`
					const value = typeof newValue === 'boolean' ? (newValue ? 'yes' : 'no') : (typeof newValue === 'string' ? newValue : JSON.stringify(newValue))
					window.OCP.AppConfig.setValue('core', configName, value)
					target[property] = newValue
					return true
				},
			})
		},
		publicShareDisclaimerEnabled: {
			get() {
				return typeof this.settingsData.publicShareDisclaimerText === 'string'
			},
			set(value) {
				if (value) {
					this.settingsData.publicShareDisclaimerText = ''
				} else {
					this.onUpdateDisclaimer()
				}
			},
		},
	},
	methods: {
		t,

		onUpdateDisclaimer: debounce(function(value?: string) {
			const options = {
				success() {
					if (value) {
						showSuccess(t('settings', 'Changed disclaimer text'))
					} else {
						showSuccess(t('settings', 'Deleted disclaimer text'))
					}
				},
				error() {
					showError(t('settings', 'Could not set disclaimer text'))
				},
			}
			if (!value) {
				window.OCP.AppConfig.deleteKey('core', 'shareapi_public_link_disclaimertext', options)
			} else {
				window.OCP.AppConfig.setValue('core', 'shareapi_public_link_disclaimertext', value, options)
			}
			this.settingsData.publicShareDisclaimerText = value
		}, 500) as (v?: string) => void,
	},
})
</script>

<style scoped lang="scss">
.sharing {
	display: flex;
	flex-direction: column;
	gap: 12px;

	&__labeled-entry {
		display: flex;
		flex: 1 0;
		flex-direction: column;
		gap: 4px;
	}

	&__section {
		display: flex;
		flex-direction: column;
		gap: 4px;
		margin-block-end: 12px
	}

	&__sub-section {
		display: flex;
		flex-direction: column;
		gap: 4px;

		margin-inline-start: 44px;
		margin-block-end: 12px
	}

	&__input {
		max-width: 500px;
		// align with checkboxes
		margin-inline-start: 14px;

		:deep(.v-select.select) {
			width: 100%;
		}
	}
}

@media only screen and (max-width: 350px) {
	// ensure no overflow happens on small devices (required for WCAG)
	.sharing {
		&__sub-section {
			margin-inline-start: 14px;
		}
	}
}
</style>
