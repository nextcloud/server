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
		}
	},

	data() {
		return {
			height: null,
			width: null,
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
		}
	},

	mounted() {
		this.$el.addEventListener('error', e => {
			console.error('Error loading', this.path, e)
			this.$emit('error', e)
		})
	},

	methods: {
		doneLoading(event) {
			// send the current state
			this.$emit('update:loaded', true)
			// save the current state
			this.isLoaded = true
		},
		updateHeightWidth(contentHeight, contentWidth) {
			const modalContainer = this.$parent.$el.querySelector('.modal-wrapper')
			if (modalContainer) {
				const wrapperMaxHeight = window.getComputedStyle(modalContainer.children[0]).maxHeight.replace('%', '')
				const wrapperMaxWidth = window.getComputedStyle(modalContainer.children[0]).maxWidth.replace('%', '')

				const parentHeight = Math.round(modalContainer.clientHeight * Number(wrapperMaxHeight) / 100) - 50 // minus header
				const parentWidth = Math.round(modalContainer.clientWidth * Number(wrapperMaxWidth) / 100)

				const heightRatio = parentHeight / contentHeight
				const widthRatio = parentWidth / contentWidth

				// if the video height is capped by the parent height
				// AND the video is bigger than the parent
				if (heightRatio < widthRatio && heightRatio < 1) {
					this.height = parentHeight
					this.width = Math.round(contentWidth / contentHeight * parentHeight)

				// if the video width is capped by the parent width
				// AND the video is bigger than the parent
				} else if (heightRatio > widthRatio && widthRatio < 1) {
					this.width = parentWidth
					this.height = Math.round(contentHeight / contentWidth * parentWidth)

				// RESET
				} else {
					this.height = contentHeight
					this.width = contentWidth
				}
			}
		},
		enableSwipe() {
			this.$emit('update:canSwipe', true)
		},
		disableSwipe() {
			this.$emit('update:canSwipe', false)
		}
	}
}
