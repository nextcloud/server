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
	<NcModal class="modal"
		size="small"
		v-on="$listeners">
		<form class="modal__form"
			data-test="form"
			:disabled="loading.all"
			@submit.prevent="createUser">
			<h2>{{ t('settings', 'New user') }}</h2>
			<NcTextField ref="username"
				class="modal__item"
				data-test="username"
				:value.sync="newUser.id"
				:disabled="settings.newUserGenerateUserID"
				:label="usernameLabel"
				autocapitalize="none"
				autocomplete="off"
				spellcheck="false"
				pattern="[a-zA-Z0-9 _\.@\-']+"
				required />
			<NcTextField class="modal__item"
				data-test="displayName"
				:value.sync="newUser.displayName"
				:label="t('settings', 'Display name')"
				autocapitalize="none"
				autocomplete="off"
				spellcheck="false" />
			<span v-if="!settings.newUserRequireEmail"
				id="password-email-hint"
				class="modal__hint">
				{{ t('settings', 'Either password or email is required') }}
			</span>
			<NcPasswordField ref="password"
				class="modal__item"
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
			<NcTextField class="modal__item"
				data-test="email"
				type="email"
				:value.sync="newUser.mailAddress"
				aria-describedby="password-email-hint"
				:label="newUser.password === '' || settings.newUserRequireEmail ? t('settings', 'Email (required)') : t('settings', 'Email')"
				autocapitalize="none"
				autocomplete="off"
				spellcheck="false"
				:required="newUser.password === '' || settings.newUserRequireEmail" />
			<div class="modal__item">
				<!-- hidden input trick for vanilla html5 form validation -->
				<NcTextField v-if="!settings.isAdmin"
					id="new-user-groups-input"
					tabindex="-1"
					:class="{ 'icon-loading-small': loading.groups }"
					:value="newUser.groups"
					:required="!settings.isAdmin" />
				<label class="modal__label"
					for="new-user-groups">
					{{ !settings.isAdmin ? t('settings', 'Groups (required)') : t('settings', 'Groups') }}
				</label>
				<NcSelect class="modal__select"
					input-id="new-user-groups"
					:placeholder="t('settings', 'Set user groups')"
					:disabled="loading.groups || loading.all"
					:options="canAddGroups"
					:value="newUser.groups"
					label="name"
					:close-on-select="false"
					:multiple="true"
					:taggable="true"
					@input="handleGroupInput"
					@option:created="createGroup" />
					<!-- If user is not admin, he is a subadmin.
						Subadmins can't create users outside their groups
						Therefore, empty select is forbidden -->
			</div>
			<div v-if="subAdminsGroups.length > 0 && settings.isAdmin"
				class="modal__item">
				<label class="modal__label"
					for="new-user-sub-admin">
					{{ t('settings', 'Administered groups') }}
				</label>
				<NcSelect v-model="newUser.subAdminsGroups"
					class="modal__select"
					input-id="new-user-sub-admin"
					:placeholder="t('settings', 'Set user as admin for …')"
					:options="subAdminsGroups"
					:close-on-select="false"
					:multiple="true"
					label="name" />
			</div>
			<div class="modal__item">
				<label class="modal__label"
					for="new-user-quota">
					{{ t('settings', 'Quota') }}
				</label>
				<NcSelect v-model="newUser.quota"
					class="modal__select"
					input-id="new-user-quota"
					:placeholder="t('settings', 'Set user quota')"
					:options="quotaOptions"
					:clearable="false"
					:taggable="true"
					:create-option="validateQuota" />
			</div>
			<div v-if="showConfig.showLanguages"
				class="modal__item">
				<label class="modal__label"
					for="new-user-language">
					{{ t('settings', 'Language') }}
				</label>
				<NcSelect	v-model="newUser.language"
					class="modal__select"
					input-id="new-user-language"
					:placeholder="t('settings', 'Set default language')"
					:clearable="false"
					:selectable="option => !option.languages"
					:filter-by="languageFilterBy"
					:options="languages"
					label="name" />
			</div>
			<div :class="['modal__item managers', { 'icon-loading-small': loading.manager }]">
				<label class="modal__label"
					for="new-user-manager">
					<!-- TRANSLATORS This string describes a manager in the context of an organization -->
					{{ t('settings', 'Manager') }}
				</label>
				<NcSelect v-model="newUser.manager"
					class="modal__select"
					input-id="new-user-manager"
					:placeholder="managerLabel"
					:options="possibleManagers"
					:user-select="true"
					label="displayname"
					@search="searchUserManager" />
			</div>
			<NcButton class="modal__submit"
				data-test="submit"
				type="primary"
				native-type="submit">
				{{ t('settings', 'Add new user') }}
			</NcButton>
		</form>
	</NcModal>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

export default {
	name: 'NewUserModal',

	components: {
		NcButton,
		NcModal,
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
			managerLabel: t('settings', 'Set user manager'),
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
				return t('settings', 'Username will be autogenerated')
			}
			return t('settings', 'Username (required)')
		},

		minPasswordLength() {
			return this.$store.getters.getPasswordPolicyMinLength
		},

		groups() {
			// data provided php side + remove the disabled group
			return this.$store.getters.getGroups
				.filter(group => group.id !== 'disabled')
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
				this.$refs.username?.$refs?.inputField?.$refs?.input?.focus?.()
				this.$emit('close')
			} catch (error) {
				this.loading.all = false
				if (error.response && error.response.data && error.response.data.ocs && error.response.data.ocs.meta) {
					const statuscode = error.response.data.ocs.meta.statuscode
					if (statuscode === 102) {
						// wrong username
						this.$refs.username?.$refs?.inputField?.$refs?.input?.focus?.()
					} else if (statuscode === 107) {
						// wrong password
						this.$refs.password?.$refs?.inputField?.$refs?.input?.focus?.()
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
				quota = OC.Util.humanFileSize(OC.Util.computerFileSize(quota))
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
.modal {
	&__form {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 20px;
		gap: 4px 0;

		/* fake input for groups validation */
		#new-user-groups-input {
			position: absolute;
			opacity: 0;
			/* The "hidden" input is behind the NcSelect, so in general it does
			* not receives clicks. However, with Firefox, after the validation
			* fails, it will receive the first click done on it, so its width needs
			* to be set to 0 to prevent that ("pointer-events: none" does not
			* prevent it). */
			width: 0;
		}
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

	&__submit {
		margin-top: 20px;
	}
}
</style>
