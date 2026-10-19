<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IDialogButton } from '@nextcloud/dialogs'
import type { IToken, ITokenResponse } from '../store/authtoken.ts'

import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import AuthTokenAccessPicker from './AuthTokenAccessPicker.vue'
import logger from '../../logger.ts'
import { useAuthTokenStore } from '../store/authtoken.ts'
import { fromScopes, sameItems, toScopes } from '../utils/accessLevel.ts'

/** Whether the dialog is open */
const open = defineModel<boolean>('open', { required: true })

const props = defineProps<{
	/** Token to edit, missing to create a new one */
	token?: IToken
}>()

const emit = defineEmits<{
	/** A new app password was created */
	created: [response: ITokenResponse]
}>()

const authTokenStore = useAuthTokenStore()

const initial = fromScopes(props.token?.scope.scopes)
const initialScopes = toScopes(initial.selection, initial.kept)

const name = ref('')
const saving = ref(false)
const selection = ref(initial.selection)

const scopes = computed(() => toScopes(selection.value, initial.kept))

const canSave = computed(() => {
	if (selection.value.preset === 'custom' && selection.value.grants.length === 0) {
		return false
	}
	if (props.token) {
		return scopes.value === null || initialScopes === null
			? scopes.value !== initialScopes
			: !sameItems(scopes.value, initialScopes)
	}
	return name.value.trim() !== ''
})

const buttons = computed<IDialogButton[]>(() => [
	{
		label: t('settings', 'Cancel'),
		variant: 'tertiary',
		callback: () => {},
	},
	{
		label: props.token ? t('settings', 'Save') : t('settings', 'Create'),
		variant: 'primary',
		disabled: !canSave.value,
		callback: save,
	},
])

/**
 * Create the app password or save its new access.
 * Returns false to keep the dialog open when that failed.
 */
async function save(): Promise<false | undefined> {
	if (props.token) {
		return await authTokenStore.setTokenScopes(props.token, scopes.value) ? undefined : false
	}

	try {
		emit('created', await authTokenStore.addToken(name.value, scopes.value))
	} catch (error) {
		logger.error('Could not create the app password', { error })
		showError(t('settings', 'Error while creating device token'))
		return false
	}
}

/**
 * Enter in the name field. The dialog buttons only handle clicks, so close here on success.
 */
async function submit() {
	if (!canSave.value || saving.value) {
		return
	}
	saving.value = true
	if (await save() !== false) {
		open.value = false
	}
	saving.value = false
}
</script>

<template>
	<NcDialog
		v-model:open="open"
		:name="token ? t('settings', 'Edit access for {name}', { name: token.name }) : t('settings', 'Create new app password')"
		size="normal"
		isForm
		:buttons="buttons"
		@submit="submit">
		<!-- NcModal ignores Escape pressed in any input, radios and checkboxes included -->
		<div class="access-dialog" @keydown.esc="open = false">
			<NcTextField
				v-if="!token"
				v-model="name"
				:maxlength="120"
				:label="t('settings', 'App name')" />
			<AuthTokenAccessPicker v-model="selection" :kept="initial.kept" />
		</div>
	</NcDialog>
</template>

<style lang="scss" scoped>
.access-dialog {
	display: flex;
	flex-direction: column;
	gap: calc(4 * var(--default-grid-baseline));
}
</style>
