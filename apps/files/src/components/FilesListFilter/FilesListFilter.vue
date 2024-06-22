<template>
	<NcActions force-menu
		:type="isActive ? 'primary' : 'tertiary'"
		:menu-name="filterName">
		<template #icon>
			<slot name="icon" />
		</template>
		<template v-if="isActive">
			<NcActionButton class="files-list-filter__clear-button" close-after-click @click="$emit('reset-filter')">
				{{ t('files', 'Clear filter') }}
			</NcActionButton>
			<NcActionSeparator />
		</template>
		<slot />
	</NcActions>
</template>

<script lang="ts">
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionSeparator from '@nextcloud/vue/dist/Components/NcActionSeparator.js'

export default defineComponent({
	name: 'FilesListFilter',

	components: {
		NcActions,
		NcActionButton,
		NcActionSeparator,
	},

	props: {
		isActive: {
			type: Boolean,
			required: true,
		},

		filterName: {
			type: String,
			required: true,
		},
	},

	emits: ['reset-filter'],

	methods: {
		t,
	},
})
</script>

<style scoped>
.files-list-filter__clear-button :deep(.action-button__text) {
	color: var(--color-error-text);
}
</style>
