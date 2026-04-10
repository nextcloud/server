<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcModal @close="$emit('close')">
		<div class="nesting-modal">
			<h2 class="nesting-modal__header">
				{{ t('settings', 'Nested groups for "{group}"', { group: groupName }) }}
			</h2>

			<section class="nesting-modal__section">
				<h3>{{ t('settings', 'Subgroups') }}</h3>
				<p class="nesting-modal__hint">
					{{ t('settings', 'Members of a subgroup are treated as effective members of this group for shares, permissions and app restrictions.') }}
				</p>

				<ul v-if="subGroups.length" class="nesting-modal__list">
					<li v-for="child in subGroups" :key="'sub-' + child">
						<span>{{ child }}</span>
						<NcButton variant="tertiary" @click="removeSubGroup(child)">
							<template #icon>
								<Delete :size="16" />
							</template>
							{{ t('settings', 'Remove') }}
						</NcButton>
					</li>
				</ul>
				<p v-else class="nesting-modal__empty">
					{{ t('settings', 'No direct subgroups.') }}
				</p>

				<div class="nesting-modal__form">
					<NcSelect
						class="nesting-modal__picker"
						:input-label="t('settings', 'Add subgroup')"
						:placeholder="t('settings', 'Search for a group…')"
						:options="subGroupOptions"
						:loading="subGroupLoading"
						:model-value="pendingSubGroup"
						label="name"
						@search="onSearchSubGroup"
						@update:model-value="onPickSubGroup" />
				</div>
			</section>

			<section class="nesting-modal__section">
				<h3>{{ t('settings', 'Admin groups') }}</h3>
				<p class="nesting-modal__hint">
					{{ t('settings', 'Members of an admin group gain sub-admin rights over this group and all of its subgroups.') }}
				</p>

				<ul v-if="adminGroups.length" class="nesting-modal__list">
					<li v-for="admin in adminGroups" :key="'admin-' + admin">
						<span>{{ admin }}</span>
						<NcButton variant="tertiary" @click="removeAdminGroup(admin)">
							<template #icon>
								<Delete :size="16" />
							</template>
							{{ t('settings', 'Remove') }}
						</NcButton>
					</li>
				</ul>
				<p v-else class="nesting-modal__empty">
					{{ t('settings', 'No admin groups.') }}
				</p>

				<div class="nesting-modal__form">
					<NcSelect
						class="nesting-modal__picker"
						:input-label="t('settings', 'Add admin group')"
						:placeholder="t('settings', 'Search for a group…')"
						:options="adminGroupOptions"
						:loading="adminGroupLoading"
						:model-value="pendingAdminGroup"
						label="name"
						@search="onSearchAdminGroup"
						@update:model-value="onPickAdminGroup" />
				</div>
			</section>
		</div>
	</NcModal>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcModal from '@nextcloud/vue/components/NcModal'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import Delete from 'vue-material-design-icons/TrashCanOutline.vue'
import { searchGroups } from '../service/groups.ts'

export default {
	name: 'GroupNestingModal',
	components: {
		Delete,
		NcButton,
		NcModal,
		NcSelect,
	},

	props: {
		groupId: {
			type: String,
			required: true,
		},

		groupName: {
			type: String,
			required: true,
		},
	},

	emits: ['close'],

	data() {
		return {
			subGroups: [],
			adminGroups: [],
			subGroupOptions: [],
			adminGroupOptions: [],
			subGroupLoading: false,
			adminGroupLoading: false,
			pendingSubGroup: null,
			pendingAdminGroup: null,
			searchPromise: null,
		}
	},

	async mounted() {
		await Promise.all([this.refreshSubGroups(), this.refreshAdminGroups()])
	},

	methods: {
		async refreshSubGroups() {
			try {
				this.subGroups = await this.$store.dispatch('fetchSubGroups', this.groupId)
			} catch {
				showError(t('settings', 'Failed to load subgroups'))
			}
		},

		async refreshAdminGroups() {
			try {
				this.adminGroups = await this.$store.dispatch('fetchGroupSubAdmins', this.groupId)
			} catch {
				showError(t('settings', 'Failed to load admin groups'))
			}
		},

		async doSearch(query, target) {
			// Shared autocomplete for both pickers; excludes self and already-added entries.
			const excludes = target === 'sub'
				? new Set([this.groupId, ...this.subGroups])
				: new Set([this.groupId, ...this.adminGroups])
			const toggleLoading = target === 'sub'
				? (v) => { this.subGroupLoading = v }
				: (v) => { this.adminGroupLoading = v }
			toggleLoading(true)
			try {
				if (this.searchPromise) {
					this.searchPromise.cancel()
				}
				this.searchPromise = searchGroups({ search: query ?? '', offset: 0, limit: 25 })
				const results = await this.searchPromise
				const filtered = results.filter((g) => !excludes.has(g.id))
				if (target === 'sub') {
					this.subGroupOptions = filtered
				} else {
					this.adminGroupOptions = filtered
				}
			} catch {
				// cancelation or network error -- leave options alone
			} finally {
				toggleLoading(false)
			}
		},

		onSearchSubGroup(query) {
			this.doSearch(query, 'sub')
		},

		onSearchAdminGroup(query) {
			this.doSearch(query, 'admin')
		},

		async onPickSubGroup(group) {
			if (!group) {
				return
			}
			try {
				await this.$store.dispatch('addSubGroup', {
					gid: this.groupId,
					subGroupId: group.id,
				})
				showSuccess(t('settings', 'Added subgroup "{name}"', { name: group.name ?? group.id }))
				this.pendingSubGroup = null
				this.subGroupOptions = []
				await this.refreshSubGroups()
			} catch (e) {
				const message = e?.response?.data?.ocs?.meta?.message
				showError(message || t('settings', 'Failed to add subgroup (cycle or missing group?)'))
			}
		},

		async removeSubGroup(childId) {
			try {
				await this.$store.dispatch('removeSubGroup', {
					gid: this.groupId,
					subGroupId: childId,
				})
				await this.refreshSubGroups()
			} catch {
				showError(t('settings', 'Failed to remove subgroup'))
			}
		},

		async onPickAdminGroup(group) {
			if (!group) {
				return
			}
			try {
				await this.$store.dispatch('addGroupSubAdmin', {
					gid: this.groupId,
					adminGroupId: group.id,
				})
				showSuccess(t('settings', 'Added admin group "{name}"', { name: group.name ?? group.id }))
				this.pendingAdminGroup = null
				this.adminGroupOptions = []
				await this.refreshAdminGroups()
			} catch {
				showError(t('settings', 'Failed to add admin group'))
			}
		},

		async removeAdminGroup(adminId) {
			try {
				await this.$store.dispatch('removeGroupSubAdmin', {
					gid: this.groupId,
					adminGroupId: adminId,
				})
				await this.refreshAdminGroups()
			} catch {
				showError(t('settings', 'Failed to remove admin group'))
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.nesting-modal {
	display: flex;
	flex-direction: column;
	padding: 20px;
	gap: 16px;
	min-width: 480px;

	&__header {
		margin: 0;
	}

	&__section {
		display: flex;
		flex-direction: column;
		gap: 8px;
	}

	&__hint {
		opacity: 0.8;
		font-size: 0.9em;
	}

	&__list {
		list-style: none;
		padding: 0;
		margin: 0;

		li {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 4px 0;
		}
	}

	&__empty {
		opacity: 0.6;
		font-style: italic;
	}

	&__form {
		display: flex;
		align-items: flex-end;
		gap: 8px;
	}

	&__picker {
		flex: 1;
	}
}
</style>
