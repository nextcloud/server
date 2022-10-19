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
					class="version-image">
				<div class="version-info">
					<a v-tooltip="version.dateTime" :href="version.url">{{ version.relativeTime }}</a>
					<div class="version-info-size">
						{{ version.size }}
					</div>
				</div>
				<NcButton v-tooltip="t('files_versions', `Download file ${fileInfo.name} with version ${version.displayVersionName}`)"
					type="secondary"
					class="download-button"
					:href="version.url"
					:aria-label="t('files_versions', `Download file ${fileInfo.name} with version ${version.displayVersionName}`)">
					<template #icon>
						<Download :size="22" />
					</template>
				</NcButton>
				<NcButton v-tooltip="t('files_versions', `Restore file ${fileInfo.name} with version ${version.displayVersionName}`)"
					type="secondary"
					class="restore-button"
					:aria-label="t('files_versions', `Restore file ${fileInfo.name} with version ${version.displayVersionName}`)"
					@click="restoreVersion(version)">
					<template #icon>
						<BackupRestore :size="22" />
					</template>
				</NcButton>
			</li>
		</ul>
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
import Download from 'vue-material-design-icons/Download.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import moment from '@nextcloud/moment'
import { basename, joinPaths } from '@nextcloud/paths'

/**
 *
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
 *
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
	}
}

export default {
	name: 'VersionTab',
	components: {
		NcButton,
		BackupRestore,
		Download,
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
	},
}
</script>

<style scopped lang="scss">
.version {
	display: flex;
	flex-direction: row;
	&-info {
		display: flex;
		flex-direction: column;
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
	.restore-button {
		margin-left: 1rem;
		align-self: center;
	}
	.download-button {
		margin-left: auto;
		align-self: center;
	}
}
</style>
