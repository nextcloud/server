<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<fieldset class="permissions-select">
		<NcCheckboxRadioSwitch :checked="canCreate" @update:checked="toggle(PERMISSION_CREATE)">
			{{ t('settings', 'Create') }}
		</NcCheckboxRadioSwitch>
		<NcCheckboxRadioSwitch :checked="canUpdate" @update:checked="toggle(PERMISSION_UPDATE)">
			{{ t('settings', 'Change') }}
		</NcCheckboxRadioSwitch>
		<NcCheckboxRadioSwitch :checked="canDelete" @update:checked="toggle(PERMISSION_DELETE)">
			{{ t('settings', 'Delete') }}
		</NcCheckboxRadioSwitch>
		<NcCheckboxRadioSwitch :checked="canShare" @update:checked="toggle(PERMISSION_SHARE)">
			{{ t('settings', 'Reshare') }}
		</NcCheckboxRadioSwitch>
	</fieldset>
</template>

<script lang="ts">
import { translate } from '@nextcloud/l10n'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { defineComponent } from 'vue'

export default defineComponent({
	name: 'SelectSharingPermissions',
	components: {
		NcCheckboxRadioSwitch,
	},
	props: {
		value: {
			type: Number,
			required: true,
		},
	},
	emits: {
		'update:value': (value: number) => typeof value === 'number',
	},
	data() {
		return {
			PERMISSION_UPDATE: 2,
			PERMISSION_CREATE: 4,
			PERMISSION_DELETE: 8,
			PERMISSION_SHARE: 16,
		}
	},
	computed: {
		canCreate() {
			return (this.value & this.PERMISSION_CREATE) !== 0
		},
		canUpdate() {
			return (this.value & this.PERMISSION_UPDATE) !== 0
		},
		canDelete() {
			return (this.value & this.PERMISSION_DELETE) !== 0
		},
		canShare() {
			return (this.value & this.PERMISSION_SHARE) !== 0
		},
	},
	methods: {
		t: translate,
		/**
		 * Toggle a permission
		 * @param permission The permission (bit) to toggle
		 */
		toggle(permission: number) {
			// xor to toggle the bit
			this.$emit('update:value', this.value ^ permission)
		},
	},
})
</script>

<style scoped>
.permissions-select {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
}
</style>
