<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { INode } from '@nextcloud/files'
import type { OCSResponse } from '@nextcloud/typings/ocs'
import type { NcSelectUsersModel } from '@nextcloud/vue/components/NcSelectUsers'

import { mdiFolderOutline } from '@mdi/js'
import { getCurrentUser } from '@nextcloud/auth'
import axios, { isAxiosError } from '@nextcloud/axios'
import { getCapabilities } from '@nextcloud/capabilities'
import { FilePickerClosed, getFilePickerBuilder, showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { useDebounceFn } from '@vueuse/core'
import { computed, onBeforeMount, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcFormGroup from '@nextcloud/vue/components/NcFormGroup'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcSelectUsers from '@nextcloud/vue/components/NcSelectUsers'
import { logger } from '../utils/logger.ts'

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const minSearchStringLength = (getCapabilities() as any).files_sharing.sharee.minSearchStringLength
const picker = getFilePickerBuilder(t('files', 'Choose a file or folder to transfer'))
	.allowDirectories()
	.setMultiSelect(false)
	.setButtonFactory(([node]) => {
		const canPick = !!node?.path && node.path !== '/' && node.owner === getCurrentUser()!.uid
		return [{
			label: canPick
				? t('files', 'Transfer "{path}"', { path: node.displayname })
				: t('files', 'Select file or folder'),
			callback: () => {},
			disabled: !canPick,
			variant: 'primary',
		}]
	})
	.build()

const nodeForTransfer = ref<INode>()
const loadingUsers = ref(false)
const selectedUser = ref<NcSelectUsersModel>()
const userSuggestions = ref<NcSelectUsersModel[]>([])

const canSubmit = computed(() => !!nodeForTransfer.value && !!selectedUser.value)

const submitButtonText = computed(() => {
	if (!nodeForTransfer.value || !selectedUser.value) {
		return t('files', 'Transfer')
	}
	return t('files', 'Transfer {path} to {userid}', { path: nodeForTransfer.value!.displayname, userid: selectedUser.value!.displayName })
})

onBeforeMount(() => searchUsers(''))

/**
 * Open the file picker to choose a file or folder for which the ownership should be transferred.
 */
async function chooseNodeForTransfer() {
	try {
		const [node] = await picker.pickNodes()
		nodeForTransfer.value = node
	} catch (error) {
		if (error instanceof FilePickerClosed) {
			logger.debug('Selecting object for transfer aborted', { error })
			return
		}

		nodeForTransfer.value = undefined
		logger.error('Error while opening file picker for transfer ownership', { error })
		showError(t('files', 'Error while opening file picker for transfer ownership'))
		return
	}
}

const searchUsersDebounced = useDebounceFn(searchUsers, 500)

/**
 * Handle the user search input and fetch matching users from the server.
 *
 * @param query - The search string entered by the user
 */
async function searchUsers(query: string) {
	query = query.trim()
	if (query.length < minSearchStringLength) {
		return
	}

	loadingUsers.value = true
	try {
		const response = await axios.get<OCSResponse>(generateOcsUrl('apps/files_sharing/api/v1/sharees'), {
			params: {
				format: 'json',
				itemType: 'file',
				search: query,
				perPage: 20,
				lookup: false,
			},
		})

		const data = [...response.data.ocs.data.exact.users, ...response.data.ocs.data.users]
		userSuggestions.value = data.map((user) => ({
			displayName: user.label,
			id: user.value.shareWith,
			user: user.value.shareWith,
			subname: user.shareWithDisplayNameUnique,
		} as NcSelectUsersModel))
	} catch (error) {
		logger.error('could not fetch users', { error })
		showError(t('files', 'Error while searching for users'))
	} finally {
		loadingUsers.value = false
	}
}

/**
 * Handle submit of the ownership transfer.
 */
async function submit() {
	if (!canSubmit.value) {
		logger.warn('ignoring form submit')
	}

	const requestParameters = {
		path: nodeForTransfer.value?.path,
		recipient: selectedUser.value?.user,
	}
	logger.debug('submit transfer ownership form', { requestParameters })

	try {
		const url = generateOcsUrl('apps/files/api/v1/transferownership')
		const { data } = await axios.post(url, requestParameters)
		logger.info('Transfer ownership request sent', { data })

		nodeForTransfer.value = undefined
		selectedUser.value = undefined
		showSuccess(t('files', 'Ownership transfer request sent'))
	} catch (error) {
		logger.error('Could not send ownership transfer request', { error })

		if (isAxiosError(error) && error.response?.status === 403) {
			showError(t('files', 'Cannot transfer ownership of a file or folder you do not own'))
		} else {
			showError(t('files', 'Error while sending ownership transfer request'))
		}
	}
}
</script>

<template>
	<form @submit.prevent="submit">
		<NcFormGroup
			:class="$style.transferOwnership__group"
			:label="t('files', 'Transfer ownership of a file or folder')">
			<NcFormBox v-slot="{ itemClass }">
				<NcFormBoxButton
					inverted-accent
					:label="t('files', 'File or folder to transfer')"
					:description="nodeForTransfer?.displayname ?? t('files', 'No file or folder selected')"
					@click="chooseNodeForTransfer">
					<template #icon>
						<NcIconSvgWrapper :path="mdiFolderOutline" />
					</template>
				</NcFormBoxButton>

				<div :class="[itemClass, $style.transferOwnership__newOwner]">
					<NcSelectUsers
						v-model="selectedUser"
						:class="$style.transferOwnership__newOwnerSelect"
						:input-label="t('files', 'New owner')"
						:loading="loadingUsers"
						:options="userSuggestions"
						@search="searchUsersDebounced" />
				</div>

				<NcButton
					:disabled="!canSubmit"
					type="submit"
					variant="primary"
					wide>
					{{ submitButtonText }}
				</NcButton>
			</NcFormBox>

			<p class="hidden-visually" aria-live="polite">
				<span v-if="!nodeForTransfer">{{ t('files', 'You need to select a file or folder to transfer ownership.') }}</span>
				<span v-if="!selectedUser">{{ t('files', 'You need to select a new owner for the file or folder.') }}</span>
			</p>
		</NcFormGroup>
	</form>
</template>

<style module>
.transferOwnership__group {
	max-width: 512px;
}

.transferOwnership__newOwner {
	background-color: var(--color-primary-element-light);
	padding-block: var(--default-grid-baseline);
	padding-inline: calc(var(--border-radius-element) + var(--default-grid-baseline));
}

.transferOwnership__newOwnerSelect {
	width: 100%;
}

.transferOwnership__newOwner :global(.vs--open .vs__dropdown-toggle) {
	background-color: var(--color-main-background);
}
</style>
