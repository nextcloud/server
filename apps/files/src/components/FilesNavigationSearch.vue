<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcAppNavigationSearch from '@nextcloud/vue/components/NcAppNavigationSearch'
import IconSearch from 'vue-material-design-icons/Magnify.vue'
import IconSearchGlobally from 'vue-material-design-icons/SearchWeb.vue'
import { useFilenameFilter } from '../composables/useFilenameFilter.ts'

const { searchQuery } = useFilenameFilter()
const searchGlobally = ref(false)

/**
 * Different searchbox label depending if filtering or searching
 */
const searchLabel = computed(() => (searchGlobally.value
	? t('files', 'Search by filename …')
	: t('files', 'Filter file names …')
))
</script>

<template>
	<NcAppNavigationSearch v-model="searchQuery" :label="searchLabel">
		<template #actions>
			<NcActions>
				<template #icon>
					<IconSearchGlobally v-if="searchGlobally" />
					<IconSearch v-else />
				</template>
				<NcActionButton close-after-click @click="searchGlobally = false">
					<template #icon>
						<IconSearch />
					</template>
					{{ t('files', 'Filter in current view') }}
				</NcActionButton>
				<NcActionButton close-after-click @click="searchGlobally = true">
					<template #icon>
						<IconSearchGlobally />
					</template>
					{{ t('files', 'Search globally') }}
				</NcActionButton>
			</NcActions>
		</template>
	</NcAppNavigationSearch>
</template>
