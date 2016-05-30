/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function(OCA) {

	function modelToSelection(model) {
		var data = model.toJSON();
		if (!OC.isUserAdmin() && !data.canAssign) {
			data.locked = true;
		}
		return data;
	}

	/**
	 * @class OCA.SystemTags.SystemTagsInfoView
	 * @classdesc
	 *
	 * Displays a file's system tags
	 *
	 */
	var SystemTagsInfoView = OCA.Files.DetailFileInfoView.extend(
		/** @lends OCA.SystemTags.SystemTagsInfoView.prototype */ {

		_rendered: false,

		className: 'systemTagsInfoView hidden',

		/**
		 * @type OC.SystemTags.SystemTagsInputField
		 */
		_inputView: null,

		initialize: function(options) {
			var self = this;
			options = options || {};

			this._inputView = new OC.SystemTags.SystemTagsInputField({
				multiple: true,
				allowActions: true,
				allowCreate: true,
				isAdmin: OC.isUserAdmin(),
				initSelection: function(element, callback) {
					callback(self.selectedTagsCollection.map(modelToSelection));
				}
			});

			this.selectedTagsCollection = new OC.SystemTags.SystemTagsMappingCollection([], {objectType: 'files'});

			this._inputView.collection.on('change:name', this._onTagRenamedGlobally, this);
			this._inputView.collection.on('remove', this._onTagDeletedGlobally, this);

			this._inputView.on('select', this._onSelectTag, this);
			this._inputView.on('deselect', this._onDeselectTag, this);
		},

		/**
		 * Event handler whenever a tag was selected
		 */
		_onSelectTag: function(tag) {
			// create a mapping entry for this tag
			this.selectedTagsCollection.create(tag.toJSON());
		},

		/**
		 * Event handler whenever a tag gets deselected.
		 * Removes the selected tag from the mapping collection.
		 *
		 * @param {string} tagId tag id
		 */
		_onDeselectTag: function(tagId) {
			this.selectedTagsCollection.get(tagId).destroy();
		},

		/**
		 * Event handler whenever a tag was renamed globally.
		 *
		 * This will automatically adjust the tag mapping collection to
		 * container the new name.
		 *
		 * @param {OC.Backbone.Model} changedTag tag model that has changed
		 */
		_onTagRenamedGlobally: function(changedTag) {
			// also rename it in the selection, if applicable
			var selectedTagMapping = this.selectedTagsCollection.get(changedTag.id);
			if (selectedTagMapping) {
				selectedTagMapping.set(changedTag.toJSON());
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
		_onTagDeletedGlobally: function(tagId) {
			// also rename it in the selection, if applicable
			this.selectedTagsCollection.remove(tagId);
		},

		setFileInfo: function(fileInfo) {
			var self = this;
			if (!this._rendered) {
				this.render();
			}

			if (fileInfo) {
				this.selectedTagsCollection.setObjectId(fileInfo.id);
				this.selectedTagsCollection.fetch({
					success: function(collection) {
						collection.fetched = true;
						self._inputView.setData(collection.map(modelToSelection));
						self.$el.removeClass('hidden');
					}
				});
			}
			this.$el.addClass('hidden');
		},

		/**
		 * Renders this details view
		 */
		render: function() {
			this.$el.append(this._inputView.$el);
			this._inputView.render();
		},

		remove: function() {
			this._inputView.remove();
		}
	});

	OCA.SystemTags.SystemTagsInfoView = SystemTagsInfoView;

})(OCA);

