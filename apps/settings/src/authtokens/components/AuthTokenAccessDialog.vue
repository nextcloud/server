<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IToken, ITokenResponse } from '../store/authtoken.ts'

import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, ref, useTemplateRef } from 'vue'
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

type INcDialogButtons = InstanceType<typeof NcDialog>['$props']['buttons']

const authTokenStore = useAuthTokenStore()

const initial = fromScopes(props.token?.scope.scopes)
const initialScopes = toScopes(initial.selection, initial.kept)

const name = ref('')
const selection = ref(initial.selection)
const content = useTemplateRef('content')

const scopes = computed(() => toScopes(selection.value, initial.kept))

const changed = computed(() => scopes.value === null || initialScopes === null
	? scopes.value !== initialScopes
	: !sameItems(scopes.value, initialScopes))

const buttons = computed<INcDialogButtons>(() => [
	{
		label: t('settings', 'Cancel'),
		variant: 'tertiary',
	},
	{
		label: props.token ? t('settings', 'Save') : t('settings', 'Create'),
		type: 'submit',
		variant: 'primary',
		callback: save,
	},
])

/**
 * Create the app password or save its new access.
 * Returns false to keep the dialog open when the form is invalid or saving failed.
 */
async function save(): Promise<false | undefined> {
	// NcDialog only checks validity after the callback, so check before saving
	if (!content.value?.closest('form')?.reportValidity()) {
		return false
	}
	if (props.token) {
		if (!changed.value) {
			return
		}
		return await authTokenStore.setTokenScopes(props.token, scopes.value) ? undefined : false
	}

	try {
		const response = await authTokenStore.addToken(name.value, scopes.value)
		emit('created', response)
	} catch (error) {
		logger.error('Could not create the app password', { error })
		showError(t('settings', 'Error while creating device token'))
		return false
	}
}
</script>

<template>
	<NcDialog
		v-model:open="open"
		:name="token ? t('settings', 'Edit access for {name}', { name: token.name }) : t('settings', 'Create new app password')"
		size="normal"
		isForm
		:buttons="buttons">
		<!-- NcModal ignores Escape pressed in any input, radios and checkboxes included -->
		<div ref="content" class="access-dialog" @keydown.esc="open = false">
			<NcTextField
				v-if="!token"
				v-model="name"
				required
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
