<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSettingsSection
		:name="t('settings', 'Two-Factor Authentication')"
		:description="t('settings', 'Two-factor authentication can be enforced for all accounts and specific groups. If they do not have a two-factor provider configured, they will be unable to log into the system.')"
		:doc-url="twoFactorAdminDoc">
		<p v-if="loading">
			<span class="icon-loading-small two-factor-loading" />
			<span>{{ t('settings', 'Enforce two-factor authentication') }}</span>
		</p>
		<NcCheckboxRadioSwitch
			v-else
			id="two-factor-enforced"
			v-model="enforced"
			type="switch">
			{{ t('settings', 'Enforce two-factor authentication') }}
		</NcCheckboxRadioSwitch>
		<template v-if="enforced">
			<h3>{{ t('settings', 'Limit to groups') }}</h3>
			{{ t('settings', 'Enforcement of two-factor authentication can be set for certain groups only.') }}
			<p class="top-margin">
				{{ t('settings', 'Two-factor authentication is enforced for all members of the following groups.') }}
			</p>
			<p>
				<label for="enforcedGroups">
					<span>{{ t('settings', 'Enforced groups') }}</span>
				</label>
				<NcSelect
					v-model="enforcedGroups"
					input-id="enforcedGroups"
					label="displayname"
					:options="groups"
					:disabled="loading"
					:multiple="true"
					:loading="loadingGroups"
					keep-open
					@search="searchGroup" />
			</p>
			<p class="top-margin">
				{{ t('settings', 'Two-factor authentication is not enforced for members of the following groups.') }}
			</p>
			<p>
				<label for="excludedGroups">
					<span>{{ t('settings', 'Excluded groups') }}</span>
				</label>
				<NcSelect
					v-model="excludedGroups"
					input-id="excludedGroups"
					label="displayname"
					:options="groups"
					:disabled="loading"
					:multiple="true"
					:loading="loadingGroups"
					keep-open
					@search="searchGroup" />
			</p>
			<p class="top-margin">
				<em>
					<!-- this text is also found in the documentation. update it there as well if it ever changes -->
					{{ t('settings', 'When groups are selected/excluded, they use the following logic to determine if an account has 2FA enforced: If no groups are selected, 2FA is enabled for everyone except members of the excluded groups. If groups are selected, 2FA is enabled for all members of these. If an account is both in a selected and excluded group, the selected takes precedence and 2FA is enforced.') }}
				</em>
			</p>
		</template>
		<p class="top-margin">
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

<script>
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { PwdConfirmationMode } from '@nextcloud/password-confirmation'
import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import debounce from 'lodash/debounce.js'
import uniq from 'lodash/uniq.js'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import logger from '../logger.ts'

export default {
	name: 'AdminTwoFactor',
	components: {
		NcSelect,
		NcButton,
		NcCheckboxRadioSwitch,
		NcSettingsSection,
	},

	data() {
		return {
			loading: false,
			dirty: false,
			groups: [],
			loadingGroups: false,
			twoFactorAdminDoc: loadState('settings', 'two-factor-admin-doc'),
		}
	},

	computed: {
		enforced: {
			get() {
				return this.$store.state.enforced
			},

			set(val) {
				this.dirty = true
				this.$store.commit('setEnforced', val)
			},
		},

		// enforcedGroups/excludedGroups store plain group IDs in Vuex, but NcSelect
		// needs {id, displayname} objects (matching :options="groups") to render
		// anything but the raw ID.
		enforcedGroups: {
			get() {
				return this.$store.state.enforcedGroups.map((id) => this.resolveGroup(id))
			},

			set(val) {
				this.dirty = true
				this.$store.commit('setEnforcedGroups', val.map((group) => group.id))
			},
		},

		excludedGroups: {
			get() {
				return this.$store.state.excludedGroups.map((id) => this.resolveGroup(id))
			},

			set(val) {
				this.dirty = true
				this.$store.commit('setExcludedGroups', val.map((group) => group.id))
			},
		},
	},

	async mounted() {
		// Populate the groups with a first set so the dropdown is not empty
		// when opening the page the first time
		await this.fetchGroups('')

		// The first set above is capped and may not include every already
		// enforced/excluded group, so those wouldn't otherwise resolve to a
		// display name - or be selectable at all, since NcSelect can only
		// show options present in :options="groups".
		const selectedIds = uniq(this.$store.state.enforcedGroups.concat(this.$store.state.excludedGroups))
		const missingIds = selectedIds.filter((id) => !this.groups.some((group) => group.id === id))
		await Promise.all(missingIds.map((id) => this.fetchGroups(id)))
	},

	methods: {
		resolveGroup(id) {
			return this.groups.find((group) => group.id === id) || { id, displayname: id }
		},

		async fetchGroups(query) {
			this.loadingGroups = true
			try {
				const res = await axios.get(generateOcsUrl('cloud/groups/details?offset=0&search={query}&limit=20', { query }))
				const fetched = res.data.ocs.data.groups.map(({ id, displayname }) => ({ id, displayname }))
				const merged = new Map(this.groups.map((group) => [group.id, group]))
				fetched.forEach((group) => merged.set(group.id, group))
				this.groups = [...merged.values()]
			} catch (error) {
				logger.error('could not search groups', { error })
			} finally {
				this.loadingGroups = false
			}
		},

		searchGroup: debounce(function(query) {
			this.fetchGroups(query)
		}, 500),

		saveChanges() {
			this.loading = true

			const data = {
				enforced: this.enforced,
				enforcedGroups: this.$store.state.enforcedGroups,
				excludedGroups: this.$store.state.excludedGroups,
			}
			axios.put(generateUrl('/settings/api/admin/twofactorauth'), data, { confirmPassword: PwdConfirmationMode.Strict })
				.then((resp) => resp.data)
				.then((state) => {
					this.state = state
					this.dirty = false
				})
				.catch((error) => {
					logger.error('could not save changes', { error })
				})
				.then(() => { this.loading = false })
		},
	},
}
</script>

<style scoped>
	.two-factor-loading {
		display: inline-block;
		vertical-align: sub;
		margin-inline: -2px 1px;
	}

	.top-margin {
		margin-top: 0.5rem;
	}
</style>
