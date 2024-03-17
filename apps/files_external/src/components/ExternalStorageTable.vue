<template>
	<!-- The ID is just for backwards compatibility -->
	<table id="externalStorage">
		<thead>
			<tr>
				<th>
					<span class="hidden-visually" v-text="t('files_external', 'Folder name')" />
				</th>
				<th>{{ t('files_external', 'Folder name') }}</th>
				<th>{{ t('files_external', 'External storage') }}</th>
				<th>{{ t('files_external', 'Authentication') }}</th>
				<th>{{ t('files_external', 'Configuration') }}</th>
				<th v-if="isAdmin">
					{{ t('files_external', 'Available for') }}
				</th>
			</tr>
		</thead>
		<tbody>
			<ExternalStorageRow />
		</tbody>
	</table>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

const { isAdmin } = loadState<{ isAdmin: boolean }>('files_external', 'settings')

export default defineComponent({
	name: 'ExternalStorageTable',

	setup() {
		// Non reactive props
		return {
			isAdmin,
		}
	},
})
</script>
