/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* @global Handlebars */

(function() {
	var TEMPLATE_ITEM =
		'<li data-revision="{{timestamp}}">' +
		'<div>' +
		'<div class="preview-container">' +
		'<img class="preview" src="{{previewUrl}}" width="44" height="44"/>' +
		'</div>' +
		'<div class="version-container">' +
		'<div>' +
		'<a href="{{downloadUrl}}" class="downloadVersion"><img src="{{downloadIconUrl}}" />' +
		'<span class="versiondate has-tooltip live-relative-timestamp" data-timestamp="{{millisecondsTimestamp}}" title="{{formattedTimestamp}}">{{relativeTimestamp}}</span>' +
		'</a>' +
		'</div>' +
		'{{#hasDetails}}' +
		'<div class="version-details">' +
		'<span class="size has-tooltip" title="{{altSize}}">{{humanReadableSize}}</span>' +
		'</div>' +
		'{{/hasDetails}}' +
		'</div>' +
		'{{#canRevert}}' +
		'<a href="#" class="revertVersion" title="{{revertLabel}}"><img src="{{revertIconUrl}}" /></a>' +
		'{{/canRevert}}' +
		'</div>' +
		'</li>';

	var TEMPLATE =
		'<ul class="versions"></ul>' +
		'<div class="clear-float"></div>' +
		'<div class="empty hidden">' +
		'<div class="emptycontent">' +
		'<div class="icon-history"></div>' +
		'<p>{{emptyResultLabel}}</p>' +
		'</div></div>' +
		'<input type="button" class="showMoreVersions hidden" value="{{moreVersionsLabel}}"' +
		' name="show-more-versions" id="show-more-versions" />' +
		'<div class="loading hidden" style="height: 50px"></div>';

	/**
	 * @memberof OCA.Versions
	 */
	var VersionsTabView = OCA.Files.DetailTabView.extend(
		/** @lends OCA.Versions.VersionsTabView.prototype */ {
		id: 'versionsTabView',
		className: 'tab versionsTabView',

		_template: null,

		$versionsContainer: null,

		events: {
			'click .revertVersion': '_onClickRevertVersion',
			'click .showMoreVersions': '_onClickShowMoreVersions'
		},

		initialize: function() {
			OCA.Files.DetailTabView.prototype.initialize.apply(this, arguments);
			this.collection = new OCA.Versions.VersionCollection();
			this.collection.on('request', this._onRequest, this);
			this.collection.on('sync', this._onEndRequest, this);
			this.collection.on('update', this._onUpdate, this);
			this.collection.on('error', this._onError, this);
			this.collection.on('add', this._onAddModel, this);
		},

		getLabel: function() {
			return t('files_versions', 'Versions');
		},

		nextPage: function() {
			if (this._loading || !this.collection.hasMoreResults()) {
				return;
			}

			if (this.collection.getFileInfo() && this.collection.getFileInfo().isDirectory()) {
				return;
			}
			this.collection.fetchNext();
		},

		_onClickShowMoreVersions: function(ev) {
			ev.preventDefault();
			this.nextPage();
		},

		_onClickRevertVersion: function(ev) {
			var self = this;
			var $target = $(ev.target);
			var fileInfoModel = this.collection.getFileInfo();
			var revision;
			if (!$target.is('li')) {
				$target = $target.closest('li');
			}

			ev.preventDefault();
			revision = $target.attr('data-revision');

			this.$el.find('.versions, .showMoreVersions').addClass('hidden');

			var versionModel = this.collection.get(revision);
			versionModel.revert({
				success: function() {
					// reset and re-fetch the updated collection
					self.$versionsContainer.empty();
					self.collection.setFileInfo(fileInfoModel);
					self.collection.reset([], {silent: true});
					self.collection.fetchNext();

					self.$el.find('.versions').removeClass('hidden');

					// update original model
					fileInfoModel.trigger('busy', fileInfoModel, false);
					fileInfoModel.set({
						size: versionModel.get('size'),
						mtime: versionModel.get('timestamp') * 1000,
						// temp dummy, until we can do a PROPFIND
						etag: versionModel.get('id') + versionModel.get('timestamp')
					});
				},

				error: function() {
					fileInfoModel.trigger('busy', fileInfoModel, false);
					self.$el.find('.versions').removeClass('hidden');
					self._toggleLoading(false);
					OC.Notification.show(t('files_version', 'Failed to revert {file} to revision {timestamp}.', 
						{
							file: versionModel.getFullPath(),
							timestamp: OC.Util.formatDate(versionModel.get('timestamp') * 1000)
						}),
						{
							type: 'error'
						}
					);
				}
			});

			// spinner
			this._toggleLoading(true);
			fileInfoModel.trigger('busy', fileInfoModel, true);
		},

		_toggleLoading: function(state) {
			this._loading = state;
			this.$el.find('.loading').toggleClass('hidden', !state);
		},

		_onRequest: function() {
			this._toggleLoading(true);
			this.$el.find('.showMoreVersions').addClass('hidden');
		},

		_onEndRequest: function() {
			this._toggleLoading(false);
			this.$el.find('.empty').toggleClass('hidden', !!this.collection.length);
			this.$el.find('.showMoreVersions').toggleClass('hidden', !this.collection.hasMoreResults());
		},

		_onAddModel: function(model) {
			var $el = $(this.itemTemplate(this._formatItem(model)));
			this.$versionsContainer.append($el);

			var preview = $el.find('.preview')[0];
			this._lazyLoadPreview({
				url: model.getPreviewUrl(),
				mime: model.get('mimetype'),
				callback: function(url) {
					preview.src = url;
				}
			});
			$el.find('.has-tooltip').tooltip();
		},

		template: function(data) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}

			return this._template(data);
		},

		itemTemplate: function(data) {
			if (!this._itemTemplate) {
				this._itemTemplate = Handlebars.compile(TEMPLATE_ITEM);
			}

			return this._itemTemplate(data);
		},

		setFileInfo: function(fileInfo) {
			if (fileInfo) {
				this.render();
				this.collection.setFileInfo(fileInfo);
				this.collection.reset([], {silent: true});
				this.nextPage();
			} else {
				this.render();
				this.collection.reset();
			}
		},

		_formatItem: function(version) {
			var timestamp = version.get('timestamp') * 1000;
			var size = version.has('size') ? version.get('size') : 0;
			return _.extend({
				millisecondsTimestamp: timestamp,
				formattedTimestamp: OC.Util.formatDate(timestamp),
				relativeTimestamp: OC.Util.relativeModifiedDate(timestamp),
				humanReadableSize: OC.Util.humanFileSize(size, true),
				altSize: n('files', '%n byte', '%n bytes', size),
				hasDetails: version.has('size'),
				downloadUrl: version.getDownloadUrl(),
				downloadIconUrl: OC.imagePath('core', 'actions/download'),
				revertIconUrl: OC.imagePath('core', 'actions/history'),
				revertLabel: t('files_versions', 'Restore'),
				canRevert: (this.collection.getFileInfo().get('permissions') & OC.PERMISSION_UPDATE) !== 0
			}, version.attributes);
		},

		/**
		 * Renders this details view
		 */
		render: function() {
			this.$el.html(this.template({
				emptyResultLabel: t('files_versions', 'No earlier versions available'),
				moreVersionsLabel: t('files_versions', 'More versions …')
			}));
			this.$el.find('.has-tooltip').tooltip();
			this.$versionsContainer = this.$el.find('ul.versions');
			this.delegateEvents();
		},

		/**
		 * Returns true for files, false for folders.
		 *
		 * @return {bool} true for files, false for folders
		 */
		canDisplay: function(fileInfo) {
			if (!fileInfo) {
				return false;
			}
			return !fileInfo.isDirectory();
		},

		/**
		 * Lazy load a file's preview.
		 *
		 * @param path path of the file
		 * @param mime mime type
		 * @param callback callback function to call when the image was loaded
		 * @param etag file etag (for caching)
		 */
		_lazyLoadPreview : function(options) {
			var url = options.url;
			var mime = options.mime;
			var ready = options.callback;

			// get mime icon url
			var iconURL = OC.MimeType.getIconUrl(mime);
			ready(iconURL); // set mimeicon URL

			var img = new Image();
			img.onload = function(){
				// if loading the preview image failed (no preview for the mimetype) then img.width will < 5
				if (img.width > 5) {
					ready(url, img);
				} else if (options.error) {
					options.error();
				}
			};
			if (options.error) {
				img.onerror = options.error;
			}
			img.src = url;
		}
	});

	OCA.Versions = OCA.Versions || {};

	OCA.Versions.VersionsTabView = VersionsTabView;
})();
