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
			<NcListItem v-for="version in versions"
				class="version"
				key="version.url"
				:title="version.title"
				:href="version.url">
				<template #icon>
					<img lazy="true"
						:src="version.preview"
						alt=""
						height="256"
						width="256"
						class="version-image">
				</template>
				<template #subtitle>
					<div class="version-info">
						<a v-tooltip="version.dateTime" :href="version.url">{{ version.relativeTime }}</a>
						<span class="version-info-size">•</span>
						<span class="version-info-size">
							{{ version.size }}
						</span>
					</div>
				</template>
				<template #actions>
					<NcActionLink :href="version.url">
						<template #icon>
							<Download :size="22" />
						</template>
						{{ t('files_versions', 'Download version') }}
					</NcActionLink>
					<NcActionButton @click="restoreVersion(version)" v-if="!version.isCurrent">
						<template #icon>
							<BackupRestore :size="22" />
						</template>
						{{ t('files_versions', 'Restore version') }}
					</NcActionButton>
				</template>
			</NcListItem>
			<NcEmptyContent v-if="!loading && versions.length === 1"
				:title="t('files_version', 'No versions yet')">
				<!-- length === 1, since we don't want to show versions if there is only the current file -->
				<template #icon>
					<BackupRestore />
				</template>
			</NcEmptyContent>
		</ul>
	</div>
</template>

<script>
import { createClient, getPatcher } from 'webdav'
import axios from '@nextcloud/axios'
import { generateRemoteUrl, generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import BackupRestore from 'vue-material-design-icons/BackupRestore.vue'
import Download from 'vue-material-design-icons/Download.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionLink from '@nextcloud/vue/dist/Components/NcActionLink.js'
import NcListItem from '@nextcloud/vue/dist/Components/NcListItem.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import moment from '@nextcloud/moment'
import { basename, joinPaths } from '@nextcloud/paths'
import { getLoggerBuilder } from '@nextcloud/logger'
import { translate } from '@nextcloud/l10n'

const logger = getLoggerBuilder()
	.setApp('files_version')
	.detectUser()
	.build()

/**
 * Get WebDAV request body for version list
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
 */
function formatVersion(version, fileInfo) {
	const fileVersion = basename(version.filename)
	const isCurrent = version.mime === ''

	const preview = isCurrent
		? generateUrl('/core/preview?fileId={fileId}&c={fileEtag}&x=250&y=250&forceIcon=0&a=0', {
			fileId: fileInfo.id,
			fileEtag: fileInfo.etag,
		}) : generateUrl('/apps/files_versions/preview?file={file}&version={fileVersion}', {
			file: joinPaths(fileInfo.path, fileInfo.name),
			fileVersion,
		})

	return {
		displayVersionName: fileVersion,
		title: isCurrent ? translate('files_versions', 'Current version') : '',
		fileName: version.filename,
		mimeType: version.mime,
		size: OC.Util.humanFileSize(isCurrent ? fileInfo.size : version.size),
		type: version.type,
		dateTime: moment(isCurrent ? fileInfo.mtime : version.lastmod),
		relativeTime: moment(isCurrent ? fileInfo.mtime : version.lastmod).fromNow(),
		preview,
		url: isCurrent ? joinPaths('/remote.php/dav', version.filename) : joinPaths('/remote.php/dav', fileInfo.path, fileInfo.name),
		fileVersion,
		isCurrent,
	}
}

const rootPath = 'dav'

// force our axios
const patcher = getPatcher()
patcher.patch('request', axios)

// init webdav client on default dav endpoint
const remote = generateRemoteUrl(rootPath)
const client = createClient(remote)

export default {
	name: 'VersionTab',
	components: {
		NcButton,
		NcEmptyContent,
		NcActionLink,
		NcActionButton,
		NcListItem,
		BackupRestore,
		Download,
	},
	data() {

		return {
			fileInfo: null,
			versions: [],
			loading: true,
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

			try {
				const response = await client.getDirectoryContents(path, {
					data: getDavRequest(),
				})
				this.versions = response.map(version => formatVersion(version, this.fileInfo))
				this.loading = false
			} catch (exception) {
				logger.error('Could not fetch version', {exception})
				this.loading = false
			}
		},

		/**
		 * Restore the given version
		 *
		 * @param version
		 */
		async restoreVersion(version) {
			try {
				logger.debug('restoring version', version.url)
				const response = await client.moveFile(
					`/versions/${getCurrentUser().uid}/versions/${this.fileInfo.id}/${version.fileVersion}`,
					`/versions/${getCurrentUser().uid}/restore/target`
				)
				showSuccess(t('files_versions', 'Version restored'))
				await this.fetchVersions()
			} catch (exception) {
				logger.error('Could not restore version', {exception})
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
		border: 1px solid var(--color-border);
		margin-right: 1rem;
		border-radius: var(--border-radius-large);
	}
}
</style>
