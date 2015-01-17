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
		'<img class="svg" alt="{{altText}}" src="{{imgFile}}" />' +
		'</a>';

	/**
	 * Returns the path to the star image
	 *
	 * @param {boolean} state true if starred, false otherwise
	 * @return {string} path to star image
	 */
	function getStarImage(state) {
		return OC.imagePath('core', state ? 'actions/starred' : 'actions/star');
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
			imgFile: getStarImage(state)
		});
	}

	/**
	 * Toggle star icon on action element
	 *
	 * @param {Object} action element
	 * @param {boolean} state true if starred, false otherwise
	 */
	function toggleStar($actionEl, state) {
		$actionEl.find('img').attr('src', getStarImage(state));
		$actionEl.hide().show(0); //force Safari to redraw element on src change
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
				name: 'favorite',
				displayName: 'Favorite',
				mime: 'all',
				permissions: OC.PERMISSION_READ,
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
					toggleStar($actionEl, !isFavorite);

					self.applyFileTags(
						dir + '/' + fileName,
						tags
					).then(function(result) {
						// response from server should contain updated tags
						var newTags = result.tags;
						if (_.isUndefined(newTags)) {
							newTags = tags;
						}
						var fileInfo = context.fileList.files[$file.index()];
						// read latest state from result
						toggleStar($actionEl, (newTags.indexOf(OC.TAG_FAVORITE) >= 0));
						$file.attr('data-tags', newTags.join('|'));
						$file.attr('data-favorite', !isFavorite);
						fileInfo.tags = newTags;
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
		 */
		applyFileTags: function(fileName, tagNames) {
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
			});
		}
	};
})(OCA);

OC.Plugins.register('OCA.Files.FileList', OCA.Files.TagsPlugin);

