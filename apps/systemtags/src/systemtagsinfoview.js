/**
 * Copyright (c) 2015
 *
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

(function(OCA) {

	/**
	 * @param {any} model -
	 */
	function modelToSelection(model) {
		const data = model.toJSON()
		if (!OC.isUserAdmin() && !data.canAssign) {
			data.locked = true
		}
		return data
	}

	/**
	 * @class OCA.SystemTags.SystemTagsInfoView
	 * @classdesc
	 *
	 * Displays a file's system tags
	 *
	 */
	const SystemTagsInfoView = OCA.Files.DetailFileInfoView.extend(
		/** @lends OCA.SystemTags.SystemTagsInfoView.prototype */ {

			_rendered: false,

			className: 'systemTagsInfoView',
			name: 'systemTags',

			/* required by the new files sidebar to check if the view is unique */
			id: 'systemTagsInfoView',

			/**
			 * @type {OC.SystemTags.SystemTagsInputField}
			 */
			_inputView: null,

			initialize(options) {
				const self = this
				options = options || {}

				this._inputView = new OC.SystemTags.SystemTagsInputField({
					multiple: true,
					allowActions: true,
					allowCreate: true,
					isAdmin: OC.isUserAdmin(),
					initSelection(element, callback) {
						callback(self.selectedTagsCollection.map(modelToSelection))
					},
				})

				this.selectedTagsCollection = new OC.SystemTags.SystemTagsMappingCollection([], { objectType: 'files' })

				this._inputView.collection.on('change:name', this._onTagRenamedGlobally, this)
				this._inputView.collection.on('remove', this._onTagDeletedGlobally, this)

				this._inputView.on('select', this._onSelectTag, this)
				this._inputView.on('deselect', this._onDeselectTag, this)
			},

			/**
			 * Event handler whenever a tag was selected
			 *
			 * @param {object} tag the tag to create
			 */
			_onSelectTag(tag) {
			// create a mapping entry for this tag
				this.selectedTagsCollection.create(tag.toJSON())
			},

			/**
			 * Event handler whenever a tag gets deselected.
			 * Removes the selected tag from the mapping collection.
			 *
			 * @param {string} tagId tag id
			 */
			_onDeselectTag(tagId) {
				this.selectedTagsCollection.get(tagId).destroy()
			},

			/**
			 * Event handler whenever a tag was renamed globally.
			 *
			 * This will automatically adjust the tag mapping collection to
			 * container the new name.
			 *
			 * @param {OC.Backbone.Model} changedTag tag model that has changed
			 */
			_onTagRenamedGlobally(changedTag) {
			// also rename it in the selection, if applicable
				const selectedTagMapping = this.selectedTagsCollection.get(changedTag.id)
				if (selectedTagMapping) {
					selectedTagMapping.set(changedTag.toJSON())
				}
			},

			/**
			 * Event handler whenever a tag was deleted globally.
			 *
			 * This will automatically adjust the tag mapping collection to
			 * container the new name.
			 *
			 * @param {OC.Backbone.Model} tagId tag model that has changed
			 */
			_onTagDeletedGlobally(tagId) {
			// also rename it in the selection, if applicable
				this.selectedTagsCollection.remove(tagId)
			},

			setFileInfo(fileInfo) {
				const self = this
				if (!this._rendered) {
					this.render()
				}

				if (fileInfo) {
					this.selectedTagsCollection.setObjectId(fileInfo.id)
					this.selectedTagsCollection.fetch({
						success(collection) {
							collection.fetched = true

							const appliedTags = collection.map(modelToSelection)
							self._inputView.setData(appliedTags)
							if (appliedTags.length > 0) {
								self.show()
							}
						},
					})
				}

				this.hide()
			},

			/**
			 * Renders this details view
			 */
			render() {
				this.$el.append(this._inputView.$el)
				this._inputView.render()
			},

			isVisible() {
				return !this.$el.hasClass('hidden')
			},

			show() {
				this.$el.removeClass('hidden')
			},

			hide() {
				this.$el.addClass('hidden')
			},

			toggle() {
				this.$el.toggleClass('hidden')
			},

			openDropdown() {
				this.$el.find('.systemTagsInputField').select2('open')
			},

			remove() {
				this._inputView.remove()
			},
		})

	OCA.SystemTags.SystemTagsInfoView = SystemTagsInfoView

})(OCA)
