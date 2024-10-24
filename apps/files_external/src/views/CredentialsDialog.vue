<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog :buttons="dialogButtons"
		class="external-storage-auth"
		close-on-click-outside
		data-cy-external-storage-auth
		is-form
		:name="t('files_external', 'Storage credentials')"
		out-transition
		@submit="$emit('close', {login, password})"
		@update:open="$emit('close')">
		<!-- Header -->
		<NcNoteCard class="external-storage-auth__header"
			:text="t('files_external', 'To access the storage, you need to provide the authentication credentials.')"
			type="info" />

		<!-- Login -->
		<NcTextField ref="login"
			class="external-storage-auth__login"
			data-cy-external-storage-auth-dialog-login
			:label="t('files_external', 'Login')"
			:placeholder="t('files_external', 'Enter the storage login')"
			minlength="2"
			name="login"
			required
			:value.sync="login" />

		<!-- Password -->
		<NcPasswordField ref="password"
			class="external-storage-auth__password"
			data-cy-external-storage-auth-dialog-password
			:label="t('files_external', 'Password')"
			:placeholder="t('files_external', 'Enter the storage password')"
			name="password"
			required
			:value.sync="password" />
	</NcDialog>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { t } from '@nextcloud/l10n'

import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

export default defineComponent({
	name: 'CredentialsDialog',

	components: {
		NcDialog,
		NcNoteCard,
		NcTextField,
		NcPasswordField,
	},

	setup() {
		return {
			t,
		}
	},

	data() {
		return {
			login: '',
			password: '',
		}
	},

	computed: {
		dialogButtons() {
			return [{
				label: t('files_external', 'Submit'),
				type: 'primary',
				nativeType: 'submit',
			}]
		},
	},
})
</script>
