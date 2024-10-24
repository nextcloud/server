<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog class="dialog"
		size="small"
		:name="t('settings', 'New account')"
		out-transition
		v-on="$listeners">
		<form id="new-user-form"
			class="dialog__form"
			data-test="form"
			:disabled="loading.all"
			@submit.prevent="createUser">
			<NcTextField ref="username"
				class="dialog__item"
				data-test="username"
				:value.sync="newUser.id"
				:disabled="settings.newUserGenerateUserID"
				:label="usernameLabel"
				autocapitalize="none"
				autocomplete="off"
				spellcheck="false"
				pattern="[a-zA-Z0-9 _\.@\-']+"
				required />
			<NcTextField class="dialog__item"
				data-test="displayName"
				:value.sync="newUser.displayName"
				:label="t('settings', 'Display name')"
				autocapitalize="none"
				autocomplete="off"
				spellcheck="false" />
			<span v-if="!settings.newUserRequireEmail"
				id="password-email-hint"
				class="dialog__hint">
				{{ t('settings', 'Either password or email is required') }}
			</span>
			<NcPasswordField ref="password"
				class="dialog__item"
				data-test="password"
				:value.sync="newUser.password"
				:minlength="minPasswordLength"
				:maxlength="469"
				aria-describedby="password-email-hint"
				:label="newUser.mailAddress === '' ? t('settings', 'Password (required)') : t('settings', 'Password')"
				autocapitalize="none"
				autocomplete="new-password"
				spellcheck="false"
				:required="newUser.mailAddress === ''" />
			<NcTextField class="dialog__item"
				data-test="email"
				type="email"
				:value.sync="newUser.mailAddress"
				aria-describedby="password-email-hint"
				:label="newUser.password === '' || settings.newUserRequireEmail ? t('settings', 'Email (required)') : t('settings', 'Email')"
				autocapitalize="none"
				autocomplete="off"
				spellcheck="false"
				:required="newUser.password === '' || settings.newUserRequireEmail" />
			<div class="dialog__item">
				<NcSelect class="dialog__select"
					:input-label="!settings.isAdmin && !settings.isDelegatedAdmin ? t('settings', 'Member of the following groups (required)') : t('settings', 'Member of the following groups')"
					:placeholder="t('settings', 'Set account groups')"
					:disabled="loading.groups || loading.all"
					:options="canAddGroups"
					:value="newUser.groups"
					label="name"
					:close-on-select="false"
					:multiple="true"
					:taggable="true"
					:required="!settings.isAdmin && !settings.isDelegatedAdmin"
					@input="handleGroupInput"
					@option:created="createGroup" />
					<!-- If user is not admin, they are a subadmin.
						Subadmins can't create users outside their groups
						Therefore, empty select is forbidden -->
			</div>
			<div v-if="subAdminsGroups.length > 0"
				class="dialog__item">
				<NcSelect v-model="newUser.subAdminsGroups"
					class="dialog__select"
					:input-label="t('settings', 'Admin of the following groups')"
					:placeholder="t('settings', 'Set account as admin for …')"
					:options="subAdminsGroups"
					:close-on-select="false"
					:multiple="true"
					label="name" />
			</div>
			<div class="dialog__item">
				<NcSelect v-model="newUser.quota"
					class="dialog__select"
					:input-label="t('settings', 'Quota')"
					:placeholder="t('settings', 'Set account quota')"
					:options="quotaOptions"
					:clearable="false"
					:taggable="true"
					:create-option="validateQuota" />
			</div>
			<div v-if="showConfig.showLanguages"
				class="dialog__item">
				<NcSelect v-model="newUser.language"
					class="dialog__select"
					:input-label="t('settings', 'Language')"
					:placeholder="t('settings', 'Set default language')"
					:clearable="false"
					:selectable="option => !option.languages"
					:filter-by="languageFilterBy"
					:options="languages"
					label="name" />
			</div>
			<div :class="['dialog__item dialog__managers', { 'icon-loading-small': loading.manager }]">
				<NcSelect v-model="newUser.manager"
					class="dialog__select"
					:input-label="managerInputLabel"
					:placeholder="managerLabel"
					:options="possibleManagers"
					:user-select="true"
					label="displayname"
					@search="searchUserManager" />
			</div>
		</form>

		<template #actions>
			<NcButton class="dialog__submit"
				data-test="submit"
				form="new-user-form"
				type="primary"
				native-type="submit">
				{{ t('settings', 'Add new account') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { formatFileSize, parseFileSize } from '@nextcloud/files'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

export default {
	name: 'NewUserDialog',

	components: {
		NcButton,
		NcDialog,
		NcPasswordField,
		NcSelect,
		NcTextField,
	},

	props: {
		loading: {
			type: Object,
			required: true,
		},

		newUser: {
			type: Object,
			required: true,
		},

		quotaOptions: {
			type: Array,
			required: true,
		},
	},

	data() {
		return {
			possibleManagers: [],
			// TRANSLATORS This string describes a manager in the context of an organization
			managerInputLabel: t('settings', 'Manager'),
			// TRANSLATORS This string describes a manager in the context of an organization
			managerLabel: t('settings', 'Set line manager'),
		}
	},

	computed: {
		showConfig() {
			return this.$store.getters.getShowConfig
		},

		settings() {
			return this.$store.getters.getServerData
		},

		usernameLabel() {
			if (this.settings.newUserGenerateUserID) {
				return t('settings', 'Account name will be autogenerated')
			}
			return t('settings', 'Account name (required)')
		},

		minPasswordLength() {
			return this.$store.getters.getPasswordPolicyMinLength
		},

		groups() {
			// data provided php side + remove the recent and disabled groups
			return this.$store.getters.getGroups
				.filter(group => group.id !== '__nc_internal_recent' && group.id !== 'disabled')
				.sort((a, b) => a.name.localeCompare(b.name))
		},

		subAdminsGroups() {
			// data provided php side
			return this.$store.getters.getSubadminGroups
		},

		canAddGroups() {
			// disabled if no permission to add new users to group
			return this.groups.map(group => {
				// clone object because we don't want
				// to edit the original groups
				group = Object.assign({}, group)
				group.$isDisabled = group.canAdd === false
				return group
			})
		},

		languages() {
			return [
				{
					name: t('settings', 'Common languages'),
					languages: this.settings.languages.commonLanguages,
				},
				...this.settings.languages.commonLanguages,
				{
					name: t('settings', 'Other languages'),
					languages: this.settings.languages.otherLanguages,
				},
				...this.settings.languages.otherLanguages,
			]
		},
	},

	async beforeMount() {
		await this.searchUserManager()
	},

	mounted() {
		this.$refs.username?.focus?.()
	},

	methods: {
		async createUser() {
			this.loading.all = true
			try {
				await this.$store.dispatch('addUser', {
					userid: this.newUser.id,
					password: this.newUser.password,
					displayName: this.newUser.displayName,
					email: this.newUser.mailAddress,
					groups: this.newUser.groups.map(group => group.id),
					subadmin: this.newUser.subAdminsGroups.map(group => group.id),
					quota: this.newUser.quota.id,
					language: this.newUser.language.code,
					manager: this.newUser.manager.id,
				})

				this.$emit('reset')
				this.$refs.username?.focus?.()
				this.$emit('closing')
			} catch (error) {
				this.loading.all = false
				if (error.response && error.response.data && error.response.data.ocs && error.response.data.ocs.meta) {
					const statuscode = error.response.data.ocs.meta.statuscode
					if (statuscode === 102) {
						// wrong username
						this.$refs.username?.focus?.()
					} else if (statuscode === 107) {
						// wrong password
						this.$refs.password?.focus?.()
					}
				}
			}
		},

		handleGroupInput(groups) {
			/**
			 * Filter out groups with no id to prevent duplicate selected options
			 *
			 * Created groups are added programmatically by `createGroup()`
			 */
			 this.newUser.groups = groups.filter(group => Boolean(group.id))
		},

		/**
		 * Create a new group
		 *
		 * @param {any} group Group
		 * @param {string} group.name Group id
		 */
		async createGroup({ name: gid }) {
			this.loading.groups = true
			try {
				await this.$store.dispatch('addGroup', gid)
				this.newUser.groups.push(this.groups.find(group => group.id === gid))
				this.loading.groups = false
			} catch (error) {
				this.loading.groups = false
			}
		},

		/**
		 * Validate quota string to make sure it's a valid human file size
		 *
		 * @param {string} quota Quota in readable format '5 GB'
		 * @return {object}
		 */
		validateQuota(quota) {
			// only used for new presets sent through @Tag
			const validQuota = OC.Util.computerFileSize(quota)
			if (validQuota !== null && validQuota >= 0) {
				// unify format output
				quota = formatFileSize(parseFileSize(quota))
				this.newUser.quota = { id: quota, label: quota }
				return this.newUser.quota
			}
			// Default is unlimited
			this.newUser.quota = this.quotaOptions[0]
			return this.quotaOptions[0]
		},

		languageFilterBy(option, label, search) {
			// Show group header of the language
			if (option.languages) {
				return option.languages.some(
					({ name }) => name.toLocaleLowerCase().includes(search.toLocaleLowerCase()),
				)
			}

			return (label || '').toLocaleLowerCase().includes(search.toLocaleLowerCase())
		},

		async searchUserManager(query) {
			await this.$store.dispatch(
				'searchUsers',
				{
					offset: 0,
					limit: 10,
					search: query,
				},
			).then(response => {
				const users = response?.data ? Object.values(response?.data.ocs.data.users) : []
				if (users.length > 0) {
					this.possibleManagers = users
				}
			})
		},
	},
}
</script>

<style lang="scss" scoped>
.dialog {
	&__form {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 0 8px;
		gap: 4px 0;
	}

	&__item {
		width: 100%;

		&:not(:focus):not(:active) {
			border-color: var(--color-border-dark);
		}
	}

	&__hint {
		color: var(--color-text-maxcontrast);
		margin-top: 8px;
		align-self: flex-start;
	}

	&__label {
		display: block;
		padding: 4px 0;
	}

	&__select {
		width: 100%;
	}

	&__managers {
		margin-bottom: 12px;
	}

	&__submit {
		margin-top: 4px;
		margin-bottom: 8px;
	}

	:deep {
		.dialog__actions {
			margin: auto;
		}
	}
}
</style>
