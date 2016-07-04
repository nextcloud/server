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
		'<div class="thumbnailContainer"><a href="#" class="thumbnail action-default"><div class="stretcher"/></a></div>' +
		'<div class="file-details-container">' +
		'<div class="fileName">' +
			'<h3 title="{{name}}" class="ellipsis">{{name}}</h3>' +
			'<a class="permalink" href="{{permalink}}" title="{{permalinkTitle}}">' +
				'<span class="icon icon-public"></span>' +
				'<span class="hidden-visually">{{permalinkTitle}}</span>' +
			'</a>' +
		'</div>' +
		'	<div class="file-details ellipsis">' +
		'		<a href="#" ' +
		'		class="action action-favorite favorite">' +
		'			<img class="svg" alt="{{starAltText}}" src="{{starIcon}}" />' +
		'		</a>' +
		'		{{#if hasSize}}<span class="size" title="{{altSize}}">{{size}}</span>, {{/if}}<span class="date" title="{{altDate}}">{{date}}</span>' +
		'	</div>' +
		'</div>' +
		'<div class="hidden permalink-field">' +
			'<input type="text" value="{{permalink}}" placeholder="{{permalinkTitle}}" readonly="readonly"/>' +
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

		/**
		 * @type {OCA.Files.SidebarPreviewManager}
		 */
		_previewManager: null,

		events: {
			'click a.action-favorite': '_onClickFavorite',
			'click a.action-default': '_onClickDefaultAction',
			'click a.permalink': '_onClickPermalink',
			'focus .permalink-field>input': '_onFocusPermalink'
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
				throw 'Missing required parameter "fileList"';
			}
			if (!this._fileActions) {
				throw 'Missing required parameter "fileActions"';
			}
			this._previewManager = new OCA.Files.SidebarPreviewManager(this._fileList);
		},

		_onClickPermalink: function() {
			var $row = this.$('.permalink-field');
			$row.toggleClass('hidden');
			if (!$row.hasClass('hidden')) {
				$row.find('>input').focus();
			}
			// cancel click, user must right-click + copy or middle click
			return false;
		},

		_onFocusPermalink: function() {
			this.$('.permalink-field>input').select();
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

		_makePermalink: function(fileId) {
			var baseUrl = OC.getProtocol() + '://' + OC.getHost();
			return baseUrl + OC.generateUrl('/f/{fileId}', {fileId: fileId});
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
					type: this.model.isImage()? 'image': '',
					nameLabel: t('files', 'Name'),
					name: this.model.get('displayName') || this.model.get('name'),
					pathLabel: t('files', 'Path'),
					path: this.model.get('path'),
					hasSize: this.model.has('size'),
					sizeLabel: t('files', 'Size'),
					size: OC.Util.humanFileSize(this.model.get('size'), true),
					altSize: n('files', '%n byte', '%n bytes', this.model.get('size')),
					dateLabel: t('files', 'Modified'),
					altDate: OC.Util.formatDate(this.model.get('mtime')),
					date: OC.Util.relativeModifiedDate(this.model.get('mtime')),
					starAltText: isFavorite ? t('files', 'Favorited') : t('files', 'Favorite'),
					starIcon: OC.imagePath('core', isFavorite ? 'actions/starred' : 'actions/star'),
					permalink: this._makePermalink(this.model.get('id')),
					permalinkTitle: t('files', 'Local link')
				}));

				// TODO: we really need OC.Previews
				var $iconDiv = this.$el.find('.thumbnail');
				var $container = this.$el.find('.thumbnailContainer');
				if (!this.model.isDirectory()) {
					$iconDiv.addClass('icon-loading icon-32');
					this._previewManager.loadPreview(this.model, $iconDiv, $container);
				} else {
					var iconUrl = this.model.get('icon') || OC.MimeType.getIconUrl('dir');
					$iconDiv.css('background-image', 'url("' + iconUrl + '")');
					OC.Util.scaleFixForIE8($iconDiv);
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
