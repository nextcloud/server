<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { watchDebounced } from '@vueuse/core'
import { ref, watch } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSelectGroup from '@nextcloud/vue/components/NcSettingsSelectGroup'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import SelectSharingPermissions from './SelectSharingPermissions.vue'
import logger from '../../../logger.ts'
import { useSharingSettings } from '../composables/useSharingSettings.ts'
import { deleteCoreConfigValue, setCoreConfigValue } from '../services/appConfig.ts'

const settings = useSharingSettings()

const disclaimerText = ref(settings.publicShareDisclaimerText)
const isDisclaimerEnabled = ref(disclaimerText.value !== '')
watch(isDisclaimerEnabled, (enabled) => {
	if (!enabled) {
		disclaimerText.value = ''
	}
})
watchDebounced(disclaimerText, saveDisclaimer, { debounce: 500 })

/**
 * Store the disclaimer text of public link upload pages, an empty text removes it.
 *
 * @param text - The new disclaimer text
 */
async function saveDisclaimer(text: string) {
	try {
		if (text === '') {
			await deleteCoreConfigValue('shareapi_public_link_disclaimertext')
			showSuccess(t('settings', 'Deleted disclaimer text'))
		} else {
			await setCoreConfigValue('shareapi_public_link_disclaimertext', text)
			showSuccess(t('settings', 'Changed disclaimer text'))
		}
	} catch (error) {
		logger.error('Could not set the public share disclaimer', { error })
		showError(t('settings', 'Could not set disclaimer text'))
	}
}
</script>

<template>
	<form class="sharing">
		<NcCheckboxRadioSwitch
			v-model="settings.enabled"
			aria-controls="settings-sharing-api settings-sharing-api-settings settings-sharing-default-permissions settings-sharing-privacy-related"
			type="switch">
			{{ t('settings', 'Allow apps to use the Share API') }}
		</NcCheckboxRadioSwitch>

		<div v-show="settings.enabled" id="settings-sharing-api-settings" class="sharing__sub-section">
			<NcCheckboxRadioSwitch v-model="settings.allowResharing">
				{{ t('settings', 'Allow resharing') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch v-model="settings.allowGroupSharing">
				{{ t('settings', 'Allow sharing with groups') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch v-model="settings.onlyShareWithGroupMembers">
				{{ t('settings', 'Restrict users to only share with users in their groups') }}
			</NcCheckboxRadioSwitch>
			<div v-show="settings.onlyShareWithGroupMembers" id="settings-sharing-api-excluded-groups" class="sharing__labeled-entry sharing__input">
				<span aria-hidden="true">{{ t('settings', 'Ignore the following groups when checking group membership') }}</span>
				<NcSettingsSelectGroup
					v-model="settings.onlyShareWithGroupMembersExcludeGroupList"
					:label="t('settings', 'Ignore the following groups when checking group membership')" />
			</div>
			<NcCheckboxRadioSwitch v-model="settings.allowViewWithoutDownload">
				{{ t('settings', 'Allow users to preview files even if download is disabled') }}
			</NcCheckboxRadioSwitch>
			<NcNoteCard
				v-show="settings.allowViewWithoutDownload"
				id="settings-sharing-api-view-without-download-hint"
				class="sharing__note"
				type="warning">
				{{ t('settings', 'Users will still be able to screenshot or record the screen. This does not provide any definitive protection.') }}
			</NcNoteCard>
		</div>

		<div v-show="settings.enabled" id="settings-sharing-api" class="sharing__section">
			<NcCheckboxRadioSwitch
				v-model="settings.allowLinks"
				type="switch"
				aria-controls="settings-sharing-api-public-link">
				{{ t('settings', 'Allow users to share via link and emails') }}
			</NcCheckboxRadioSwitch>
			<fieldset v-show="settings.allowLinks" id="settings-sharing-api-public-link" class="sharing__sub-section">
				<NcCheckboxRadioSwitch v-model="settings.allowPublicUpload">
					{{ t('settings', 'Allow public uploads') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch v-model="settings.allowFederationOnPublicShares">
					{{ t('settings', 'Allow public shares to be added to other clouds by federation.') }}
					{{ t('settings', 'This will add share permissions to all newly created link shares.') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch v-model="settings.enableLinkPasswordByDefault">
					{{ t('settings', 'Always ask for a password') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch
					v-show="settings.enableLinkPasswordByDefault"
					v-model="settings.enforceLinksPassword">
					{{ t('settings', 'Enforce password protection') }}
				</NcCheckboxRadioSwitch>
				<div
					v-if="settings.enforceLinksPasswordExcludedGroupsEnabled"
					v-show="settings.enableLinkPasswordByDefault"
					class="sharing__labeled-entry sharing__input">
					<span aria-hidden="true">{{ t('settings', 'Exclude groups from password requirements') }}</span>
					<NcSettingsSelectGroup
						v-model="settings.enforceLinksPasswordExcludedGroups"
						:label="t('settings', 'Exclude groups from password requirements')"
						:disabled="!settings.enforceLinksPassword" />
				</div>
				<div class="sharing__labeled-entry sharing__input">
					<span aria-hidden="true">{{ t('settings', 'Exclude groups from creating link shares') }}</span>
					<NcSettingsSelectGroup
						v-model="settings.allowLinksExcludeGroups"
						:label="t('settings', 'Exclude groups from creating link shares')" />
				</div>
			</fieldset>

			<NcCheckboxRadioSwitch
				v-model="settings.allowCustomTokens"
				type="switch"
				aria-describedby="settings-sharing-custom-token-disable-hint settings-sharing-custom-token-access-hint">
				{{ t('settings', 'Allow users to set custom share link tokens') }}
			</NcCheckboxRadioSwitch>
			<div class="sharing__sub-section">
				<NcNoteCard
					id="settings-sharing-custom-token-disable-hint"
					class="sharing__note"
					type="info">
					{{ t('settings', 'Shares with custom tokens will continue to be accessible after this setting has been disabled') }}
				</NcNoteCard>
				<NcNoteCard
					id="settings-sharing-custom-token-access-hint"
					class="sharing__note"
					type="warning">
					{{ t('settings', 'Shares with guessable tokens may be accessed easily') }}
				</NcNoteCard>
			</div>

			<fieldset>
				<legend>{{ t('settings', 'Limit sharing based on groups') }}</legend>
				<div class="sharing__sub-section">
					<NcCheckboxRadioSwitch
						v-model="settings.excludeGroups"
						name="excludeGroups"
						value="no"
						type="radio">
						{{ t('settings', 'Allow sharing for everyone (default)') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						v-model="settings.excludeGroups"
						name="excludeGroups"
						value="yes"
						type="radio">
						{{ t('settings', 'Exclude some groups (block list)') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						v-model="settings.excludeGroups"
						name="excludeGroups"
						value="allow"
						type="radio">
						{{ t('settings', 'Allow some groups only (allow list)') }}
					</NcCheckboxRadioSwitch>
					<div v-show="settings.excludeGroups !== 'no'" class="sharing__labeled-entry sharing__input">
						<NcSettingsSelectGroup
							v-model="settings.excludeGroupsList"
							aria-describedby="settings-sharing-excluded-groups-desc"
							:label="settings.excludeGroups === 'allow' ? t('settings', 'Groups allowed to share') : t('settings', 'Groups excluded from sharing')"
							:disabled="settings.excludeGroups === 'no'" />
						<em id="settings-sharing-excluded-groups-desc">
							<template v-if="settings.excludeGroups === 'allow'">
								{{ t('settings', 'Only members of at least one of these groups will be able to initiate shares.') }}
								{{ t('settings', 'Others will be blocked, but are still able to receive shares.') }}
							</template>
							<template v-else>
								{{ t('settings', 'A member of at least one of these groups will not be able to initiate new shares, but will still be able to receive shares.') }}
							</template>
						</em>
					</div>
				</div>
			</fieldset>

			<NcCheckboxRadioSwitch
				v-model="settings.defaultInternalExpireDate"
				type="switch"
				aria-controls="settings-sharing-api-expiration">
				{{ t('settings', 'Set default expiration date for internal shares') }}
			</NcCheckboxRadioSwitch>
			<fieldset v-show="settings.defaultInternalExpireDate" id="settings-sharing-api-expiration" class="sharing__sub-section">
				<NcCheckboxRadioSwitch v-model="settings.enforceInternalExpireDate">
					{{ t('settings', 'Enforce expiration date') }}
				</NcCheckboxRadioSwitch>
				<NcTextField
					v-model="settings.internalExpireAfterNDays"
					type="number"
					class="sharing__input"
					:label="t('settings', 'Default expiration time of new shares in days')"
					:placeholder="t('settings', 'Expire shares after x days')" />
			</fieldset>

			<NcCheckboxRadioSwitch
				v-model="settings.defaultRemoteExpireDate"
				type="switch"
				aria-controls="settings-sharing-remote-api-expiration">
				{{ t('settings', 'Set default expiration date for shares to other servers') }}
			</NcCheckboxRadioSwitch>
			<fieldset v-show="settings.defaultRemoteExpireDate" id="settings-sharing-remote-api-expiration" class="sharing__sub-section">
				<NcCheckboxRadioSwitch v-model="settings.enforceRemoteExpireDate">
					{{ t('settings', 'Enforce expiration date for remote shares') }}
				</NcCheckboxRadioSwitch>
				<NcTextField
					v-model="settings.remoteExpireAfterNDays"
					type="number"
					class="sharing__input"
					:label="t('settings', 'Default expiration time of remote shares in days')"
					:placeholder="t('settings', 'Expire remote shares after x days')" />
			</fieldset>

			<NcCheckboxRadioSwitch
				v-model="settings.defaultExpireDate"
				type="switch"
				aria-controls="settings-sharing-api-api-expiration"
				:disabled="!settings.allowLinks">
				{{ t('settings', 'Set default expiration date for shares via link or mail') }}
			</NcCheckboxRadioSwitch>
			<fieldset v-show="settings.allowLinks && settings.defaultExpireDate" id="settings-sharing-api-api-expiration" class="sharing__sub-section">
				<NcCheckboxRadioSwitch v-model="settings.enforceExpireDate">
					{{ t('settings', 'Enforce expiration date for link or mail shares') }}
				</NcCheckboxRadioSwitch>
				<NcTextField
					v-model="settings.expireAfterNDays"
					type="number"
					class="sharing__input"
					:label="t('settings', 'Default expiration time of shares in days')"
					:placeholder="t('settings', 'Expire shares after x days')" />
			</fieldset>
		</div>

		<div v-show="settings.enabled" id="settings-sharing-privacy-related" class="sharing__section">
			<h3>{{ t('settings', 'Privacy settings for sharing') }}</h3>

			<NcCheckboxRadioSwitch
				v-model="settings.allowShareDialogUserEnumeration"
				type="switch"
				aria-controls="settings-sharing-privacy-user-enumeration">
				{{ t('settings', 'Allow account name autocompletion in share dialog and allow access to the system address book') }}
			</NcCheckboxRadioSwitch>
			<fieldset v-show="settings.allowShareDialogUserEnumeration" id="settings-sharing-privacy-user-enumeration" class="sharing__sub-section">
				<legend class="hidden-visually">
					{{ t('settings', 'Sharing autocompletion restrictions') }}
				</legend>
				<em>
					{{ t('settings', 'If autocompletion restrictions for both "same group" and "phonebook integration" are enabled, a match in either is enough to show the user.') }}
				</em>
				<NcCheckboxRadioSwitch v-model="settings.restrictUserEnumerationToGroup">
					{{ t('settings', 'Restrict account name autocompletion and system address book access to users within the same groups') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch v-model="settings.restrictUserEnumerationToPhone">
					{{ t('settings', 'Restrict account name autocompletion to users based on their phonebook') }}
				</NcCheckboxRadioSwitch>
			</fieldset>

			<NcCheckboxRadioSwitch
				v-model="settings.restrictUserEnumerationFullMatch"
				type="switch"
				aria-controls="settings-sharing-privacy-autocomplete">
				{{ t('settings', 'Allow autocompletion to full match when entering the full name (ignoring restrictions like group membership or missing phonebook match)') }}
			</NcCheckboxRadioSwitch>
			<fieldset v-show="settings.restrictUserEnumerationFullMatch" id="settings-sharing-privacy-autocomplete" class="sharing__sub-section">
				<legend class="hidden-visually">
					{{ t('settings', 'Full match autocompletion restrictions') }}
				</legend>
				<NcCheckboxRadioSwitch v-model="settings.restrictUserEnumerationFullMatchUserId">
					{{ t('settings', 'Also allow autocompletion on full match of the user ID') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch v-model="settings.restrictUserEnumerationFullMatchDisplayname">
					{{ t('settings', 'Also allow autocompletion on full match of the display name') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch v-model="settings.restrictUserEnumerationFullMatchEmail">
					{{ t('settings', 'Also allow autocompletion on full match of the user email') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch v-model="settings.restrictUserEnumerationFullMatchIgnoreSecondDN">
					{{ t('settings', 'Do not use second user displayname for full match') }}
				</NcCheckboxRadioSwitch>
			</fieldset>

			<NcCheckboxRadioSwitch v-model="isDisclaimerEnabled" type="switch">
				{{ t('settings', 'Show disclaimer text on the public link upload page (only shown when the file list is hidden)') }}
			</NcCheckboxRadioSwitch>
			<div v-if="isDisclaimerEnabled" class="sharing__sub-section">
				<NcTextArea
					v-model="disclaimerText"
					class="sharing__input"
					:label="t('settings', 'Disclaimer text')"
					aria-describedby="settings-sharing-privacy-related-disclaimer-hint" />
				<em id="settings-sharing-privacy-related-disclaimer-hint" class="sharing__input">
					{{ t('settings', 'This text will be shown on the public link upload page when the file list is hidden.') }}
				</em>
			</div>
		</div>

		<div id="settings-sharing-default-permissions" class="sharing__section">
			<h3>{{ t('settings', 'Default share permissions') }}</h3>
			<SelectSharingPermissions v-model="settings.defaultPermissions" />
		</div>
	</form>
</template>

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

	& &__note {
		margin: 2px 0;
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
