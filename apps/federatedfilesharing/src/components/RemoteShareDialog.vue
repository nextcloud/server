<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'

const props = defineProps<{
	/** Name of the share */
	name: string
	/** Display name of the owner */
	owner: string
	/** The remote instance name */
	remote: string
	/** True if the user should enter a password */
	passwordRequired: boolean
}>()

const emit = defineEmits<{
	(e: 'close', state: boolean, password?: string): void
}>()

const password = ref('')

/**
 * The dialog buttons
 */
const buttons = computed(() => [
	{
		label: t('federatedfilesharing', 'Cancel'),
		callback: () => emit('close', false),
	},
	{
		label: t('federatedfilesharing', 'Add remote share'),
		nativeType: props.passwordRequired ? 'submit' : undefined,
		type: 'primary',
		callback: () => emit('close', true, password.value),
	},
])
</script>

<template>
	<NcDialog :buttons="buttons"
		:is-form="passwordRequired"
		:name="t('federatedfilesharing', 'Remote share')"
		@submit="emit('close', true, password)">
		<p>
			{{ t('federatedfilesharing', 'Do you want to add the remote share {name} from {owner}@{remote}?', { name, owner, remote }) }}
		</p>
		<NcPasswordField v-if="passwordRequired"
			class="remote-share-dialog__password"
			:label="t('federatedfilesharing', 'Remote share password')"
			:value.sync="password" />
	</NcDialog>
</template>

<style scoped lang="scss">
.remote-share-dialog {

	&__password {
		margin-block: 1em .5em;
	}
}
</style>
