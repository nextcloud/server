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
	<tr class="header">
		<th class="header__cell header__cell--avatar"
			data-cy-user-list-header-avatar
			scope="col">
			<span class="hidden-visually">
				{{ t('settings', 'Avatar') }}
			</span>
		</th>
		<th class="header__cell header__cell--displayname"
			data-cy-user-list-header-displayname
			scope="col">
			<strong>
				{{ t('settings', 'Display name') }}
			</strong>
			<span class="header__subtitle">
				{{ t('settings', 'Username') }}
			</span>
		</th>
		<th class="header__cell"
			:class="{ 'header__cell--obfuscated': hasObfuscated }"
			data-cy-user-list-header-password
			scope="col">
			<span>{{ passwordLabel }}</span>
		</th>
		<th class="header__cell"
			data-cy-user-list-header-email
			scope="col">
			<span>{{ t('settings', 'Email') }}</span>
		</th>
		<th class="header__cell header__cell--large"
			data-cy-user-list-header-groups
			scope="col">
			<span>{{ t('settings', 'Groups') }}</span>
		</th>
		<th v-if="subAdminsGroups.length > 0 && settings.isAdmin"
			class="header__cell header__cell--large"
			data-cy-user-list-header-subadmins
			scope="col">
			<span>{{ t('settings', 'Group admin for') }}</span>
		</th>
		<th class="header__cell"
			data-cy-user-list-header-quota
			scope="col">
			<span>{{ t('settings', 'Quota') }}</span>
		</th>
		<th v-if="showConfig.showLanguages"
			class="header__cell header__cell--large"
			data-cy-user-list-header-languages
			scope="col">
			<span>{{ t('settings', 'Language') }}</span>
		</th>
		<th v-if="showConfig.showUserBackend || showConfig.showStoragePath"
			class="header__cell header__cell--large"
			data-cy-user-list-header-storage-location
			scope="col">
			<span v-if="showConfig.showUserBackend">
				{{ t('settings', 'User backend') }}
			</span>
			<span v-if="showConfig.showStoragePath"
				class="header__subtitle">
				{{ t('settings', 'Storage location') }}
			</span>
		</th>
		<th v-if="showConfig.showLastLogin"
			class="header__cell"
			data-cy-user-list-header-last-login
			scope="col">
			<span>{{ t('settings', 'Last login') }}</span>
		</th>
		<th class="header__cell header__cell--large header__cell--fill"
			data-cy-user-list-header-manager
			scope="col">
			<!-- TRANSLATORS This string describes a manager in the context of an organization -->
			<span>{{ t('settings', 'Manager') }}</span>
		</th>
		<th class="header__cell header__cell--actions"
			data-cy-user-list-header-actions
			scope="col">
			<span class="hidden-visually">
				{{ t('settings', 'User actions') }}
			</span>
		</th>
	</tr>
</template>

<script lang="ts">
import Vue from 'vue'

import { translate as t } from '@nextcloud/l10n'

export default Vue.extend({
	name: 'UserListHeader',

	props: {
		hasObfuscated: {
			type: Boolean,
			required: true,
		},
	},

	computed: {
		showConfig() {
			// @ts-expect-error: allow untyped $store
			return this.$store.getters.getShowConfig
		},

		settings() {
			// @ts-expect-error: allow untyped $store
			return this.$store.getters.getServerData
		},

		subAdminsGroups() {
			// @ts-expect-error: allow untyped $store
			return this.$store.getters.getSubadminGroups
		},

		passwordLabel(): string {
			if (this.hasObfuscated) {
				// TRANSLATORS This string is for a column header labelling either a password or a message that the current user has insufficient permissions
				return t('settings', 'Password or insufficient permissions message')
			}
			return t('settings', 'Password')
		},
	},

	methods: {
		t,
	},
})
</script>

<style lang="scss" scoped>
@import './shared/styles.scss';

.header {
	@include row;
	@include cell;

	border-bottom: 1px solid var(--color-border);
}
</style>
