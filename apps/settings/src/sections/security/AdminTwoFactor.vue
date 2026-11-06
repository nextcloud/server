<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { PwdConfirmationMode } from '@nextcloud/password-confirmation'
import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import { useDebounceFn } from '@vueuse/core'
import { ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import logger from '../../logger.ts'

interface TwoFactorState {
	enforced: boolean
	enforcedGroups: string[]
	excludedGroups: string[]
}

const twoFactorAdminDoc = loadState<string>('settings', 'two-factor-admin-doc')
const twoFactorState = ref(loadState<TwoFactorState>('settings', 'mandatory2FAState'))

const loading = ref(false)
const dirty = ref(false)
const loadingGroups = ref(false)
// Groups are loaded dynamically, but the assigned ones *should*
// be valid groups, so let's add them as initial state
const groups = ref(unique(twoFactorState.value.enforcedGroups, twoFactorState.value.excludedGroups))

watch(twoFactorState, () => {
	dirty.value = true
}, { deep: true })

const searchGroup = useDebounceFn(async (query: string) => {
	loadingGroups.value = true
	try {
		const { data } = await axios.get(generateOcsUrl('cloud/groups?offset=0&search={query}&limit=20', { query }))
		groups.value = unique(groups.value, data.ocs.data.groups)
	} catch (error) {
		logger.error('could not search groups', { error })
	} finally {
		loadingGroups.value = false
	}
}, 500)

// Populate the groups with a first set so the dropdown is not empty
// when opening the page the first time
searchGroup('')

/**
 * @param lists Group lists to merge
 */
function unique(...lists: string[][]): string[] {
	return [...new Set(lists.flat())]
}

/**
 * Store the enforcement state, keeps the form dirty if that fails
 */
async function saveChanges() {
	loading.value = true
	try {
		await axios.put(generateUrl('/settings/api/admin/twofactorauth'), twoFactorState.value, { confirmPassword: PwdConfirmationMode.Strict })
		dirty.value = false
	} catch (error) {
		logger.error('could not save changes', { error })
		showError(t('settings', 'Could not save the two-factor authentication settings'))
	} finally {
		loading.value = false
	}
}
</script>

<template>
	<NcSettingsSection
		:name="t('settings', 'Two-Factor Authentication')"
		:description="t('settings', 'Two-factor authentication can be enforced for all accounts and specific groups. If they do not have a two-factor provider configured, they will be unable to log into the system.')"
		:docUrl="twoFactorAdminDoc">
		<NcCheckboxRadioSwitch
			id="two-factor-enforced"
			v-model="twoFactorState.enforced"
			:loading="loading"
			:disabled="loading"
			type="switch">
			{{ t('settings', 'Enforce two-factor authentication') }}
		</NcCheckboxRadioSwitch>
		<template v-if="twoFactorState.enforced">
			<h3>{{ t('settings', 'Limit to groups') }}</h3>
			{{ t('settings', 'Enforcement of two-factor authentication can be set for certain groups only.') }}
			<p :class="$style.topMargin">
				{{ t('settings', 'Two-factor authentication is enforced for all members of the following groups.') }}
			</p>
			<NcSelect
				v-model="twoFactorState.enforcedGroups"
				inputId="enforcedGroups"
				:inputLabel="t('settings', 'Enforced groups')"
				:options="groups"
				:disabled="loading"
				multiple
				:loading="loadingGroups"
				keepOpen
				@search="searchGroup" />
			<p :class="$style.topMargin">
				{{ t('settings', 'Two-factor authentication is not enforced for members of the following groups.') }}
			</p>
			<NcSelect
				v-model="twoFactorState.excludedGroups"
				inputId="excludedGroups"
				:inputLabel="t('settings', 'Excluded groups')"
				:options="groups"
				:disabled="loading"
				multiple
				:loading="loadingGroups"
				keepOpen
				@search="searchGroup" />
			<p :class="$style.topMargin">
				<em>
					<!-- this text is also found in the documentation. update it there as well if it ever changes -->
					{{ t('settings', 'When groups are selected/excluded, they use the following logic to determine if an account has 2FA enforced: If no groups are selected, 2FA is enabled for everyone except members of the excluded groups. If groups are selected, 2FA is enabled for all members of these. If an account is both in a selected and excluded group, the selected takes precedence and 2FA is enforced.') }}
				</em>
			</p>
		</template>
		<p :class="$style.topMargin">
			<NcButton
				v-if="dirty"
				variant="primary"
				:disabled="loading"
				@click="saveChanges">
				{{ t('settings', 'Save changes') }}
			</NcButton>
		</p>
	</NcSettingsSection>
</template>

<style module>
.topMargin {
	margin-block-start: 0.5rem;
}
</style>
