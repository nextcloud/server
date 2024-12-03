<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="field">
		<label :for="id">{{ displayName }}</label>
		<div class="field__row">
			<NcButton :id="id"
				type="secondary"
				:aria-label="ariaLabel"
				data-admin-theming-setting-file-picker
				@click="activateLocalFilePicker">
				<template #icon>
					<Upload :size="20" />
				</template>
				{{ t('theming', 'Upload') }}
			</NcButton>
			<NcButton v-if="showReset"
				type="tertiary"
				:aria-label="t('theming', 'Reset to default')"
				data-admin-theming-setting-file-reset
				@click="undo">
				<template #icon>
					<Undo :size="20" />
				</template>
			</NcButton>
			<NcButton v-if="showRemove"
				type="tertiary"
				:aria-label="t('theming', 'Remove background image')"
				data-admin-theming-setting-file-remove
				@click="removeBackground">
				<template #icon>
					<Delete :size="20" />
				</template>
			</NcButton>
			<NcLoadingIcon v-if="showLoading"
				class="field__loading-icon"
				:size="20" />
		</div>

		<div v-if="(name === 'logoheader' || name === 'favicon') && mimeValue !== defaultMimeValue"
			class="field__preview"
			:class="{
				'field__preview--logoheader': name === 'logoheader',
				'field__preview--favicon': name === 'favicon',
			}" />

		<NcNoteCard v-if="errorMessage"
			type="error"
			:show-alert="true">
			<p>{{ errorMessage }}</p>
		</NcNoteCard>

		<input ref="input"
			:accept="acceptMime"
			type="file"
			@change="onChange">
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import Delete from 'vue-material-design-icons/Delete.vue'
import Undo from 'vue-material-design-icons/UndoVariant.vue'
import Upload from 'vue-material-design-icons/Upload.vue'

import FieldMixin from '../../mixins/admin/FieldMixin.js'

const {
	allowedMimeTypes,
} = loadState('theming', 'adminThemingParameters', {})

export default {
	name: 'FileInputField',

	components: {
		Delete,
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		Undo,
		Upload,
	},

	mixins: [
		FieldMixin,
	],

	props: {
		name: {
			type: String,
			required: true,
		},
		mimeName: {
			type: String,
			required: true,
		},
		mimeValue: {
			type: String,
			required: true,
		},
		defaultMimeValue: {
			type: String,
			default: '',
		},
		displayName: {
			type: String,
			required: true,
		},
		ariaLabel: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			showLoading: false,
			acceptMime: (allowedMimeTypes[this.name]
				|| ['image/jpeg', 'image/png', 'image/gif', 'image/webp']).join(','),
		}
	},

	computed: {
		showReset() {
			return this.mimeValue !== this.defaultMimeValue
		},

		showRemove() {
			if (this.name === 'background') {
				if (this.mimeValue.startsWith('image/')) {
					return true
				}
				if (this.mimeValue === this.defaultMimeValue) {
					return true
				}
			}
			return false
		},
	},

	methods: {
		activateLocalFilePicker() {
			this.reset()
			// Set to null so that selecting the same file will trigger the change event
			this.$refs.input.value = null
			this.$refs.input.click()
		},

		async onChange(e) {
			const file = e.target.files[0]

			const formData = new FormData()
			formData.append('key', this.name)
			formData.append('image', file)

			const url = generateUrl('/apps/theming/ajax/uploadImage')
			try {
				this.showLoading = true
				const { data } = await axios.post(url, formData)
				this.showLoading = false
				this.$emit('update:mime-value', file.type)
				this.$emit('uploaded', data.data.url)
				this.handleSuccess()
			} catch (e) {
				this.showLoading = false
				this.errorMessage = e.response.data.data?.message
			}
		},

		async undo() {
			this.reset()
			const url = generateUrl('/apps/theming/ajax/undoChanges')
			try {
				await axios.post(url, {
					setting: this.mimeName,
				})
				this.$emit('update:mime-value', this.defaultMimeValue)
				this.handleSuccess()
			} catch (e) {
				this.errorMessage = e.response.data.data?.message
			}
		},

		async removeBackground() {
			this.reset()
			const url = generateUrl('/apps/theming/ajax/updateStylesheet')
			try {
				await axios.post(url, {
					setting: this.mimeName,
					value: 'backgroundColor',
				})
				this.$emit('update:mime-value', 'backgroundColor')
				this.handleSuccess()
			} catch (e) {
				this.errorMessage = e.response.data.data?.message
			}
		},
	},
}
</script>

<style lang="scss" scoped>
@use './shared/field' as *;

.field {
	&__loading-icon {
		width: 44px;
		height: 44px;
	}

	&__preview {
		width: 70px;
		height: 70px;
		background-size: contain;
		background-position: center;
		background-repeat: no-repeat;
		margin: 10px 0;

		&--logoheader {
			background-image: var(--image-logoheader);
		}

		&--favicon {
			background-image: var(--image-favicon);
		}
	}
}

input[type="file"] {
	display: none;
}
</style>
