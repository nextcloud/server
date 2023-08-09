<!--
  - @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
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
	<NcAppContent data-cy-files-content>
		<div class="files-list__header">
			<!-- Current folder breadcrumbs -->
			<BreadCrumbs :path="dir" @reload="fetchContent" />

			<!-- Secondary loading indicator -->
			<NcLoadingIcon v-if="isRefreshing" class="files-list__refresh-icon" />
		</div>

		<!-- Initial loading -->
		<NcLoadingIcon v-if="loading && !isRefreshing"
			class="files-list__loading-icon"
			:size="38"
			:name="t('files', 'Loading current folder')" />

		<!-- Empty content placeholder -->
		<NcEmptyContent v-else-if="!loading && isEmptyDir"
			:name="currentView?.emptyTitle || t('files', 'No files in here')"
			:description="currentView?.emptyCaption || t('files', 'Upload some content or sync with your devices!')"
			data-cy-files-content-empty>
			<template #action>
				<NcButton v-if="dir !== '/'"
					aria-label="t('files', 'Go to the previous folder')"
					type="primary"
					:to="toPreviousDir">
					{{ t('files', 'Go back') }}
				</NcButton>
			</template>
			<template #icon>
				<NcIconSvgWrapper :svg="currentView.icon" />
			</template>
		</NcEmptyContent>

		<!-- File list -->
		<FilesListVirtual v-else
			ref="filesListVirtual"
			:current-folder="currentFolder"
			:current-view="currentView"
			:nodes="dirContents" />
	</NcAppContent>
</template>

<script lang="ts">
import type { Route } from 'vue-router'
import type { Navigation, ContentsWithRoot } from '../services/Navigation.ts'
import type { UserConfig } from '../types.ts'

import { Folder, Node } from '@nextcloud/files'
import { join } from 'path'
import { orderBy } from 'natural-orderby'
import { translate } from '@nextcloud/l10n'
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import Vue from 'vue'

import { useFilesStore } from '../store/files.ts'
import { usePathsStore } from '../store/paths.ts'
import { useSelectionStore } from '../store/selection.ts'
import { useUserConfigStore } from '../store/userconfig.ts'
import { useViewConfigStore } from '../store/viewConfig.ts'
import BreadCrumbs from '../components/BreadCrumbs.vue'
import FilesListVirtual from '../components/FilesListVirtual.vue'
import filesSortingMixin from '../mixins/filesSorting.ts'
import logger from '../logger.js'

export default Vue.extend({
	name: 'FilesList',

	components: {
		BreadCrumbs,
		FilesListVirtual,
		NcAppContent,
		NcButton,
		NcEmptyContent,
		NcIconSvgWrapper,
		NcLoadingIcon,
	},

	mixins: [
		filesSortingMixin,
	],

	setup() {
		const filesStore = useFilesStore()
		const pathsStore = usePathsStore()
		const selectionStore = useSelectionStore()
		const userConfigStore = useUserConfigStore()
		const viewConfigStore = useViewConfigStore()
		return {
			filesStore,
			pathsStore,
			selectionStore,
			userConfigStore,
			viewConfigStore,
		}
	},

	data() {
		return {
			loading: true,
			promise: null,
		}
	},

	computed: {
		userConfig(): UserConfig {
			return this.userConfigStore.userConfig
		},

		currentView(): Navigation {
			return (this.$navigation.active
				|| this.$navigation.views.find(view => view.id === 'files')) as Navigation
		},

		/**
		 * The current directory query.
		 */
		dir(): string {
			// Remove any trailing slash but leave root slash
			return (this.$route?.query?.dir || '/').replace(/^(.+)\/$/, '$1')
		},

		/**
		 * The current folder.
		 */
		currentFolder(): Folder|undefined {
			if (!this.currentView?.id) {
				return
			}

			if (this.dir === '/') {
				return this.filesStore.getRoot(this.currentView.id)
			}
			const fileId = this.pathsStore.getPath(this.currentView.id, this.dir)
			return this.filesStore.getNode(fileId)
		},

		/**
		 * The current directory contents.
		 */
		dirContents(): Node[] {
			if (!this.currentView) {
				return []
			}

			const customColumn = (this.currentView?.columns || [])
				.find(column => column.id === this.sortingMode)

			// Custom column must provide their own sorting methods
			if (customColumn?.sort && typeof customColumn.sort === 'function') {
				const results = [...(this.currentFolder?._children || []).map(this.getNode).filter(file => file)]
					.sort(customColumn.sort)
				return this.isAscSorting ? results : results.reverse()
			}

			const identifiers = [
				// Sort favorites first if enabled
				...this.userConfig.sort_favorites_first ? [v => v.attributes?.favorite !== 1] : [],
				// Sort folders first if sorting by name
				...this.sortingMode === 'basename' ? [v => v.type !== 'folder'] : [],
				// Use sorting mode if NOT basename (to be able to use displayName too)
				...this.sortingMode !== 'basename' ? [v => v[this.sortingMode]] : [],
				// Use displayName if available, fallback to name
				v => v.attributes?.displayName || v.basename,
				// Finally, use basename if all previous sorting methods failed
				v => v.basename,
			]
			const orders = new Array(identifiers.length).fill(this.isAscSorting ? 'asc' : 'desc')

			return orderBy(
				[...(this.currentFolder?._children || []).map(this.getNode).filter(file => file)],
				identifiers,
				orders,
			)
		},

		/**
		 * The current directory is empty.
		 */
		isEmptyDir(): boolean {
			return this.dirContents.length === 0
		},

		/**
		 * We are refreshing the current directory.
		 * But we already have a cached version of it
		 * that is not empty.
		 */
		isRefreshing(): boolean {
			return this.currentFolder !== undefined
				&& !this.isEmptyDir
				&& this.loading
		},

		/**
		 * Route to the previous directory.
		 */
		toPreviousDir(): Route {
			const dir = this.dir.split('/').slice(0, -1).join('/') || '/'
			return { ...this.$route, query: { dir } }
		},
	},

	watch: {
		currentView(newView, oldView) {
			if (newView?.id === oldView?.id) {
				return
			}

			logger.debug('View changed', { newView, oldView })
			this.selectionStore.reset()
			this.fetchContent()
		},

		dir(newDir, oldDir) {
			logger.debug('Directory changed', { newDir, oldDir })
			// TODO: preserve selection on browsing?
			this.selectionStore.reset()
			this.fetchContent()

			// Scroll to top, force virtual scroller to re-render
			if (this.$refs?.filesListVirtual?.$el) {
				this.$refs.filesListVirtual.$el.scrollTop = 0
			}
		},
	},

	methods: {
		async fetchContent() {
			this.loading = true
			const dir = this.dir
			const currentView = this.currentView

			// If we have a cancellable promise ongoing, cancel it
			if (typeof this.promise?.cancel === 'function') {
				this.promise.cancel()
				logger.debug('Cancelled previous ongoing fetch')
			}

			// Fetch the current dir contents
			this.promise = currentView.getContents(dir) as Promise<ContentsWithRoot>
			try {
				const { folder, contents } = await this.promise
				logger.debug('Fetched contents', { dir, folder, contents })

				// Update store
				this.filesStore.updateNodes(contents)

				// Define current directory children
				folder._children = contents.map(node => node.fileid)

				// If we're in the root dir, define the root
				if (dir === '/') {
					this.filesStore.setRoot({ service: currentView.id, root: folder })
				} else {
					// Otherwise, add the folder to the store
					if (folder.fileid) {
						this.filesStore.updateNodes([folder])
						this.pathsStore.addPath({ service: currentView.id, fileid: folder.fileid, path: dir })
					} else {
						// If we're here, the view API messed up
						logger.error('Invalid root folder returned', { dir, folder, currentView })
					}
				}

				// Update paths store
				const folders = contents.filter(node => node.type === 'folder')
				folders.forEach(node => {
					this.pathsStore.addPath({ service: currentView.id, fileid: node.fileid, path: join(dir, node.basename) })
				})
			} catch (error) {
				logger.error('Error while fetching content', { error })
			} finally {
				this.loading = false
			}

		},

		/**
		 * Get a cached note from the store
		 *
		 * @param {number} fileId the file id to get
		 * @return {Folder|File}
		 */
		 getNode(fileId) {
			return this.filesStore.getNode(fileId)
		},

		t: translate,
	},
})
</script>

<style scoped lang="scss">
.app-content {
	// Virtual list needs to be full height and is scrollable
	display: flex;
	overflow: hidden;
	flex-direction: column;
	max-height: 100%;
}

$margin: 4px;
$navigationToggleSize: 50px;

.files-list {
	&__header {
		display: flex;
		align-content: center;
		// Do not grow or shrink (vertically)
		flex: 0 0;
		// Align with the navigation toggle icon
		margin: $margin $margin $margin $navigationToggleSize;
		> * {
			// Do not grow or shrink (horizontally)
			// Only the breadcrumbs shrinks
			flex: 0 0;
		}
	}
	&__refresh-icon {
		flex: 0 0 44px;
		width: 44px;
		height: 44px;
	}
	&__loading-icon {
		margin: auto;
	}
}

</style>
