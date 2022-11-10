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
				:key="version.mtime"
				class="version"
				:title="version.title"
				:href="version.url">
				<template #icon>
					<img lazy="true"
						:src="version.preview"
						alt=""
						height="256"
						width="256"
						class="version__image">
				</template>
				<template #subtitle>
					<div class="version__info">
						<span>{{ version.mtime | humanDateFromNow }}</span>
						<!-- Separate dot to improve alignement -->
						<span class="version__info__size">•</span>
						<span class="version__info__size">{{ version.size | humanReadableSize }}</span>
					</div>
				</template>
				<template v-if="!version.isCurrent" #actions>
					<NcActionLink :href="version.url"
						:download="version.url">
						<template #icon>
							<Download :size="22" />
						</template>
						{{ t('files_versions', 'Download version') }}
					</NcActionLink>
					<NcActionButton @click="restoreVersion(version)">
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
import BackupRestore from 'vue-material-design-icons/BackupRestore.vue'
import Download from 'vue-material-design-icons/Download.vue'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionLink from '@nextcloud/vue/dist/Components/NcActionLink.js'
import NcListItem from '@nextcloud/vue/dist/Components/NcListItem.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { fetchVersions, restoreVersion } from '../utils/versions.js'
import moment from '@nextcloud/moment'

export default {
	name: 'VersionTab',
	components: {
		NcEmptyContent,
		NcActionLink,
		NcActionButton,
		NcListItem,
		BackupRestore,
		Download,
	},
	filters: {
		humanReadableSize(bytes) {
			return OC.Util.humanFileSize(bytes)
		},
		humanDateFromNow(timestamp) {
			return moment(timestamp * 1000).fromNow()
		},
	},
	data() {
		return {
			fileInfo: null,
			/** @type {import('../utils/versions.js').Version[]} */
			versions: [],
			loading: false,
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
			try {
				this.loading = true
				this.versions = await fetchVersions(this.fileInfo)
			} finally {
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
				await restoreVersion(version, this.fileInfo)
				// File info is not updated so we manually update its size and mtime if the restoration went fine.
				this.fileInfo.size = version.size
				this.fileInfo.mtime = version.lastmod
				showSuccess(t('files_versions', 'Version restored'))
				await this.fetchVersions()
			} catch (exception) {
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

	&__info {
		display: flex;
		flex-direction: row;
		align-items: center;
		gap: 0.5rem;

		&__size {
			color: var(--color-text-lighter);
		}
	}

	&__image {
		width: 3rem;
		height: 3rem;
		border: 1px solid var(--color-border);
		margin-right: 1rem;
		border-radius: var(--border-radius-large);
	}
}
</style>
