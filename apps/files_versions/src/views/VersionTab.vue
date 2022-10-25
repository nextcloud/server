<!--
 - @copyright 2022 Carl Schwan <carl@carlschwan.eu>
 - @license AGPL-3.0-or-later
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
 -->
<template>
	<div>
		<ul>
			<li v-for="version in versions" class="version">
				<img lazy="true"
					:src="version.preview"
					height="256"
					width="256"
					class="version-image" />
				<div>
					<div v-if="version.versionName !== ''" ><b>{{ version.versionName }}</b></div>
					<div class="version-info">
						<NcAvatar
							:user="version.user"
							:size="22"
							:disableMenu="true"
							:showUserStatus="false"
							:ariaLabel="version.userDisplayName"
							:disableTooltip="true" />
						<a v-tooltip="version.dateTime" :href="version.url">{{ version.relativeTime }}</a>
						<span class="version-info-size">•</span>
						<span class="version-info-size">
							{{ version.size }}
						</span>
					</div>
				</div>

				<NcActions class="version-actions">
					<NcActionButton @click="showSaveVersion(version)">
						<template #icon>
							<Star v-if="version.versionName !== ''" :size="22" />
							<StarOutline v-else :size="22" />
						</template>
						{{ t('files_versions', `Name version ${version.displayVersionName} for file ${fileInfo.name}`) }}
					</NcActionButton>
					<NcActionLink :href="version.url">
						<template #icon>
							<Download :size="22" />
						</template>
						{{ t('files_versions', `Download file ${fileInfo.name} with version ${version.displayVersionName}`) }}
					</NcActionLink>
					<NcActionButton @click="restoreVersion(version)">
						<template #icon>
							<BackupRestore :size="22" />
						</template>
						{{ t('files_versions', `Restore file ${fileInfo.name} with version ${version.displayVersionName}`) }}
					</NcActionButton>
				</NcActions>
			</li>
		</ul>
		<NcModal v-if="versionNameModalVisible">
			<div class="version-modal">
				<h3>{{ t('files_version', 'Name this version') }}</h3>

				<NcTextField :label="t('files_versions', 'Version name')"
					:labelVisible="true" />

				<p>{{ t('files_versions', 'Named versions are persisted, and excluded from version cleanup if your storage quota is too full.') }}</p>

				<div>
					<NcButton type="primary">
						{{ t('files_versions', 'Save this version') }}
					</NcButton>
				</div>
			</div>
		</NcModal>
	</div>
</template>

<script>
import { createClient, getPatcher } from 'webdav'
import axios from '@nextcloud/axios'
import parseUrl from 'url-parse'
import { generateRemoteUrl, generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { translate } from '@nextcloud/l10n'
import BackupRestore from 'vue-material-design-icons/BackupRestore.vue'
import Star from 'vue-material-design-icons/Star.vue'
import StarOutline from 'vue-material-design-icons/StarOutline.vue'
import Download from 'vue-material-design-icons/Download.vue'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionLink from '@nextcloud/vue/dist/Components/NcActionLink.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import moment from '@nextcloud/moment'
import { basename, joinPaths } from '@nextcloud/paths'

/**
 * Get dav request with additonal properties
 */
function getDavRequest() {
	return `<?xml version="1.0"?>
			<d:propfind xmlns:d="DAV:"
				xmlns:oc="http://owncloud.org/ns"
				xmlns:nc="http://nextcloud.org/ns"
				xmlns:ocs="http://open-collaboration-services.org/ns">
				<d:prop>
					<d:getcontentlength />
					<d:getcontenttype />
					<d:getlastmodified />
				</d:prop>
			</d:propfind>`
}

/**
 * Format version
 * @param version
 * @param fileInfo
 */
function formatVersion(version, fileInfo) {
	const fileVersion = basename(version.filename)

	const preview = generateUrl('/apps/files_versions/preview?file={file}&version={fileVersion}', {
		file: joinPaths(fileInfo.path, fileInfo.name),
		fileVersion,
	})

	return {
		displayVersionName: fileVersion,
		fileName: version.filename,
		mimeType: version.mime,
		size: OC.Util.humanFileSize(version.size),
		type: version.type,
		dateTime: moment(version.lastmod),
		relativeTime: moment(version.lastmod).fromNow(),
		preview,
		url: joinPaths('/remote.php/dav', version.filename),
		fileVersion,

		// TODO implement this in the backend
		versionName: '',
		user: getCurrentUser().uid,
	}
}

export default {
	name: 'VersionTab',
	components: {
		NcAvatar,
		NcActions,
		NcActionLink,
		NcActionButton,
		NcTextField,
		NcModal,
		NcButton,
		BackupRestore,
		Download,
		Star,
		StarOutline,
	},
	data() {
		const rootPath = 'dav'

		// force our axios
		const patcher = getPatcher()
		patcher.patch('request', axios)

		// init webdav client on default dav endpoint
		const remote = generateRemoteUrl(rootPath)
		const client = createClient(remote)

		return {
			fileInfo: null,
			versions: [],
			client,
			remote,
			versionNameModalVisible: false,
		}
	},
	methods: {
		/**
		 * Update current fileInfo and fetch new data
		 *
		 * @param {object} fileInfo the current file FileInfo
		 */
		async update(fileInfo) {
			this.fileInfo = fileInfo
			this.resetState()
			this.fetchVersions()
		},

		/**
		 * Get the existing versions infos
		 */
		async fetchVersions() {
			const path = `/versions/${getCurrentUser().uid}/versions/${this.fileInfo.id}`
			const remotePath = parseUrl(this.remote).pathname

			const response = await this.client.getDirectoryContents(path, {
				data: getDavRequest(),
			})
			this.versions = response.filter(version => version.mime !== '')
				.map(version => formatVersion(version, this.fileInfo))
		},

		/**
		 * Restore the given version
		 *
		 * @param version
		 */
		async restoreVersion(version) {
			try {
				console.debug('restore version', version.url)
				const response = await this.client.moveFile(
					`/versions/${getCurrentUser().uid}/versions/${this.fileInfo.id}/${version.fileVersion}`,
					`/versions/${getCurrentUser().uid}/restore/target`
				)
				showSuccess(t('files_versions', 'Version restored'))
				await this.fetchVersions()
			} catch (exception) {
				console.error('Could not restore version', exception)
				showError(t('files_versions', 'Could not restore version'))
			}
		},

		/**
		 * Reset the current view to its default state
		 */
		resetState() {
			this.versions = []
		},

		showSaveVersion(version) {
			this.versionNameModalVisible = true
		},

		saveVersion(version) {
			this.versionNameModalVisible = false
		},
	},
}
</script>

<style scopped lang="scss">
.version {
	display: flex;
	flex-direction: row;
	align-items: center;
	&-info {
		display: flex;
		flex-direction: row;
		align-items: center;
		gap: 0.5rem;
		&-size {
			color: var(--color-text-lighter);
		}
	}
	&-image {
		width: 3rem;
		height: 3rem;
		filter: drop-shadow(0 1px 2px var(--color-box-shadow));
		margin-right: 1rem;
		border-radius: var(--border-radius);
	}
	&-actions {
		margin-left: auto;
	}

	&-modal {
		padding: 1rem;
		display: flex;
		flex-direction: column;
		gap: 1rem;

		h3 {
			font-weight: bold;
		}
	}
}
</style>
