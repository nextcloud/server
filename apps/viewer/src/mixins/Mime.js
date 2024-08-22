/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import debounce from 'debounce'
import PreviewUrl from '../mixins/PreviewUrl.js'
import parsePath from 'path-parse'

export default {
	inheritAttrs: false,
	mixins: [PreviewUrl],
	props: {
		// Is the current component shown
		active: {
			type: Boolean,
			default: false,
		},
		// file name
		basename: {
			type: String,
			required: true,
		},
		// file path relative to user folder
		filename: {
			type: String,
			required: true,
		},
		// file source to fetch contents from
		source: {
			type: String,
			default: undefined,
		},
		// URL the file preview
		previewUrl: {
			type: String,
			default: undefined,
		},
		// should the standard core preview be used?
		hasPreview: {
			type: Boolean,
			default: false,
		},
		// unique file id
		fileid: {
			type: [Number, String],
			required: false,
		},
		// list of all the visible files
		fileList: {
			type: Array,
			default: () => [],
		},
		// file mime (aliased if specified in the model)
		mime: {
			type: String,
			required: true,
		},
		// can the user swipe
		canSwipe: {
			type: Boolean,
			default: true,
		},
		canZoom: {
			type: Boolean,
			default: false,
		},
		// is the content loaded?
		// synced with parent
		loaded: {
			type: Boolean,
			default: false,
		},
		// is the sidebar currently opened ?
		isSidebarShown: {
			type: Boolean,
			default: false,
		},
		// are we in fullscreen mode ?
		isFullScreen: {
			type: Boolean,
			default: false,
		},
		// The file id of the peer live photo file
		metadataFilesLivePhoto: {
			type: Number,
			default: undefined,
		},
	},

	data() {
		return {
			height: null,
			width: null,
			naturalHeight: null,
			naturalWidth: null,
			isLoaded: false,
		}
	},

	computed: {
		name() {
			return parsePath(this.basename).name
		},
		ext() {
			return parsePath(this.basename).ext
		},
		src() {
			return this.source ?? this.davPath
		},
	},

	watch: {
		active(val, old) {
			// the item was hidden before and is now the current view
			if (val === true && old === false) {
				// just in case the file was preloaded, let's warn the viewer
				if (this.isLoaded) {
					this.doneLoading()
				}
			}
		},
		// update image size on sidebar toggle
		isSidebarShown() {
			// wait for transition to complete (100ms)
			setTimeout(this.updateHeightWidth, 200)
		},
	},

	mounted() {
		// detect error and let the viewer know
		this.$el.addEventListener('error', e => {
			console.error('Error loading', this.filename, e)
			this.$emit('error', e)
		})

		// update image size on window resize
		window.addEventListener('resize', debounce(() => {
			this.updateHeightWidth()
		}, 100))
	},

	methods: {

		/**
		 * This is used to make the viewer know this file is complete or ready
		 * ! you NEED to use it to make the viewer aware of the current loading state
		 */
		doneLoading() {
			// send the current state
			this.$emit('update:loaded', true)
			// save the current state
			this.isLoaded = true
		},

		/**
		 * Updates the current height and width data
		 * based on the viewer maximum size
		 */
		updateHeightWidth() {
			const modalWrapper = this.$parent.$el.querySelector('.modal-wrapper')
			if (modalWrapper && this.naturalHeight > 0 && this.naturalWidth > 0) {
				const modalContainer = modalWrapper.querySelector('.modal-container')

				const parentHeight = modalContainer.clientHeight
				const parentWidth = modalContainer.clientWidth

				const heightRatio = parentHeight / this.naturalHeight
				const widthRatio = parentWidth / this.naturalWidth

				// if the video height is capped by the parent height
				// AND the video is bigger than the parent
				if (heightRatio < widthRatio && heightRatio < 1) {
					this.height = parentHeight
					this.width = Math.round(this.naturalWidth / this.naturalHeight * parentHeight)

				// if the video width is capped by the parent width
				// AND the video is bigger than the parent
				} else if (heightRatio > widthRatio && widthRatio < 1) {
					this.width = parentWidth
					this.height = Math.round(this.naturalHeight / this.naturalWidth * parentWidth)

				// RESET
				} else {
					this.height = this.naturalHeight
					this.width = this.naturalWidth
				}
			} else {
				this.height = this.naturalHeight
				this.width = this.naturalWidth
			}
		},

		/**
		 * Enable the viewer swiping previous/next capability
		 */
		enableSwipe() {
			this.$emit('update:canSwipe', true)
		},

		/**
		 * Disable the viewer swiping previous/next capability
		 */
		disableSwipe() {
			this.$emit('update:canSwipe', false)
		},

		/**
		 * Toggle the fullscreen on the current visible element
		 */
		toggleFullScreen() {
			if (this.isFullScreen) {
				document.exitFullscreen()
			} else {
				this.$el.requestFullscreen()
			}
		},
	},
}
