<!--
	- @copyright 2022 Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
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
	<section id="vue-avatar-section">
		<h3 class="hidden-visually">
			{{ t('settings', 'Your profile information') }}
		</h3>
		<HeaderBar :input-id="avatarChangeSupported ? inputId : null"
			:readable="avatar.readable"
			:scope.sync="avatar.scope" />

		<div v-if="!showCropper" class="avatar__container">
			<div class="avatar__preview">
				<NcAvatar v-if="!loading"
					:key="version"
					:user="userId"
					:aria-label="t('settings', 'Your profile picture')"
					:disabled-tooltip="true"
					:show-user-status="false"
					:size="180" />
				<div v-else class="icon-loading" />
			</div>
			<template v-if="avatarChangeSupported">
				<div class="avatar__buttons">
					<NcButton :aria-label="t('settings', 'Upload profile picture')"
						@click="activateLocalFilePicker">
						<template #icon>
							<Upload :size="20" />
						</template>
					</NcButton>
					<NcButton :aria-label="t('settings', 'Choose profile picture from Files')"
						@click="openFilePicker">
						<template #icon>
							<Folder :size="20" />
						</template>
					</NcButton>
					<NcButton v-if="!isGenerated"
						:aria-label="t('settings', 'Remove profile picture')"
						@click="removeAvatar">
						<template #icon>
							<Delete :size="20" />
						</template>
					</NcButton>
				</div>
				<span>{{ t('settings', 'The file must be a PNG or JPG') }}</span>
				<input :id="inputId"
					ref="input"
					type="file"
					:accept="validMimeTypes.join(',')"
					@change="onChange">
			</template>
			<span v-else>
				{{ t('settings', 'Picture provided by original account') }}
			</span>
		</div>

		<!-- Use v-show to ensure early cropper ref availability -->
		<div v-show="showCropper" class="avatar__container">
			<VueCropper ref="cropper"
				class="avatar__cropper"
				v-bind="cropperOptions" />
			<div class="avatar__cropper-buttons">
				<NcButton @click="cancel">
					{{ t('settings', 'Cancel') }}
				</NcButton>
				<NcButton type="primary"
					@click="saveAvatar">
					{{ t('settings', 'Set as profile picture') }}
				</NcButton>
			</div>
			<span>{{ t('settings', 'Please note that it can take up to 24 hours for your profile picture to be updated everywhere.') }}</span>
		</div>
	</section>
</template>

<script>
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { getFilePickerBuilder, showError } from '@nextcloud/dialogs'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'

import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import VueCropper from 'vue-cropperjs'
// eslint-disable-next-line n/no-extraneous-import
import 'cropperjs/dist/cropper.css'

import Upload from 'vue-material-design-icons/Upload.vue'
import Folder from 'vue-material-design-icons/Folder.vue'
import Delete from 'vue-material-design-icons/Delete.vue'

import HeaderBar from './shared/HeaderBar.vue'
import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'

const { avatar } = loadState('settings', 'personalInfoParameters', {})
const { avatarChangeSupported } = loadState('settings', 'accountParameters', {})

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
		HeaderBar,
		NcAvatar,
		NcButton,
		Upload,
		VueCropper,
	},

	data() {
		return {
			avatar: { ...avatar, readable: NAME_READABLE_ENUM[avatar.name] },
			avatarChangeSupported,
			showCropper: false,
			loading: false,
			userId: getCurrentUser().uid,
			displayName: getCurrentUser().displayName,
			version: oc_userconfig.avatar.version,
			isGenerated: oc_userconfig.avatar.generated,
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

	computed: {
		inputId() {
			return `account-property-${this.avatar.name}`
		},
	},

	created() {
		subscribe('settings:display-name:updated', this.handleDisplayNameUpdate)
	},

	beforeDestroy() {
		unsubscribe('settings:display-name:updated', this.handleDisplayNameUpdate)
	},

	methods: {
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
					const tempAvatar = generateUrl('/avatar/tmp') + '?requesttoken=' + encodeURIComponent(OC.requestToken) + '#' + Math.floor(Math.random() * 1000)
					this.$refs.cropper.replace(tempAvatar)
					this.showCropper = true
				} else {
					showError(data.data.message)
					this.cancel()
				}
			} catch (e) {
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
				} catch (e) {
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
			} catch (e) {
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
			this.version = oc_userconfig.avatar.version = Date.now()
			this.isGenerated = oc_userconfig.avatar.generated = isGenerated
			this.loading = false
			emit('settings:avatar:updated', oc_userconfig.avatar.version)
		},

		handleDisplayNameUpdate() {
			this.version = oc_userconfig.avatar.version
		},
	},
}
</script>

<style lang="scss" scoped>
section {
	grid-row: 1/3;
}
.avatar {
	&__container {
		margin: 0 auto;
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
		gap: 16px 0;
		width: min(100%, 300px);

		span {
			color: var(--color-text-lighter);
		}
	}

	&__preview {
		display: flex;
		justify-content: center;
		align-items: center;
		width: 180px;
		height: 180px;
	}

	&__buttons {
		display: flex;
		gap: 0 10px;
	}

	&__cropper {
		width: 300px;
		height: 300px;
		overflow: hidden;

		&-buttons {
			width: 100%;
			display: flex;
			justify-content: space-between;
		}

		&::v-deep .cropper-view-box {
			border-radius: 50%;
		}
	}
}

input[type="file"] {
	display: none;
}
</style>
