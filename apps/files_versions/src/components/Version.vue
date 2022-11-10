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
	<NcListItem class="version"
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
		<template v-if="!isCurrent" #actions>
			<NcActionButton v-if="experimental"
				:close-after-click="true"
				@click="openVersionNameModal">
				<template #icon>
					<Pen :size="22" />
				</template>
				{{ version.title === '' ? t('files_versions', 'Name this version') : t('files_versions', 'Edit version name') }}
			</NcActionButton>
			<NcModal v-if="showVersionNameEditor"
				:title="t('files_versions', 'Name this version')"
				:close-button-contained="false"
				@close="showVersionNameEditor = false">
				<div class="modal">
					<NcTextField ref="nameInput"
						:value.sync="versionName"
						:label="t('photos', 'Named versions are persisted, and excluded from automatic cleanups when your storage quota is full.')"
						:placeholder="t('photos', 'Version name')"
						:label-visible="true" />
					<div class="modal__actions">
						<NcButton @click="setVersionName('')">
							{{ t('files_versions', 'Remove version name') }}
						</NcButton>
						<NcButton type="primary" :disabled="versionName.trim().length === 0" @click="setVersionName(versionName)">
							<template #icon>
								<ContentSave />
							</template>
							{{ t('files_versions', 'Save version name') }}
						</NcButton>
					</div>
				</div>
			</NcModal>
			<NcActionLink :href="version.url"
				:close-after-click="true"
				:download="version.url">
				<template #icon>
					<Download :size="22" />
				</template>
				{{ t('files_versions', 'Download version') }}
			</NcActionLink>
			<NcActionButton :close-after-click="true"
				@click="restoreVersion">
				<template #icon>
					<BackupRestore :size="22" />
				</template>
				{{ t('files_versions', 'Restore version') }}
			</NcActionButton>
			<NcActionButton v-if="experimental"
				:close-after-click="true"
				@click="deleteVersion">
				<template #icon>
					<Delete :size="22" />
				</template>
				{{ t('files_versions', 'Delete version') }}
			</NcActionButton>
		</template>
	</NcListItem>
</template>

<script>
import BackupRestore from 'vue-material-design-icons/BackupRestore.vue'
import Download from 'vue-material-design-icons/Download.vue'
import Pen from 'vue-material-design-icons/Pen.vue'
import ContentSave from 'vue-material-design-icons/ContentSave.vue'
import Delete from 'vue-material-design-icons/Delete'
import { NcActionButton, NcActionLink, NcListItem, NcModal, NcButton, NcTextField } from '@nextcloud/vue'
import moment from '@nextcloud/moment'

export default {
	name: 'Version',
	components: {
		NcActionLink,
		NcActionButton,
		NcListItem,
		NcModal,
		NcButton,
		NcTextField,
		BackupRestore,
		Download,
		Pen,
		ContentSave,
		Delete,
	},
	filters: {
		humanReadableSize(bytes) {
			return OC.Util.humanFileSize(bytes)
		},
		humanDateFromNow(timestamp) {
			return moment(timestamp).fromNow()
		},
	},
	props: {
		/** @type {Vue.PropOptions<import('../utils/versions.js').Version>} */
		version: {
			type: Object,
			required: true,
		},
		isCurrent: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			showVersionNameEditor: false,
			versionName: this.version.title,
			experimental: OC.experimental ?? false,
		}
	},
	methods: {
		openVersionNameModal() {
			this.showVersionNameEditor = true
			this.$nextTick(() => {
				this.$refs.nameInput.$el.getElementsByTagName('input')[0].focus()
			})
		},

		restoreVersion() {
			this.$emit('restore', this.version)
		},

		setVersionName(name) {
			this.showVersionNameEditor = false
			this.$emit('name-update', this.version, name)
		},

		deleteVersion() {
			this.$emit('delete', this.version)
		},
	},
}
</script>

<style scoped lang="scss">
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

.modal {
	display: flex;
	justify-content: space-between;
	flex-direction: column;
	height: 250px;
	padding: 16px;

	&__actions {
		display: flex;
		justify-content: space-between;
		margin-top: 64px;
	}
}
</style>
