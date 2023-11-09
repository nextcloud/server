<!--
	- @copyright 2023 Ferdinand Thiessen <opensource@fthiessen.de>
	-
	- @author Christopher Ng <chrng8@gmail.com>
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
	<NcActions :aria-label="t('settings', 'Toggle user actions menu')"
		:disabled="disabled"
		:inline="1">
		<NcActionButton :data-cy-user-list-action-toggle-edit="`${edit}`"
			:disabled="disabled"
			@click="toggleEdit">
			{{ edit ? t('settings', 'Done') : t('settings', 'Edit') }}
			<template #icon>
				<NcIconSvgWrapper :key="editSvg" :svg="editSvg" aria-hidden="true" />
			</template>
		</NcActionButton>
		<NcActionButton v-for="({ action, icon, text }, index) in actions"
			:key="index"
			:disabled="disabled"
			:aria-label="text"
			:icon="icon"
			@click="(event) => action(event, { ...user })">
			{{ text }}
		</NcActionButton>
	</NcActions>
</template>

<script lang="ts">
import { PropType, defineComponent } from 'vue'

import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import SvgCheck from '@mdi/svg/svg/check.svg?raw'
import SvgPencil from '@mdi/svg/svg/pencil.svg?raw'

interface UserAction {
	action: (event: MouseEvent, user: Record<string, unknown>) => void,
	icon: string,
	text: string
}

export default defineComponent({
	components: {
		NcActionButton,
		NcActions,
		NcIconSvgWrapper,
	},

	props: {
		/**
		 * Array of user actions
		 */
		actions: {
			type: Array as PropType<readonly UserAction[]>,
			required: true,
		},

		/**
		 * The state whether the row is currently disabled
		 */
		disabled: {
			type: Boolean,
			required: true,
		},

		/**
		 * The state whether the row is currently edited
		 */
		edit: {
			type: Boolean,
			required: true,
		},

		/**
		 * Target of this actions
		 */
		user: {
			type: Object,
			required: true,
		},
	},

	computed: {
		/**
		 * Current MDI logo to show for edit toggle
		 */
		editSvg() {
			return this.edit ? SvgCheck : SvgPencil
		},
	},

	methods: {
		/**
		 * Toggle edit mode by emitting the update event
		 */
		toggleEdit() {
			this.$emit('update:edit', !this.edit)
		},
	},
})
</script>
