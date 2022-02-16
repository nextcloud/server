<!--
  - @copyright Copyright (c) 2022 Louis Chmn <louis@chmn.me>
  -
  - @author Louis Chmn <louis@chmn.me>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<span>
		<!-- file -->
		<ActionCheckbox v-if="!isFolder"
			:checked="shareHasPermissions(atomicPermissions.UPDATE)"
			:disabled="saving"
			@update:checked="toggleSharePermissions(atomicPermissions.UPDATE)">
			{{ t('files_sharing', 'Allow editing') }}
		</ActionCheckbox>

		<!-- folder -->
		<template v-if="isFolder && fileHasCreatePermission && config.isPublicUploadEnabled">
			<template v-if="!showCustomPermissionsForm">
				<ActionRadio :checked="sharePermissionEqual(bundledPermissions.READ_ONLY)"
					:value="bundledPermissions.READ_ONLY"
					:name="randomFormName"
					:disabled="saving"
					@change="setSharePermissions(bundledPermissions.READ_ONLY)">
					{{ t('files_sharing', 'Read only') }}
				</ActionRadio>

				<ActionRadio :checked="sharePermissionEqual(bundledPermissions.UPLOAD_AND_UPDATE)"
					:value="bundledPermissions.UPLOAD_AND_UPDATE"
					:disabled="saving"
					:name="randomFormName"
					@change="setSharePermissions(bundledPermissions.UPLOAD_AND_UPDATE)">
					{{ t('files_sharing', 'Allow upload and editing') }}
				</ActionRadio>
				<ActionRadio :checked="sharePermissionEqual(bundledPermissions.FILE_DROP)"
					:value="bundledPermissions.FILE_DROP"
					:disabled="saving"
					:name="randomFormName"
					class="sharing-entry__action--public-upload"
					@change="setSharePermissions(bundledPermissions.FILE_DROP)">
					{{ t('files_sharing', 'File drop (upload only)') }}
				</ActionRadio>

				<!-- custom permissions button -->
				<ActionButton :title="t('files_sharing', 'Custom permissions')"
					@click="showCustomPermissionsForm = true">
					<template #icon>
						<Tune />
					</template>
					{{ sharePermissionsIsBundle ? "" : sharePermissionsSummary }}
				</ActionButton>
			</template>

			<!-- custom permissions -->
			<span v-else :class="{error: !sharePermissionsSetIsValid}">
				<ActionCheckbox :checked="shareHasPermissions(atomicPermissions.READ)"
					:disabled="saving || !canToggleSharePermissions(atomicPermissions.READ)"
					@update:checked="toggleSharePermissions(atomicPermissions.READ)">
					{{ t('files_sharing', 'Read') }}
				</ActionCheckbox>
				<ActionCheckbox :checked="shareHasPermissions(atomicPermissions.CREATE)"
					:disabled="saving || !canToggleSharePermissions(atomicPermissions.CREATE)"
					@update:checked="toggleSharePermissions(atomicPermissions.CREATE)">
					{{ t('files_sharing', 'Upload') }}
				</ActionCheckbox>
				<ActionCheckbox :checked="shareHasPermissions(atomicPermissions.UPDATE)"
					:disabled="saving || !canToggleSharePermissions(atomicPermissions.UPDATE)"
					@update:checked="toggleSharePermissions(atomicPermissions.UPDATE)">
					{{ t('files_sharing', 'Edit') }}
				</ActionCheckbox>
				<ActionCheckbox :checked="shareHasPermissions(atomicPermissions.DELETE)"
					:disabled="saving || !canToggleSharePermissions(atomicPermissions.DELETE)"
					@update:checked="toggleSharePermissions(atomicPermissions.DELETE)">
					{{ t('files_sharing', 'Delete') }}
				</ActionCheckbox>

				<ActionButton @click="showCustomPermissionsForm = false">
					<template #icon>
						<ChevronLeft />
					</template>
					{{ t('files_sharing', 'Bundled permissions') }}
				</ActionButton>
			</span>
		</template>
	</span>
</template>

<script>
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionRadio from '@nextcloud/vue/dist/Components/ActionRadio'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'

import SharesMixin from '../mixins/SharesMixin'
import {
	ATOMIC_PERMISSIONS,
	BUNDLED_PERMISSIONS,
	hasPermissions,
	permissionsSetIsValid,
	togglePermissions,
	canTogglePermissions,
} from '../lib/SharePermissionsToolBox'

import Tune from 'vue-material-design-icons/Tune'
import ChevronLeft from 'vue-material-design-icons/ChevronLeft'

export default {
	name: 'SharePermissionsEditor',

	components: {
		ActionButton,
		ActionCheckbox,
		ActionRadio,
		Tune,
		ChevronLeft,
	},

	mixins: [SharesMixin],

	data() {
		return {
			randomFormName: Math.random().toString(27).substring(2),

			showCustomPermissionsForm: false,

			atomicPermissions: ATOMIC_PERMISSIONS,
			bundledPermissions: BUNDLED_PERMISSIONS,
		}
	},

	computed: {
		/**
		 * Return the summary of custom checked permissions.
		 *
		 * @return {string}
		 */
		sharePermissionsSummary() {
			return Object.values(this.atomicPermissions)
				.filter(permission => this.shareHasPermissions(permission))
				.map(permission => {
					switch (permission) {
					case this.atomicPermissions.CREATE:
						return this.t('files_sharing', 'Upload')
					case this.atomicPermissions.READ:
						return this.t('files_sharing', 'Read')
					case this.atomicPermissions.UPDATE:
						return this.t('files_sharing', 'Edit')
					case this.atomicPermissions.DELETE:
						return this.t('files_sharing', 'Delete')
					default:
						return ''
					}
				})
				.join(', ')
		},

		/**
		 * Return whether the share's permission is a bundle.
		 *
		 * @return {boolean}
		 */
		sharePermissionsIsBundle() {
			return Object.values(BUNDLED_PERMISSIONS)
				.map(bundle => this.sharePermissionEqual(bundle))
				.filter(isBundle => isBundle)
				.length > 0
		},

		/**
		 * Return whether the share's permission is valid.
		 *
		 * @return {boolean}
		 */
		sharePermissionsSetIsValid() {
			return permissionsSetIsValid(this.share.permissions)
		},

		/**
		 * Is the current share a folder ?
		 * TODO: move to a proper FileInfo model?
		 *
		 * @return {boolean}
		 */
		isFolder() {
			return this.fileInfo.type === 'dir'
		},

		/**
		 * Does the current file/folder have create permissions.
		 * TODO: move to a proper FileInfo model?
		 *
		 * @return {boolean}
		 */
		fileHasCreatePermission() {
			return !!(this.fileInfo.permissions & ATOMIC_PERMISSIONS.CREATE)
		},
	},

	mounted() {
		// Show the Custom Permissions view on open if the permissions set is not a bundle.
		this.showCustomPermissionsForm = !this.sharePermissionsIsBundle
	},

	methods: {
		/**
		 * Return whether the share has the exact given permissions.
		 *
		 * @param {number} permissions - the permissions to check.
		 *
		 * @return {boolean}
		 */
		sharePermissionEqual(permissions) {
			// We use the share's permission without PERMISSION_SHARE as it is not relevant here.
			return (this.share.permissions & ~ATOMIC_PERMISSIONS.SHARE) === permissions
		},

		/**
		 * Return whether the share has the given permissions.
		 *
		 * @param {number} permissions - the permissions to check.
		 *
		 * @return {boolean}
		 */
		shareHasPermissions(permissions) {
			return hasPermissions(this.share.permissions, permissions)
		},

		/**
		 * Set the share permissions to the given permissions.
		 *
		 * @param {number} permissions - the permissions to set.
		 *
		 * @return {void}
		 */
		setSharePermissions(permissions) {
			this.share.permissions = permissions
			this.queueUpdate('permissions')
		},

		/**
		 * Return whether some given permissions can be toggled.
		 *
		 * @param {number} permissionsToToggle - the permissions to toggle.
		 *
		 * @return {boolean}
		 */
		canToggleSharePermissions(permissionsToToggle) {
			return canTogglePermissions(this.share.permissions, permissionsToToggle)
		},

		/**
		 * Toggle a given permission.
		 *
		 * @param {number} permissions - the permissions to toggle.
		 *
		 * @return {void}
		 */
		toggleSharePermissions(permissions) {
			this.share.permissions = togglePermissions(this.share.permissions, permissions)

			if (!this.permissionsSetIsValid(this.share.permissions)) {
				return
			}

			this.queueUpdate('permissions')
		},
	},
}
</script>
<style lang="scss" scoped>
.error {
	::v-deep .action-checkbox__label:before {
		border: 1px solid var(--color-error);
	}
}
</style>
