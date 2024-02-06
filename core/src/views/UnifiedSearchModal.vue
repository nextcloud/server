<!--
  - @copyright Copyright (c) 2024 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @author Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<NcModal :show="isVisible"
		@close="closeModal">
		<template v-if="shouldLoadContent">
			<NcEmptyContent v-if="isLoading">
				<template #icon>
					<NcLoadingIcon :size="60" />
				</template>
			</NcEmptyContent>
			<UnifiedSearchModalContent :is-visible="isVisible"
				@hook:mounted="onMountContent" />
		</template>
	</NcModal>
</template>

<script>
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

// Defer loading the actual content until opened for the first time
const UnifiedSearchModalContent = () => import('./UnifiedSearchModalContent.vue')

export default {
	name: 'UnifiedSearchModal',

	components: {
		NcModal,
		NcEmptyContent,
		NcLoadingIcon,
		UnifiedSearchModalContent,
	},

	props: {
		isVisible: {
			type: Boolean,
			required: true,
		},
	},

	data() {
		return {
			shouldLoadContent: false,
			isLoading: false,
		}
	},

	watch: {
		isVisible(isVisible) {
			if (!isVisible || this.shouldLoadContent) {
				return
			}

			this.shouldLoadContent = true
			this.isLoading = true
		},
	},

	methods: {
		onMountContent() {
			this.isLoading = false
		},
		closeModal() {
			this.$emit('update:isVisible', false)
		},
	},
}
</script>
