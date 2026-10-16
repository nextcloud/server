<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IToken, ITokenResponse } from '../store/authtoken.ts'

import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import AuthTokenAccessPicker from './AuthTokenAccessPicker.vue'
import logger from '../../logger.ts'
import { useAuthTokenStore } from '../store/authtoken.ts'
import { fromScopes, toScopes } from '../utils/accessLevel.ts'

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
const selection = ref(initial.selection)

const scopes = computed(() => toScopes(selection.value, initial.kept))

const canSave = computed(() => {
	if (selection.value.preset === 'custom' && selection.value.grants.length === 0) {
		return false
	}
	if (props.token) {
		return !sameScopes(scopes.value, initialScopes)
	}
	return name.value.trim() !== ''
})

const buttons = computed(() => [
	{
		label: t('settings', 'Cancel'),
	},
	{
		label: props.token ? t('settings', 'Save') : t('settings', 'Create'),
		variant: 'primary' as const,
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
		logger.error(error as Error)
		showError(t('settings', 'Error while creating device token'))
		return false
	}
}

/**
 * @param a First scopes, null for all access
 * @param b Second scopes, null for all access
 */
function sameScopes(a: string[] | null, b: string[] | null): boolean {
	if (a === null || b === null) {
		return a === b
	}
	return a.length === b.length && a.every((scope) => b.includes(scope))
}
</script>

<template>
	<NcDialog
		v-model:open="open"
		:name="token ? t('settings', 'Edit access') : t('settings', 'Create new app password')"
		:buttons="buttons">
		<div class="access-dialog">
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
