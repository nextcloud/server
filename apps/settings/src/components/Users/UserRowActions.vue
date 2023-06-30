<template>
	<NcActions :aria-label="t('settings', 'Toggle user actions menu')"
		:inline="1">
		<NcActionButton @click="toggleEdit">
			{{ edit ? t('settings', 'Done') : t('settings', 'Edit') }}
			<template #icon>
				<NcIconSvgWrapper :svg="editSvg" aria-hidden="true" />
			</template>
		</NcActionButton>
		<NcActionButton v-for="(action, index) in actions"
			:key="index"
			:aria-label="action.text"
			:icon="action.icon"
			@click="action.action">
			{{ action.text }}
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
	action: (event: MouseEvent) => void,
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
		 * The state whether the row is currently edited
		 */
		edit: {
			type: Boolean,
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
