<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { Permission } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

/** The selected permissions as bitmask */
const permissions = defineModel<number>({ required: true })

const options = [
	{ permission: Permission.CREATE, label: t('settings', 'Create') },
	{ permission: Permission.UPDATE, label: t('settings', 'Change') },
	{ permission: Permission.DELETE, label: t('settings', 'Delete') },
	{ permission: Permission.SHARE, label: t('settings', 'Reshare') },
]

/**
 * Toggle a permission
 *
 * @param permission - The permission (bit) to toggle
 */
function toggle(permission: number) {
	permissions.value ^= permission
}
</script>

<template>
	<fieldset :class="$style.permissionsSelect">
		<NcCheckboxRadioSwitch
			v-for="{ permission, label } in options"
			:key="permission"
			:modelValue="(permissions & permission) !== 0"
			@update:modelValue="toggle(permission)">
			{{ label }}
		</NcCheckboxRadioSwitch>
	</fieldset>
</template>

<style module>
.permissionsSelect {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
}
</style>
