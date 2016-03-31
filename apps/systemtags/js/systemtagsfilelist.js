/*
 * Copyright (c) 2016 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function() {
	/**
	 * @class OCA.SystemTags.FileList
	 * @augments OCA.Files.FileList
	 *
	 * @classdesc SystemTags file list.
	 * Contains a list of files filtered by system tags.
	 *
	 * @param $el container element with existing markup for the #controls
	 * and a table
	 * @param [options] map of options, see other parameters
	 * @param {Array.<string>} [options.systemTagIds] array of system tag ids to
	 * filter by
	 */
	var FileList = function($el, options) {
		this.initialize($el, options);
	};
	FileList.prototype = _.extend({}, OCA.Files.FileList.prototype,
		/** @lends OCA.SystemTags.FileList.prototype */ {
		id: 'systemtagsfilter',
		appName: t('systemtags', 'Tagged files'),

		/**
		 * Array of system tag ids to filter by
		 *
		 * @type Array.<string>
		 */
		_systemTagIds: [],

		_clientSideSort: true,
		_allowSelection: false,

		_filterField: null,

		/**
		 * @private
		 */
		initialize: function($el, options) {
			OCA.Files.FileList.prototype.initialize.apply(this, arguments);
			if (this.initialized) {
				return;
			}

			if (options && options.systemTagIds) {
				this._systemTagIds = options.systemTagIds;
			}

			OC.Plugins.attach('OCA.SystemTags.FileList', this);

			var $controls = this.$el.find('#controls').empty();

			this._initFilterField($controls);
		},
		
		destroy: function() {
			this.$filterField.remove();

			OCA.Files.FileList.prototype.destroy.apply(this, arguments);
		},

		_initFilterField: function($container) {
			this.$filterField = $('<input type="hidden" name="tags"/>');
			$container.append(this.$filterField);
			this.$filterField.select2({
				placeholder: t('systemtags', 'Select tags to filter by'),
				allowClear: false,
				multiple: true,
				separator: ',',
				query: _.bind(this._queryTagsAutocomplete, this),

				id: function(tag) {
					return tag.id;
				},

				initSelection: function(element, callback) {
					var val = $(element).val().trim();
					if (val) {
						var tagIds = val.split(','),
							tags = [];

						OC.SystemTags.collection.fetch({
							success: function() {
								_.each(tagIds, function(tagId) {
									var tag = OC.SystemTags.collection.get(tagId);
									if (!_.isUndefined(tag)) {
										tags.push(tag.toJSON());
									}
								});

								callback(tags);
							}
						});
					} else {
						callback([]);
					}
				},

				formatResult: function (tag) {
					return OC.SystemTags.getDescriptiveTag(tag);
				},

				formatSelection: function (tag) {
					return OC.SystemTags.getDescriptiveTag(tag)[0].outerHTML;
				},

				escapeMarkup: function(m) {
					// prevent double markup escape
					return m;
				}
			});
			this.$filterField.on('change', _.bind(this._onTagsChanged, this));
			return this.$filterField;
		},

		/**
		 * Autocomplete function for dropdown results
		 *
		 * @param {Object} query select2 query object
		 */
		_queryTagsAutocomplete: function(query) {
			OC.SystemTags.collection.fetch({
				success: function() {
					var results = OC.SystemTags.collection.filterByName(query.term);

					query.callback({
						results: _.invoke(results, 'toJSON')
					});
				}
			});
		},

		/**
		 * Event handler for when the URL changed
		 */
		_onUrlChanged: function(e) {
			if (e.dir) {
				var tags = _.filter(e.dir.split('/'), function(val) { return val.trim() !== ''; });
				this.$filterField.select2('val', tags || []);
				this._systemTagIds = tags;
				this.reload();
			}
		},

		_onTagsChanged: function(ev) {
			var val = $(ev.target).val().trim();
			if (val !== '') {
				this._systemTagIds = val.split(',');
			} else {
				this._systemTagIds = [];
			}

			this.$el.trigger(jQuery.Event('changeDirectory', {
				dir: this._systemTagIds.join('/')
			}));
			this.reload();
		},

		updateEmptyContent: function() {
			var dir = this.getCurrentDirectory();
			if (dir === '/') {
				// root has special permissions
				if (!this._systemTagIds.length) {
					// no tags selected
					this.$el.find('#emptycontent').html('<div class="icon-systemtags"></div>' +
						'<h2>' + t('systemtags', 'Please select tags to filter by') + '</h2>');
				} else {
					// tags selected but no results
					this.$el.find('#emptycontent').html('<div class="icon-systemtags"></div>' +
						'<h2>' + t('systemtags', 'No files found for the selected tags') + '</h2>');
				}
				this.$el.find('#emptycontent').toggleClass('hidden', !this.isEmpty);
				this.$el.find('#filestable thead th').toggleClass('hidden', this.isEmpty);
			}
			else {
				OCA.Files.FileList.prototype.updateEmptyContent.apply(this, arguments);
			}
		},

		getDirectoryPermissions: function() {
			return OC.PERMISSION_READ | OC.PERMISSION_DELETE;
		},

		updateStorageStatistics: function() {
			// no op because it doesn't have
			// storage info like free space / used space
		},

		reload: function() {
			if (!this._systemTagIds.length) {
				// don't reload
				this.updateEmptyContent();
				this.setFiles([]);
				return $.Deferred().resolve();
			}

			this._selectedFiles = {};
			this._selectionSummary.clear();
			if (this._currentFileModel) {
				this._currentFileModel.off();
			}
			this._currentFileModel = null;
			this.$el.find('.select-all').prop('checked', false);
			this.showMask();
			this._reloadCall = this.filesClient.getFilteredFiles(
				{
					systemTagIds: this._systemTagIds
				},
				{
					properties: this._getWebdavProperties()
				}
			);
			if (this._detailsView) {
				// close sidebar
				this._updateDetailsView(null);
			}
			var callBack = this.reloadCallback.bind(this);
			return this._reloadCall.then(callBack, callBack);
		},

		reloadCallback: function(status, result) {
			if (result) {
				// prepend empty dir info because original handler
				result.unshift({});
			}

			return OCA.Files.FileList.prototype.reloadCallback.call(this, status, result);
		}
	});

	OCA.SystemTags.FileList = FileList;
})();
