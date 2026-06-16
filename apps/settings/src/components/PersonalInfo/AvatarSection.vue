<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section class="avatar-section">
		<div v-show="showCropper" class="avatar-section__cropper">
			<VueCropper
				ref="cropper"
				class="avatar-section__cropper-canvas"
				v-bind="cropperOptions" />
			<div class="avatar-section__cropper-buttons">
				<NcButton @click="cancel">
					{{ t('settings', 'Cancel') }}
				</NcButton>
				<NcButton
					variant="primary"
					@click="saveAvatar">
					{{ t('settings', 'Set as profile picture') }}
				</NcButton>
			</div>
			<span class="avatar-section__hint">{{ t('settings', 'Please note that it can take up to 24 hours for your profile picture to be updated everywhere.') }}</span>
		</div>

		<template v-if="!showCropper">
			<div v-if="!profileEnabledGlobally" class="avatar-section__preview">
				<NcAvatar
					v-if="!loading"
					:key="version"
					:user="userId"
					:aria-label="t('settings', 'Your profile picture')"
					:disable-tooltip="true"
					hide-status
					:size="120" />
				<div v-else class="icon-loading" />
			</div>

			<template v-if="avatarChangeSupported">
				<div class="avatar-section__actions">
					<NcFormBox class="avatar-section__buttons">
						<NcFormBoxButton
							:label="t('settings', 'Upload profile picture')"
							@click="activateLocalFilePicker">
							<template #icon>
								<Upload :size="20" />
							</template>
						</NcFormBoxButton>
						<NcFormBoxButton
							:label="t('settings', 'Choose from Nextcloud Files')"
							@click="openFilePicker">
							<template #icon>
								<Folder :size="20" />
							</template>
						</NcFormBoxButton>
						<NcFormBoxButton
							v-if="!isGenerated"
							:label="t('settings', 'Delete picture')"
							@click="removeAvatar">
							<template #icon>
								<Delete :size="20" />
							</template>
						</NcFormBoxButton>
					</NcFormBox>
					<VisibilityScopeControl
						class="avatar-section__scope"
						:readable="avatar.readable"
						:name="avatar.name"
						:scope="avatar.scope"
						@update:scope="onScopeChange" />
				</div>
				<span class="avatar-section__hint">{{ t('settings', 'The file must be a PNG or JPG') }}</span>
				<input
					ref="input"
					type="file"
					:accept="validMimeTypes.join(',')"
					@change="onChange">
			</template>
			<span v-else class="avatar-section__hint">
				{{ t('settings', 'Picture provided by original account') }}
			</span>
		</template>
	</section>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { getFilePickerBuilder, showError } from '@nextcloud/dialogs'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import VueCropper from 'vue-cropperjs'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import Folder from 'vue-material-design-icons/Folder.vue'
import Delete from 'vue-material-design-icons/TrashCanOutline.vue'
import Upload from 'vue-material-design-icons/TrayArrowUp.vue'
import VisibilityScopeControl from './shared/VisibilityScopeControl.vue'
import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'

import 'cropperjs/dist/cropper.css'

const { avatar } = loadState('settings', 'personalInfoParameters', {})
const { avatarChangeSupported } = loadState('settings', 'accountParameters', {})
const profileEnabledGlobally = loadState('settings', 'profileEnabledGlobally', true)

const VALID_MIME_TYPES = ['image/png', 'image/jpeg']

const picker = getFilePickerBuilder(t('settings', 'Choose your profile picture'))
	.setMultiSelect(false)
	.setMimeTypeFilter(VALID_MIME_TYPES)
	.setType(1)
	.allowDirectories(false)
	.build()

export default {
	name: 'AvatarSection',

	components: {
		Delete,
		Folder,
		NcAvatar,
		NcButton,
		NcFormBox,
		NcFormBoxButton,
		Upload,
		VisibilityScopeControl,
		VueCropper,
	},

	data() {
		return {
			avatar: { ...avatar, readable: NAME_READABLE_ENUM[avatar.name] },
			avatarChangeSupported,
			profileEnabledGlobally,
			showCropper: false,
			loading: false,
			userId: getCurrentUser().uid,
			displayName: getCurrentUser().displayName,
			version: window.oc_userconfig.avatar.version,
			isGenerated: window.oc_userconfig.avatar.generated,
			validMimeTypes: VALID_MIME_TYPES,
			cropperOptions: {
				aspectRatio: 1 / 1,
				viewMode: 1,
				guides: false,
				center: false,
				highlight: false,
				autoCropArea: 1,
				minContainerWidth: 300,
				minContainerHeight: 300,
			},
		}
	},

	created() {
		subscribe('settings:display-name:updated', this.handleDisplayNameUpdate)
	},

	beforeDestroy() {
		unsubscribe('settings:display-name:updated', this.handleDisplayNameUpdate)
	},

	methods: {
		onScopeChange(scope) {
			this.avatar.scope = scope
		},

		activateLocalFilePicker() {
			// Set to null so that selecting the same file will trigger the change event
			this.$refs.input.value = null
			this.$refs.input.click()
		},

		onChange(e) {
			this.loading = true
			const file = e.target.files[0]
			if (!this.validMimeTypes.includes(file.type)) {
				showError(t('settings', 'Please select a valid png or jpg file'))
				this.cancel()
				return
			}

			const reader = new FileReader()
			reader.onload = (e) => {
				this.$refs.cropper.replace(e.target.result)
				this.showCropper = true
			}
			reader.readAsDataURL(file)
		},

		async openFilePicker() {
			const path = await picker.pick()
			this.loading = true
			try {
				const { data } = await axios.post(generateUrl('/avatar'), { path })
				if (data.status === 'success') {
					this.handleAvatarUpdate(false)
				} else if (data.data === 'notsquare') {
					this.$refs.cropper.replace(data.image)
					this.showCropper = true
				} else {
					showError(data.data.message)
					this.cancel()
				}
			} catch {
				showError(t('settings', 'Error setting profile picture'))
				this.cancel()
			}
		},

		saveAvatar() {
			this.showCropper = false
			this.loading = true

			const canvasData = this.$refs.cropper.getCroppedCanvas()
			const scaleFactor = canvasData.width > 512 ? 512 / canvasData.width : 1

			this.$refs.cropper.scale(scaleFactor, scaleFactor).getCroppedCanvas().toBlob(async (blob) => {
				if (blob === null) {
					showError(t('settings', 'Error cropping profile picture'))
					this.cancel()
					return
				}

				const formData = new FormData()
				formData.append('files[]', blob)
				try {
					await axios.post(generateUrl('/avatar'), formData)
					this.handleAvatarUpdate(false)
				} catch {
					showError(t('settings', 'Error saving profile picture'))
					this.handleAvatarUpdate(this.isGenerated)
				}
			})
		},

		async removeAvatar() {
			this.loading = true
			try {
				await axios.delete(generateUrl('/avatar'))
				this.handleAvatarUpdate(true)
			} catch {
				showError(t('settings', 'Error removing profile picture'))
				this.handleAvatarUpdate(this.isGenerated)
			}
		},

		cancel() {
			this.showCropper = false
			this.loading = false
		},

		handleAvatarUpdate(isGenerated) {
			// Update the avatar version so that avatar update handlers refresh correctly
			this.version = window.oc_userconfig.avatar.version = Date.now()
			this.isGenerated = window.oc_userconfig.avatar.generated = isGenerated
			this.loading = false
			emit('settings:avatar:updated', window.oc_userconfig.avatar.version)
		},

		handleDisplayNameUpdate() {
			this.version = window.oc_userconfig.avatar.version
		},
	},
}
</script>

<style lang="scss" scoped>
.avatar-section {
	display: flex;
	flex-direction: column;
	gap: 8px;
	padding: 6px 0;

	&__preview {
		display: flex;
		justify-content: center;
		align-items: center;
		width: 120px;
		height: 120px;
		margin: 0 auto;
	}

	&__actions {
		display: flex;
		flex-direction: row;
		align-items: flex-start;
		gap: 8px;
	}

	&__buttons {
		flex: 1 1 auto;
		min-width: 0;
	}

	&__scope {
		flex: 0 0 44px;
		display: flex;
		justify-content: center;
	}

	&__hint {
		color: var(--color-text-maxcontrast);
	}

	&__cropper {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 16px;

		&-canvas {
			width: 300px;
			height: 300px;
			overflow: hidden;

			:deep(.cropper-view-box) {
				border-radius: 50%;
			}
		}

		&-buttons {
			width: 100%;
			display: flex;
			justify-content: space-between;
		}
	}
}

input[type="file"] {
	display: none;
}
</style>
