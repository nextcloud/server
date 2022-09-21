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
		:title="title"
		:to="{ name: 'group', params: { selectedGroup: encodeURIComponent(id) } }"
		icon="icon-group"
		:loading="loadingRenameGroup"
		:menu-open="openGroupMenu"
		@update:menuOpen="handleGroupMenuOpen">
		<template #counter>
			<NcCounterBubble v-if="count">
				{{ count }}
			</NcCounterBubble>
		</template>
		<template #actions>
			<NcActionInput v-if="id !== 'admin' && id !== 'disabled' && settings.isAdmin"
				ref="displayNameInput"
				icon="icon-edit"
				type="text"
				:value="title"
				@submit="renameGroup(id)">
				{{ t('settings', 'Rename group') }}
			</NcActionInput>
			<NcActionButton v-if="id !== 'admin' && id !== 'disabled' && settings.isAdmin"
				icon="icon-delete"
				@click="removeGroup(id)">
				{{ t('settings', 'Remove group') }}
			</NcActionButton>
		</template>
	</NcAppNavigationItem>
</template>

<script>
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton'
import NcCounterBubble from '@nextcloud/vue/dist/Components/NcCounterBubble'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem'

export default {
	name: 'GroupListItem',
	components: {
		NcActionInput,
		NcActionButton,
		NcCounterBubble,
		NcAppNavigationItem,
	},
	props: {
		id: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		count: {
			type: Number,
			required: false,
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
			const self = this
			// TODO migrate to a vue js confirm dialog component
			OC.dialogs.confirm(
				t('settings', 'You are about to remove the group {group}. The accounts will NOT be deleted.', { group: groupid }),
				t('settings', 'Please confirm the group removal '),
				function(success) {
					if (success) {
						self.$store.dispatch('removeGroup', groupid)
					}
				}
			)
		},
	},
}
</script>
