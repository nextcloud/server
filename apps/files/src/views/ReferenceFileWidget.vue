<!--
  - @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<div v-if="!accessible" class="widget-file widget-file--no-access">
		<div class="widget-file--image widget-file--image--icon icon-folder" />
		<div class="widget-file--details">
			<p class="widget-file--title">
				{{ t('files', 'File cannot be accessed') }}
			</p>
			<p class="widget-file--description">
				{{ t('files', 'You might not have have permissions to view it, ask the sender to share it') }}
			</p>
		</div>
	</div>
	<a v-else
		class="widget-file"
		:href="richObject.link"
		@click.prevent="navigate">
		<div class="widget-file--image" :class="filePreviewClass" :style="filePreview" />
		<div class="widget-file--details">
			<p class="widget-file--title">{{ richObject.name }}</p>
			<p class="widget-file--description">{{ fileSize }}<br>{{ fileMtime }}</p>
			<p class="widget-file--link">{{ filePath }}</p>
		</div>
	</a>
</template>
<script>
import { generateUrl } from '@nextcloud/router'
import path from 'path'

export default {
	name: 'ReferenceFileWidget',
	props: {
		richObject: {
			type: Object,
			required: true,
		},
		accessible: {
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			previewUrl: window.OC.MimeType.getIconUrl(this.richObject.mimetype),
		}
	},
	computed: {
		fileSize() {
			return window.OC.Util.humanFileSize(this.richObject.size)
		},
		fileMtime() {
			return window.OC.Util.relativeModifiedDate(this.richObject.mtime * 1000)
		},
		filePath() {
			return path.dirname(this.richObject.path)
		},
		filePreview() {
			if (this.previewUrl) {
				return {
					backgroundImage: 'url(' + this.previewUrl + ')',
				}
			}

			return {
				backgroundImage: 'url(' + window.OC.MimeType.getIconUrl(this.richObject.mimetype) + ')',
			}

		},
		filePreviewClass() {
			if (this.previewUrl) {
				return 'widget-file--image--preview'
			}
			return 'widget-file--image--icon'

		},
	},
	mounted() {
		if (this.richObject['preview-available']) {
			const previewUrl = generateUrl('/core/preview?fileId={fileId}&x=250&y=250', {
				fileId: this.richObject.id,
			})
			const img = new Image()
			img.onload = () => {
				this.previewUrl = previewUrl
			}
			img.onerror = err => {
				console.error('could not load recommendation preview', err)
			}
			img.src = previewUrl
		}
	},
	methods: {
		navigate() {
			if (OCA.Viewer && OCA.Viewer.mimetypes.indexOf(this.richObject.mimetype) !== -1) {
				OCA.Viewer.open({ path: this.richObject.path })
				return
			}
			window.location = this.richObject.link
		},
	},
}
</script>
<style lang="scss" scoped>
.widget-file {
	display: flex;
	flex-grow: 1;
	color: var(--color-main-text) !important;
	text-decoration: none !important;

	&--image {
		min-width: 40%;
		background-position: center;
		background-size: cover;
		background-repeat: no-repeat;

		&.widget-file--image--icon {
			min-width: 88px;
			background-size: 44px;
		}
	}

	&--title {
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		font-weight: bold;
	}

	&--details {
		padding: 12px;
		flex-grow: 1;
		display: flex;
		flex-direction: column;

		p {
			margin: 0;
			padding: 0;
		}
	}

	&--description {
		overflow: hidden;
		text-overflow: ellipsis;
		display: -webkit-box;
		-webkit-line-clamp: 3;
		line-clamp: 3;
		-webkit-box-orient: vertical;
	}

	&--link {
		color: var(--color-text-maxcontrast);
	}

	&.widget-file--no-access {
		padding: 12px;

		.widget-file--details {
			padding: 0;
		}
	}
}
</style>
