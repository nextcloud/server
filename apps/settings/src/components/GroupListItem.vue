<!--
  - @copyright Copyright (c) 2021 Martin Jänel <spammemore@posteo.de>
  -
  - @author Martin Jänel <spammemore@posteo.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<NcAppNavigationItem :key="id"
		:exact="true"
		:name="name"
		:to="{ name: 'group', params: { selectedGroup: encodeURIComponent(id) } }"
		:loading="loadingRenameGroup"
		:menu-open="openGroupMenu"
		@update:menuOpen="handleGroupMenuOpen">
		<template #icon>
			<AccountGroup :size="20" />
		</template>
		<template #counter>
			<NcCounterBubble v-if="count"
				:type="active ? 'highlighted' : undefined">
				{{ count }}
			</NcCounterBubble>
		</template>
		<template #actions>
			<NcActionInput v-if="id !== 'admin' && id !== 'disabled' && settings.isAdmin"
				ref="displayNameInput"
				icon="icon-edit"
				:trailing-button-label="t('settings', 'Submit')"
				type="text"
				:value="name"
				:label=" t('settings', 'Rename group')"
				@submit="renameGroup(id)" />
			<NcActionButton v-if="id !== 'admin' && id !== 'disabled' && settings.isAdmin"
				icon="icon-delete"
				@click="removeGroup(id)">
				{{ t('settings', 'Remove group') }}
			</NcActionButton>
		</template>
	</NcAppNavigationItem>
</template>

<script>
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcCounterBubble from '@nextcloud/vue/dist/Components/NcCounterBubble.js'

import AccountGroup from 'vue-material-design-icons/AccountGroup.vue'

export default {
	name: 'GroupListItem',
	components: {
		AccountGroup,
		NcActionButton,
		NcActionInput,
		NcAppNavigationItem,
		NcCounterBubble,
	},
	props: {
		/**
		 * If this group is currently selected
		 */
		active: {
			type: Boolean,
			required: true,
		},
		/**
		 * Number of members within this group
		 */
		count: {
			type: Number,
			default: null,
		},
		/**
		 * Identifier of this group
		 */
		id: {
			type: String,
			required: true,
		},
		/**
		 * Name of this group
		 */
		name: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			loadingRenameGroup: false,
			openGroupMenu: false,
		}
	},
	computed: {
		settings() {
			return this.$store.getters.getServerData
		},
	},
	methods: {
		handleGroupMenuOpen() {
			this.openGroupMenu = true
		},
		async renameGroup(gid) {
			// check if group id is valid
			if (gid.trim() === '') {
				return
			}

			const displayName = this.$refs.displayNameInput.$el.querySelector('input[type="text"]').value

			// check if group name is valid
			if (displayName.trim() === '') {
				return
			}

			try {
				this.openGroupMenu = false
				this.loadingRenameGroup = true
				await this.$store.dispatch('renameGroup', {
					groupid: gid.trim(),
					displayName: displayName.trim(),
				})

				this.loadingRenameGroup = false
			} catch {
				this.openGroupMenu = true
				this.loadingRenameGroup = false
			}
		},
		removeGroup(groupid) {
			// TODO migrate to a vue js confirm dialog component
			OC.dialogs.confirm(
				t('settings', 'You are about to remove the group {group}. The users will NOT be deleted.', { group: groupid }),
				t('settings', 'Please confirm the group removal '),
				(success) => {
					if (success) {
						this.$store.dispatch('removeGroup', groupid)
					}
				},
			)
		},
	},
}
</script>
