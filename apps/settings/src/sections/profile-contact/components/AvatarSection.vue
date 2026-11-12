<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAccountProperty } from '../../../constants/AccountPropertyConstants.ts'

import { mdiFolder, mdiTrashCanOutline, mdiTrayArrowUp } from '@mdi/js'
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { FilePickerClosed, getFilePickerBuilder, showError } from '@nextcloud/dialogs'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import Cropper from 'cropperjs'
import { onBeforeUnmount, onMounted, ref, useTemplateRef } from 'vue'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import VisibilityScopeControl from './shared/VisibilityScopeControl.vue'
import { NAME_READABLE_ENUM } from '../../../constants/AccountPropertyConstants.ts'
import logger from '../../../logger.ts'

import 'cropperjs/dist/cropper.css'

const { avatar } = loadState<{ avatar: IAccountProperty }>('settings', 'personalInfoParameters')
const { avatarChangeSupported } = loadState<{ avatarChangeSupported: boolean }>('settings', 'accountParameters')
const profileEnabledGlobally = loadState('settings', 'profileEnabledGlobally', true)

const VALID_MIME_TYPES = ['image/png', 'image/jpeg']

const picker = getFilePickerBuilder(t('settings', 'Choose your profile picture'))
	.setMultiSelect(false)
	.setMimeTypeFilter(VALID_MIME_TYPES)
	.setType(1)
	.allowDirectories(false)
	.build()

const userId = getCurrentUser()!.uid
const readable = NAME_READABLE_ENUM[avatar.name]

const scope = ref(avatar.scope)
const showCropper = ref(false)
const loading = ref(false)
const version = ref(window.oc_userconfig.avatar.version)
const isGenerated = ref(window.oc_userconfig.avatar.generated)

const fileInput = useTemplateRef('fileInput')
const cropperImage = useTemplateRef('cropperImage')
let cropper: Cropper | undefined

onMounted(() => {
	cropper = new Cropper(cropperImage.value!, {
		aspectRatio: 1 / 1,
		viewMode: 1,
		guides: false,
		center: false,
		highlight: false,
		autoCropArea: 1,
		minContainerWidth: 300,
		minContainerHeight: 300,
	})
	subscribe('settings:display-name:updated', handleDisplayNameUpdate)
})

onBeforeUnmount(() => {
	cropper?.destroy()
	unsubscribe('settings:display-name:updated', handleDisplayNameUpdate)
})

/**
 * Open the native file picker
 */
function activateLocalFilePicker() {
	// Set to null so that selecting the same file will trigger the change event
	fileInput.value!.value = ''
	fileInput.value!.click()
}

/**
 * @param event Change event of the file input
 */
function onChange(event: Event) {
	loading.value = true
	const file = (event.target as HTMLInputElement).files![0]
	if (!VALID_MIME_TYPES.includes(file.type)) {
		showError(t('settings', 'Please select a valid png or jpg file'))
		cancel()
		return
	}

	const reader = new FileReader()
	reader.onload = () => {
		cropper!.replace(reader.result as string)
		showCropper.value = true
	}
	reader.readAsDataURL(file)
}

/**
 * Pick the picture from the user's files
 */
async function openFilePicker() {
	let path: string
	try {
		path = await picker.pick()
	} catch (error) {
		if (!(error instanceof FilePickerClosed)) {
			logger.error('Could not pick a profile picture', { error })
		}
		return
	}

	loading.value = true
	try {
		const { data } = await axios.post(generateUrl('/avatar'), { path })
		if (data.status === 'success') {
			handleAvatarUpdate(false)
		} else if (data.data === 'notsquare') {
			cropper!.replace(data.image)
			showCropper.value = true
		} else {
			showError(data.data.message)
			cancel()
		}
	} catch {
		showError(t('settings', 'Error setting profile picture'))
		cancel()
	}
}

/**
 * Upload the cropped picture
 */
function saveAvatar() {
	showCropper.value = false
	loading.value = true

	const canvasData = cropper!.getCroppedCanvas()
	const scaleFactor = canvasData.width > 512 ? 512 / canvasData.width : 1

	cropper!.scale(scaleFactor, scaleFactor).getCroppedCanvas().toBlob(async (blob) => {
		if (blob === null) {
			showError(t('settings', 'Error cropping profile picture'))
			cancel()
			return
		}

		const formData = new FormData()
		formData.append('files[]', blob)
		try {
			await axios.post(generateUrl('/avatar'), formData)
			handleAvatarUpdate(false)
		} catch {
			showError(t('settings', 'Error saving profile picture'))
			handleAvatarUpdate(isGenerated.value)
		}
	})
}

/**
 * Delete the uploaded picture
 */
async function removeAvatar() {
	loading.value = true
	try {
		await axios.delete(generateUrl('/avatar'))
		handleAvatarUpdate(true)
	} catch {
		showError(t('settings', 'Error removing profile picture'))
		handleAvatarUpdate(isGenerated.value)
	}
}

/**
 * Leave the cropper without saving
 */
function cancel() {
	showCropper.value = false
	loading.value = false
}

/**
 * @param generated Whether the avatar is generated now
 */
function handleAvatarUpdate(generated: boolean) {
	// Update the avatar version so that avatar update handlers refresh correctly
	version.value = window.oc_userconfig.avatar.version = Date.now()
	isGenerated.value = window.oc_userconfig.avatar.generated = generated
	loading.value = false
	emit('settings:avatar:updated', window.oc_userconfig.avatar.version)
}

/**
 * Refresh a generated avatar, it shows the initials of the display name
 */
function handleDisplayNameUpdate() {
	version.value = window.oc_userconfig.avatar.version
}
</script>

<template>
	<section :class="$style.avatarSection">
		<div v-show="showCropper" :class="$style.cropper">
			<div :class="$style.cropperCanvas">
				<img ref="cropperImage" alt="">
			</div>
			<div :class="$style.cropperButtons">
				<NcButton @click="cancel">
					{{ t('settings', 'Cancel') }}
				</NcButton>
				<NcButton
					variant="primary"
					@click="saveAvatar">
					{{ t('settings', 'Set as profile picture') }}
				</NcButton>
			</div>
			<span :class="$style.hint">{{ t('settings', 'Please note that it can take up to 24 hours for your profile picture to be updated everywhere.') }}</span>
		</div>

		<template v-if="!showCropper">
			<div v-if="!profileEnabledGlobally" :class="$style.preview">
				<NcAvatar
					v-if="!loading"
					:key="version"
					:user="userId"
					:aria-label="t('settings', 'Your profile picture')"
					disableTooltip
					hideStatus
					:size="120" />
				<div v-else class="icon-loading" />
			</div>

			<template v-if="avatarChangeSupported">
				<div :class="$style.actions">
					<NcFormBox :class="$style.buttons">
						<NcFormBoxButton
							:label="t('settings', 'Upload profile picture')"
							@click="activateLocalFilePicker">
							<template #icon>
								<NcIconSvgWrapper :path="mdiTrayArrowUp" :size="20" />
							</template>
						</NcFormBoxButton>
						<NcFormBoxButton
							:label="t('settings', 'Choose from Nextcloud Files')"
							@click="openFilePicker">
							<template #icon>
								<NcIconSvgWrapper :path="mdiFolder" :size="20" />
							</template>
						</NcFormBoxButton>
						<NcFormBoxButton
							v-if="!isGenerated"
							:label="t('settings', 'Delete picture')"
							@click="removeAvatar">
							<template #icon>
								<NcIconSvgWrapper :path="mdiTrashCanOutline" :size="20" />
							</template>
						</NcFormBoxButton>
					</NcFormBox>
					<div :class="$style.scope">
						<VisibilityScopeControl
							v-model:scope="scope"
							:readable="readable"
							:name="avatar.name" />
					</div>
				</div>
				<span :class="$style.hint">{{ t('settings', 'The file must be a PNG or JPG') }}</span>
				<input
					ref="fileInput"
					:class="$style.fileInput"
					type="file"
					:accept="VALID_MIME_TYPES.join(',')"
					@change="onChange">
			</template>
			<span v-else :class="$style.hint">
				{{ t('settings', 'Picture provided by original account') }}
			</span>
		</template>
	</section>
</template>

<style module lang="scss">
.avatarSection {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 2);
	padding: 6px 0;
}

.preview {
	display: flex;
	justify-content: center;
	align-items: center;
	width: 120px;
	height: 120px;
	margin: 0 auto;
}

.actions {
	position: relative;
}

.buttons {
	width: 100%;
}

.scope {
	position: absolute;
	inset-block-start: 0;
	inset-inline-start: calc(100% + 8px);
	display: flex;
	align-items: center;
	justify-content: center;
	width: var(--default-clickable-area);
	height: var(--default-clickable-area);
}

.hint {
	color: var(--color-text-maxcontrast);
}

.cropper {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: calc(var(--default-grid-baseline) * 4);
}

.cropperCanvas {
	width: 300px;
	height: 300px;
	overflow: hidden;

	img {
		display: block;
		max-width: 100%;
	}

	:global(.cropper-view-box) {
		border-radius: 50%;
	}
}

.cropperButtons {
	width: 100%;
	display: flex;
	justify-content: space-between;
}

.fileInput {
	display: none;
}
</style>
