<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<tr class="header">
		<th
			class="header__cell header__cell--avatar"
			data-cy-user-list-header-avatar
			scope="col">
			<span class="hidden-visually">
				{{ t('settings', 'Avatar') }}
			</span>
		</th>
		<th
			class="header__cell header__cell--displayname"
			data-cy-user-list-header-displayname
			scope="col">
			<strong>
				{{ t('settings', 'Display name') }}
			</strong>
			<UserColumnResizer
				column="displayname"
				:label="t('settings', 'Display name')"
				@resize="width => onColumnResize('displayname', width)"
				@resize-end="$emit('resize-column-end')"
				@reset="onColumnReset('displayname')" />
		</th>
		<th
			class="header__cell header__cell--username"
			data-cy-user-list-header-username
			scope="col">
			<span>
				{{ t('settings', 'Account name') }}
			</span>
			<UserColumnResizer
				column="username"
				:label="t('settings', 'Account name')"
				@resize="width => onColumnResize('username', width)"
				@resize-end="$emit('resize-column-end')"
				@reset="onColumnReset('username')" />
		</th>
		<th
			class="header__cell header__cell--email"
			data-cy-user-list-header-email
			scope="col">
			<span>{{ t('settings', 'Email') }}</span>
			<UserColumnResizer
				column="email"
				:label="t('settings', 'Email')"
				@resize="width => onColumnResize('email', width)"
				@resize-end="$emit('resize-column-end')"
				@reset="onColumnReset('email')" />
		</th>
		<th
			class="header__cell header__cell--groups"
			data-cy-user-list-header-groups
			scope="col">
			<span>{{ t('settings', 'Groups') }}</span>
			<UserColumnResizer
				column="groups"
				:label="t('settings', 'Groups')"
				@resize="width => onColumnResize('groups', width)"
				@resize-end="$emit('resize-column-end')"
				@reset="onColumnReset('groups')" />
		</th>
		<th
			v-if="settings.isAdmin || settings.isDelegatedAdmin"
			class="header__cell header__cell--large header__cell--subadmins"
			data-cy-user-list-header-subadmins
			scope="col">
			<span>{{ t('settings', 'Group admin for') }}</span>
			<UserColumnResizer
				column="subadmins"
				:label="t('settings', 'Group admin for')"
				@resize="width => onColumnResize('subadmins', width)"
				@resize-end="$emit('resize-column-end')"
				@reset="onColumnReset('subadmins')" />
		</th>
		<th
			class="header__cell header__cell--quota"
			data-cy-user-list-header-quota
			scope="col">
			<span>{{ t('settings', 'Quota') }}</span>
			<UserColumnResizer
				column="quota"
				:label="t('settings', 'Quota')"
				@resize="width => onColumnResize('quota', width)"
				@resize-end="$emit('resize-column-end')"
				@reset="onColumnReset('quota')" />
		</th>
		<th
			v-if="showConfig.showLanguages"
			class="header__cell header__cell--large header__cell--languages"
			data-cy-user-list-header-languages
			scope="col">
			<span>{{ t('settings', 'Language') }}</span>
			<UserColumnResizer
				column="languages"
				:label="t('settings', 'Language')"
				@resize="width => onColumnResize('languages', width)"
				@resize-end="$emit('resize-column-end')"
				@reset="onColumnReset('languages')" />
		</th>
		<th
			v-if="showConfig.showUserBackend || showConfig.showStoragePath"
			class="header__cell header__cell--large header__cell--storage"
			data-cy-user-list-header-storage-location
			scope="col">
			<span v-if="showConfig.showUserBackend">
				{{ t('settings', 'Account backend') }}
			</span>
			<span
				v-if="showConfig.showStoragePath"
				class="header__subtitle">
				{{ t('settings', 'Storage location') }}
			</span>
			<UserColumnResizer
				column="storage"
				:label="t('settings', 'Account backend')"
				@resize="width => onColumnResize('storage', width)"
				@resize-end="$emit('resize-column-end')"
				@reset="onColumnReset('storage')" />
		</th>
		<th
			v-if="showConfig.showFirstLogin"
			class="header__cell header__cell--first-login"
			data-cy-user-list-header-first-login
			scope="col">
			<span>{{ t('settings', 'First login') }}</span>
			<UserColumnResizer
				column="first-login"
				:label="t('settings', 'First login')"
				@resize="width => onColumnResize('first-login', width)"
				@resize-end="$emit('resize-column-end')"
				@reset="onColumnReset('first-login')" />
		</th>
		<th
			v-if="showConfig.showLastLogin"
			class="header__cell header__cell--last-login"
			data-cy-user-list-header-last-login
			scope="col">
			<span>{{ t('settings', 'Last login') }}</span>
			<UserColumnResizer
				column="last-login"
				:label="t('settings', 'Last login')"
				@resize="width => onColumnResize('last-login', width)"
				@resize-end="$emit('resize-column-end')"
				@reset="onColumnReset('last-login')" />
		</th>
		<th
			class="header__cell header__cell--large header__cell--fill"
			data-cy-user-list-header-manager
			scope="col">
			<!-- TRANSLATORS This string describes a manager in the context of an organization -->
			<span>{{ t('settings', 'Manager') }}</span>
		</th>
		<th
			class="header__cell header__cell--actions"
			data-cy-user-list-header-actions
			scope="col">
			<span class="hidden-visually">
				{{ t('settings', 'Account actions') }}
			</span>
		</th>
	</tr>
</template>

<script lang="ts">
import { translate as t } from '@nextcloud/l10n'
import Vue from 'vue'
import UserColumnResizer from './UserColumnResizer.vue'

export default Vue.extend({
	name: 'UserListHeader',

	components: {
		UserColumnResizer,
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
	},

	methods: {
		t,

		onColumnResize(column: string, width: number) {
			this.$emit('resize-column', column, width)
		},

		onColumnReset(column: string) {
			this.$emit('reset-column', column)
		},
	},
})
</script>

<style lang="scss" scoped>
@use './shared/styles.scss';

.header {
	border-bottom: 1px solid var(--color-border);

	// Positioning context for the column resize handles.
	// Declared before the shared styles so the sticky cells
	// (avatar, display name, actions) keep their position:
	// the modifiers have the same specificity and come later.
	&__cell {
		position: relative;
	}

	@include styles.row;
	@include styles.cell;
}
</style>
