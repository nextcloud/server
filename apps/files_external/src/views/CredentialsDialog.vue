<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcTextField from '@nextcloud/vue/components/NcTextField'

defineEmits<{
	close: [payload?: { login: string, password: string }]
}>()

const login = ref('')
const password = ref('')

const dialogButtons: InstanceType<typeof NcDialog>['buttons'] = [{
	label: t('files_external', 'Confirm'),
	type: 'submit',
	variant: 'primary',
}]
</script>

<template>
	<NcDialog
		:buttons="dialogButtons"
		class="external-storage-auth"
		close-on-click-outside
		data-cy-external-storage-auth
		is-form
		:name="t('files_external', 'Storage credentials')"
		out-transition
		@submit="$emit('close', { login, password })"
		@update:open="$emit('close')">
		<!-- Header -->
		<NcNoteCard
			class="external-storage-auth__header"
			:text="t('files_external', 'To access the storage, you need to provide the authentication credentials.')"
			type="info" />

		<!-- Login -->
		<NcTextField
			ref="login"
			v-model="login"
			class="external-storage-auth__login"
			data-cy-external-storage-auth-dialog-login
			:label="t('files_external', 'Login')"
			:placeholder="t('files_external', 'Enter the storage login')"
			minlength="2"
			name="login"
			required />

		<!-- Password -->
		<NcPasswordField
			ref="password"
			v-model="password"
			class="external-storage-auth__password"
			data-cy-external-storage-auth-dialog-password
			:label="t('files_external', 'Password')"
			:placeholder="t('files_external', 'Enter the storage password')"
			name="password"
			required />
	</NcDialog>
</template>
