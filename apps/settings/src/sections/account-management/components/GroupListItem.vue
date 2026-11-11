<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiAccountGroupOutline, mdiPencilOutline, mdiTrashCanOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, ref, useTemplateRef, watch } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCounterBubble from '@nextcloud/vue/components/NcCounterBubble'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcModal from '@nextcloud/vue/components/NcModal'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { groupRouteParam } from '../composables/useGroupsNavigation.ts'
import { useUsersStore } from '../store/users.ts'

const props = withDefaults(defineProps<{
	/** If this group is currently selected */
	active: boolean
	/** Number of members within this group */
	count?: number | null
	/** Identifier of this group */
	id: string
	/** Name of this group */
	name: string
}>(), {
	count: null,
})

const emit = defineEmits<{
	/** The group was picked */
	select: []
}>()

const usersStore = useUsersStore()

const listItem = useTemplateRef('listItem')
defineExpose({ listItem })

const loadingRenameGroup = ref(false)
const openGroupMenu = ref(false)
const showRemoveGroupModal = ref(false)
/** Name entered in the rename field */
const newName = ref(props.name)

const settings = computed(() => usersStore.serverData)
/** Admins and disabled accounts are no real groups and cannot be renamed or deleted */
const canEdit = computed(() => props.id !== 'admin' && props.id !== 'disabled' && (settings.value.isAdmin || settings.value.isDelegatedAdmin))

// drop a name that was entered but not submitted
watch(openGroupMenu, (open) => {
	if (!open && !loadingRenameGroup.value) {
		newName.value = props.name
	}
})
watch(() => props.name, (name) => {
	if (!openGroupMenu.value) {
		newName.value = name
	}
})

/**
 * Rename the group to the entered name
 */
async function renameGroup() {
	const displayName = newName.value.trim()
	if (props.id.trim() === '' || displayName === '') {
		return
	}

	try {
		loadingRenameGroup.value = true
		openGroupMenu.value = false
		await usersStore.renameGroup({
			groupid: props.id.trim(),
			displayName,
		})
	} catch {
		// the store already shows the error, the entered name stays for another try
		openGroupMenu.value = true
	} finally {
		loadingRenameGroup.value = false
	}
}

/**
 * Delete the group, the store shows an error if that fails
 */
async function removeGroup() {
	await usersStore.removeGroup(props.id)
	showRemoveGroupModal.value = false
}
</script>

<template>
	<NcModal
		v-if="showRemoveGroupModal"
		@close="showRemoveGroupModal = false">
		<div :class="$style.modalContent">
			<h2 :class="$style.modalHeader">
				{{ t('settings', 'Please confirm the group removal') }}
			</h2>
			<NcNoteCard
				type="warning"
				showAlert>
				{{ t('settings', 'You are about to delete the group "{group}". The accounts will NOT be deleted.', { group: name }) }}
			</NcNoteCard>
			<div :class="$style.modalButtonRow">
				<NcButton
					variant="secondary"
					@click="showRemoveGroupModal = false">
					{{ t('settings', 'Cancel') }}
				</NcButton>
				<NcButton
					variant="primary"
					@click="removeGroup">
					{{ t('settings', 'Confirm') }}
				</NcButton>
			</div>
		</div>
	</NcModal>

	<NcAppNavigationItem
		:key="id"
		ref="listItem"
		v-model:menuOpen="openGroupMenu"
		:name="name"
		:to="{ name: 'group', params: { selectedGroup: groupRouteParam(id) } }"
		:loading="loadingRenameGroup"
		@click="emit('select')">
		<template #icon>
			<NcIconSvgWrapper :path="mdiAccountGroupOutline" :size="20" />
		</template>
		<template #counter>
			<NcCounterBubble
				v-if="count"
				:type="active ? 'highlighted' : undefined"
				:count="count" />
		</template>
		<template v-if="canEdit" #actions>
			<NcActionInput
				v-model="newName"
				:trailingButtonLabel="t('settings', 'Submit')"
				type="text"
				:label="t('settings', 'Rename group')"
				@submit="renameGroup">
				<template #icon>
					<NcIconSvgWrapper :path="mdiPencilOutline" :size="20" />
				</template>
			</NcActionInput>
			<NcActionButton @click="showRemoveGroupModal = true">
				<template #icon>
					<NcIconSvgWrapper :path="mdiTrashCanOutline" :size="20" />
				</template>
				{{ t('settings', 'Delete group') }}
			</NcActionButton>
		</template>
	</NcAppNavigationItem>
</template>

<style module>
.modalHeader {
	margin: 0;
}

.modalContent {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: calc(5 * var(--default-grid-baseline));
	gap: var(--default-grid-baseline) 0;
}

.modalButtonRow {
	display: flex;
	width: 100%;
	justify-content: space-between;
}
</style>
