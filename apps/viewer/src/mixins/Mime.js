/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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

export default {
	props: {
		active: {
			type: Boolean,
			default: false
		},
		path: {
			type: String,
			required: true
		},
		davPath: {
			type: String,
			required: true
		},
		mime: {
			type: String,
			required: true
		},
		canSwipe: {
			type: Boolean,
			default: true
		},
		loaded: {
			type: Boolean,
			default: false
		},
		sidebarShown: {
			type: Boolean,
			default: false
		}
	},

	data() {
		return {
			height: null,
			width: null,
			naturalHeight: null,
			naturalWidth: null,
			isLoaded: false
		}
	},

	watch: {
		active: function(val, old) {
			// the item was hidden before and is now the current view
			if (val === true && old === false) {
				// just in case the file was preloaded, let's warn the viewer
				if (this.isLoaded) {
					this.doneLoading()
				}
			}
		},
		// update image size on SIDEBARsidebar
		sidebarShown: function() {
			// wait for transition to complete (100ms)
			setTimeout(this.updateHeightWidth, 150)
		}
	},

	mounted() {
		// detect error and let the viewer know
		this.$el.addEventListener('error', e => {
			console.error('Error loading', this.path, e)
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
				const wrapperMaxHeight = Number(window.getComputedStyle(modalContainer).maxHeight.replace('%', ''))
				const wrapperMaxWidth = Number(window.getComputedStyle(modalContainer).maxWidth.replace('%', ''))

				const parentHeight = Math.round(modalWrapper.clientHeight * wrapperMaxHeight / 100) - 50 // minus header
				const parentWidth = Math.round(modalWrapper.clientWidth * wrapperMaxWidth / 100)

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
		}
	}
}
