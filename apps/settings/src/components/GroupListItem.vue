<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Fragment>
		<NcModal v-if="showRemoveGroupModal"
			@close="showRemoveGroupModal = false">
			<div class="modal__content">
				<h2 class="modal__header">
					{{ t('settings', 'Please confirm the group removal') }}
				</h2>
				<NcNoteCard type="warning"
					show-alert>
					{{ t('settings', 'You are about to remove the group "{group}". The accounts will NOT be deleted.', { group: name }) }}
				</NcNoteCard>
				<div class="modal__button-row">
					<NcButton type="secondary"
						@click="showRemoveGroupModal = false">
						{{ t('settings', 'Cancel') }}
					</NcButton>
					<NcButton type="primary"
						@click="removeGroup">
						{{ t('settings', 'Confirm') }}
					</NcButton>
				</div>
			</div>
		</NcModal>

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
				<NcActionInput v-if="id !== 'admin' && id !== 'disabled' && (settings.isAdmin || settings.isDelegatedAdmin)"
					ref="displayNameInput"
					:trailing-button-label="t('settings', 'Submit')"
					type="text"
					:value="name"
					:label=" t('settings', 'Rename group')"
					@submit="renameGroup(id)">
					<template #icon>
						<Pencil :size="20" />
					</template>
				</NcActionInput>
				<NcActionButton v-if="id !== 'admin' && id !== 'disabled' && (settings.isAdmin || settings.isDelegatedAdmin)"
					@click="showRemoveGroupModal = true">
					<template #icon>
						<Delete :size="20" />
					</template>
					{{ t('settings', 'Remove group') }}
				</NcActionButton>
			</template>
		</NcAppNavigationItem>
	</Fragment>
</template>

<script>
import { Fragment } from 'vue-frag'

import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCounterBubble from '@nextcloud/vue/dist/Components/NcCounterBubble.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

import AccountGroup from 'vue-material-design-icons/AccountGroup.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'

import { showError } from '@nextcloud/dialogs'

export default {
	name: 'GroupListItem',
	components: {
		AccountGroup,
		Delete,
		Fragment,
		NcActionButton,
		NcActionInput,
		NcAppNavigationItem,
		NcButton,
		NcCounterBubble,
		NcModal,
		NcNoteCard,
		Pencil,
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
			showRemoveGroupModal: false,
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
		async removeGroup() {
			try {
				await this.$store.dispatch('removeGroup', this.id)
				this.showRemoveGroupModal = false
			} catch (error) {
				showError(t('settings', 'Failed to remove group "{group}"', { group: this.name }))
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.modal {
	&__header {
		margin: 0;
	}

	&__content {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 20px;
		gap: 4px 0;
	}

	&__button-row {
		display: flex;
		width: 100%;
		justify-content: space-between;
	}
}
</style>
