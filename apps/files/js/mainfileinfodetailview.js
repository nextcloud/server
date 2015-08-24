/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	var TEMPLATE =
		'<a href="#" class="thumbnail action-default"></a><div title="{{name}}" class="fileName ellipsis">{{name}}</div>' +
		'<div class="file-details ellipsis">' +
		'    <a href="#" ' +
		'    alt="{{starAltText}}"' +
		'    class="action action-favorite favorite">' +
		'    <img class="svg" src="{{starIcon}}" />' +
		'    </a>' +
		'    <span class="size" title="{{altSize}}">{{size}}</span>, <span class="date" title="{{altDate}}">{{date}}</span>' +
		'</div>';

	/**
	 * @class OCA.Files.MainFileInfoDetailView
	 * @classdesc
	 *
	 * Displays main details about a file
	 *
	 */
	var MainFileInfoDetailView = OCA.Files.DetailFileInfoView.extend(
		/** @lends OCA.Files.MainFileInfoDetailView.prototype */ {

		className: 'mainFileInfoView',

		/**
		 * Associated file list instance, for file actions
		 *
		 * @type {OCA.Files.FileList}
		 */
		_fileList: null,

		/**
		 * File actions
		 *
		 * @type {OCA.Files.FileActions}
		 */
		_fileActions: null,

		events: {
			'click a.action-favorite': '_onClickFavorite',
			'click a.action-default': '_onClickDefaultAction'
		},

		template: function(data) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			return this._template(data);
		},

		initialize: function(options) {
			options = options || {};
			this._fileList = options.fileList;
			this._fileActions = options.fileActions;
			if (!this._fileList) {
				throw 'Missing requird parameter "fileList"';
			}
			if (!this._fileActions) {
				throw 'Missing requird parameter "fileActions"';
			}
		},

		_onClickFavorite: function(event) {
			event.preventDefault();
			this._fileActions.triggerAction('Favorite', this.model, this._fileList);
		},

		_onClickDefaultAction: function(event) {
			event.preventDefault();
			this._fileActions.triggerAction(null, this.model, this._fileList);
		},

		_onModelChanged: function() {
			// simply re-render
			this.render();
		},

		setFileInfo: function(fileInfo) {
			if (this.model) {
				this.model.off('change', this._onModelChanged, this);
			}
			this.model = fileInfo;
			if (this.model) {
				this.model.on('change', this._onModelChanged, this);
			}
			this.render();
		},

		/**
		 * Renders this details view
		 */
		render: function() {
			if (this.model) {
				var isFavorite = (this.model.get('tags') || []).indexOf(OC.TAG_FAVORITE) >= 0;
				this.$el.html(this.template({
					nameLabel: t('files', 'Name'),
					name: this.model.get('name'),
					pathLabel: t('files', 'Path'),
					path: this.model.get('path'),
					sizeLabel: t('files', 'Size'),
					size: OC.Util.humanFileSize(this.model.get('size'), true),
					altSize: n('files', '%n byte', '%n bytes', this.model.get('size')),
					dateLabel: t('files', 'Modified'),
					altDate: OC.Util.formatDate(this.model.get('mtime')),
					date: OC.Util.relativeModifiedDate(this.model.get('mtime')),
					starAltText: isFavorite ? t('files', 'Favorited') : t('files', 'Favorite'),
					starIcon: OC.imagePath('core', isFavorite ? 'actions/starred' : 'actions/star')
				}));

				// TODO: we really need OC.Previews
				var $iconDiv = this.$el.find('.thumbnail');
				if (!this.model.isDirectory()) {
					// TODO: inject utility class?
					FileList.lazyLoadPreview({
						path: this.model.getFullPath(),
						mime: this.model.get('mimetype'),
						etag: this.model.get('etag'),
						x: 75,
						y: 75,
						callback: function(previewUrl) {
							$iconDiv.css('background-image', 'url("' + previewUrl + '")');
						}
					});
				} else {
					// TODO: special icons / shared / external
					$iconDiv.css('background-image', 'url("' + OC.MimeType.getIconUrl('dir') + '")');
				}
				this.$el.find('[title]').tooltip({placement: 'bottom'});
			} else {
				this.$el.empty();
			}
			this.delegateEvents();
		}
	});

	OCA.Files.MainFileInfoDetailView = MainFileInfoDetailView;
})();
