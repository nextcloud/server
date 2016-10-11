/*
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global Handlebars */

(function(OCA) {

	var TEMPLATE_FAVORITE_ACTION =
		'<a href="#" ' +
		'class="action action-favorite {{#isFavorite}}permanent{{/isFavorite}}">' +
		'<span class="icon {{iconClass}}" />' +
		'<span class="hidden-visually">{{altText}}</span>' +
		'</a>';

	/**
	 * Returns the icon class for the matching state
	 *
	 * @param {boolean} state true if starred, false otherwise
	 * @return {string} icon class for star image
	 */
	function getStarIconClass(state) {
		return state ? 'icon-starred' : 'icon-star';
	}

	/**
	 * Render the star icon with the given state
	 *
	 * @param {boolean} state true if starred, false otherwise
	 * @return {Object} jQuery object
	 */
	function renderStar(state) {
		if (!this._template) {
			this._template = Handlebars.compile(TEMPLATE_FAVORITE_ACTION);
		}
		return this._template({
			isFavorite: state,
			altText: state ? t('files', 'Favorited') : t('files', 'Favorite'),
			iconClass: getStarIconClass(state)
		});
	}

	/**
	 * Toggle star icon on action element
	 *
	 * @param {Object} action element
	 * @param {boolean} state true if starred, false otherwise
	 */
	function toggleStar($actionEl, state) {
		$actionEl.removeClass('icon-star icon-starred').addClass(getStarIconClass(state));
		$actionEl.toggleClass('permanent', state);
	}

	OCA.Files = OCA.Files || {};

	/**
	 * @namespace OCA.Files.TagsPlugin
	 *
	 * Extends the file actions and file list to include a favorite action icon
	 * and addition "data-tags" and "data-favorite" attributes.
	 */
	OCA.Files.TagsPlugin = {
		name: 'Tags',

		allowedLists: [
			'files',
			'favorites'
		],

		_extendFileActions: function(fileActions) {
			var self = this;
			// register "star" action
			fileActions.registerAction({
				name: 'Favorite',
				displayName: t('files', 'Favorite'),
				mime: 'all',
				permissions: OC.PERMISSION_READ,
				type: OCA.Files.FileActions.TYPE_INLINE,
				render: function(actionSpec, isDefault, context) {
					var $file = context.$file;
					var isFavorite = $file.data('favorite') === true;
					var $icon = $(renderStar(isFavorite));
					$file.find('td:first>.favorite').replaceWith($icon);
					return $icon;
				},
				actionHandler: function(fileName, context) {
					var $actionEl = context.$file.find('.action-favorite');
					var $file = context.$file;
					var fileInfo = context.fileList.files[$file.index()];
					var dir = context.dir || context.fileList.getCurrentDirectory();
					var tags = $file.attr('data-tags');
					if (_.isUndefined(tags)) {
						tags = '';
					}
					tags = tags.split('|');
					tags = _.without(tags, '');
					var isFavorite = tags.indexOf(OC.TAG_FAVORITE) >= 0;
					if (isFavorite) {
						// remove tag from list
						tags = _.without(tags, OC.TAG_FAVORITE);
					} else {
						tags.push(OC.TAG_FAVORITE);
					}

					// pre-toggle the star
					toggleStar($actionEl, !isFavorite);

					context.fileInfoModel.trigger('busy', context.fileInfoModel, true);

					self.applyFileTags(
						dir + '/' + fileName,
						tags,
						$actionEl,
						isFavorite
					).then(function(result) {
						context.fileInfoModel.trigger('busy', context.fileInfoModel, false);
						// response from server should contain updated tags
						var newTags = result.tags;
						if (_.isUndefined(newTags)) {
							newTags = tags;
						}
						context.fileInfoModel.set({
							'tags': newTags,
							'favorite': !isFavorite
						});
					});
				}
			});
		},

		_extendFileList: function(fileList) {
			// extend row prototype
			fileList.$el.addClass('has-favorites');
			var oldCreateRow = fileList._createRow;
			fileList._createRow = function(fileData) {
				var $tr = oldCreateRow.apply(this, arguments);
				if (fileData.tags) {
					$tr.attr('data-tags', fileData.tags.join('|'));
					if (fileData.tags.indexOf(OC.TAG_FAVORITE) >= 0) {
						$tr.attr('data-favorite', true);
					}
				}
				$tr.find('td:first').prepend('<div class="favorite"></div>');
				return $tr;
			};
			var oldElementToFile = fileList.elementToFile;
			fileList.elementToFile = function($el) {
				var fileInfo = oldElementToFile.apply(this, arguments);
				var tags = $el.attr('data-tags');
				if (_.isUndefined(tags)) {
					tags = '';
				}
				tags = tags.split('|');
				tags = _.without(tags, '');
				fileInfo.tags = tags;
				return fileInfo;
			};

			var NS_OC = 'http://owncloud.org/ns';

			var oldGetWebdavProperties = fileList._getWebdavProperties;
			fileList._getWebdavProperties = function() {
				var props = oldGetWebdavProperties.apply(this, arguments);
				props.push('{' + NS_OC + '}tags');
				props.push('{' + NS_OC + '}favorite');
				return props;
			};

			fileList.filesClient.addFileInfoParser(function(response) {
				var data = {};
				var props = response.propStat[0].properties;
				var tags = props['{' + NS_OC + '}tags'];
				var favorite = props['{' + NS_OC + '}favorite'];
				if (tags && tags.length) {
					tags = _.chain(tags).filter(function(xmlvalue) {
						return (xmlvalue.namespaceURI === NS_OC && xmlvalue.nodeName.split(':')[1] === 'tag');
					}).map(function(xmlvalue) {
						return xmlvalue.textContent || xmlvalue.text;
					}).value();
				}
				if (tags) {
					data.tags = tags;
				}
				if (favorite && parseInt(favorite, 10) !== 0) {
					data.tags = data.tags || [];
					data.tags.push(OC.TAG_FAVORITE);
				}
				return data;
			});
		},

		attach: function(fileList) {
			if (this.allowedLists.indexOf(fileList.id) < 0) {
				return;
			}
			this._extendFileActions(fileList.fileActions);
			this._extendFileList(fileList);
		},

		/**
		 * Replaces the given files' tags with the specified ones.
		 *
		 * @param {String} fileName path to the file or folder to tag
		 * @param {Array.<String>} tagNames array of tag names
		 * @param {Object} $actionEl element
		 * @param {boolean} isFavorite Was the item favorited before
		 */
		applyFileTags: function(fileName, tagNames, $actionEl, isFavorite) {
			var encodedPath = OC.encodePath(fileName);
			while (encodedPath[0] === '/') {
				encodedPath = encodedPath.substr(1);
			}
			return $.ajax({
				url: OC.generateUrl('/apps/files/api/v1/files/') + encodedPath,
				contentType: 'application/json',
				data: JSON.stringify({
					tags: tagNames || []
				}),
				dataType: 'json',
				type: 'POST'
			}).fail(function(response) {
				var message = '';
				// show message if it is available
				if(response.responseJSON && response.responseJSON.message) {
					message = ': ' + response.responseJSON.message;
				}
				OC.Notification.showTemporary(t('files', 'An error occurred while trying to update the tags') + message);
				toggleStar($actionEl, isFavorite);
			});
		}
	};
})(OCA);

OC.Plugins.register('OCA.Files.FileList', OCA.Files.TagsPlugin);

