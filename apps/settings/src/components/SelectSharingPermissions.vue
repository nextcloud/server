<!--
	- @copyright 2023 Ferdinand Thiessen <opensource@fthiessen.de>
	-
	- @author Ferdinand Thiessen <opensource@fthiessen.de>
	-
	- @license AGPL-3.0-or-later
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
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
