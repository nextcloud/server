<template>
	<div class="primary-color__wrapper">
		<NcColorPicker v-model="primaryColor" @submit="onUpdate">
			<button ref="trigger"
				class="color-container primary-color__trigger">
				{{ t('theming', 'Primary color') }}
				<NcLoadingIcon v-if="loading" />
				<IconColorPalette v-else :size="20" />
			</button>
		</NcColorPicker>
		<NcButton type="tertiary" :disabled="primaryColor === defaultColor" @click="primaryColor = defaultColor">
			<template #icon>
				<IconUndo :size="20" />
			</template>
			{{ t('theming', 'Reset primary color') }}
		</NcButton>
	</div>
</template>

<script lang="ts">
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { defineComponent } from 'vue'

import axios from '@nextcloud/axios'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcColorPicker from '@nextcloud/vue/dist/Components/NcColorPicker.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import IconColorPalette from 'vue-material-design-icons/Palette.vue'
import IconUndo from 'vue-material-design-icons/UndoVariant.vue'
import { subscribe } from '@nextcloud/event-bus'

export default defineComponent({
	name: 'UserPrimaryColor',
	components: {
		IconColorPalette,
		IconUndo,
		NcButton,
		NcColorPicker,
		NcLoadingIcon,
	},
	data() {
		const { primaryColor, defaultColor } = loadState('theming', 'data', { primaryColor: '#0082c9', defaultColor: '#0082c9' })
		return {
			defaultColor,
			primaryColor,
			loading: false,
		}
	},

	mounted() {
		subscribe('theming:global-styles-refreshed', this.onRefresh)
	},

	methods: {
		t,

		/**
		 * Global styles are reloaded so we might need to update the current value
		 */
		onRefresh() {
			const newColor = window.getComputedStyle(this.$refs.trigger).backgroundColor
			if (newColor.toLowerCase() !== this.primaryColor) {
				this.primaryColor = newColor
			}
		},

		async onUpdate(value: string) {
			this.loading = true
			const url = generateOcsUrl('apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
				appId: 'theming',
				configKey: 'primary_color',
			})
			try {
				await axios.post(url, {
					configValue: value,
				})
				this.$emit('refresh-styles')
			} catch (e) {
				console.error('Could not update primary color', e)
				showError(t('theming', 'Could not set primary color'))
			}
			this.loading = false
		},
	},
})
</script>

<style scoped lang="scss">
.primary-color {
	&__wrapper {
		display: flex;
		flex-direction: row;
		flex-wrap: wrap;
		gap: 12px;
	}

	&__trigger {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		gap: 8px;

		background-color: var(--color-primary);
		color: var(--color-primary-text);
		width: 350px;
		height: 96px;

		word-wrap: break-word;
		hyphens: auto;

		border: 2px solid var(--color-main-background);
		border-radius: var(--border-radius-large);

		&:active {
			background-color: var(--color-primary-hover) !important;
		}

		&:hover,
		&:focus,
		&:focus-visible {
			border-color: var(--color-main-background) !important;
			outline: 2px solid var(--color-main-text) !important;
		}
	}
}
</style>
