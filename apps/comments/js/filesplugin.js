/*
 * Copyright (c) 2016 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global Handlebars */

(function() {
	var TEMPLATE_COMMENTS_UNREAD =
		'<a class="action action-comment permanent" title="{{countMessage}}" href="#">' +
		'<img class="svg" src="{{iconUrl}}"/>' +
		'</a>';

	OCA.Comments = _.extend({}, OCA.Comments);
	if (!OCA.Comments) {
		/**
		 * @namespace
		 */
		OCA.Comments = {};
	}

	/**
	 * @namespace
	 */
	OCA.Comments.FilesPlugin = {
		ignoreLists: [
			'files_trashbin',
			'files.public'
		],

		_formatCommentCount: function(count) {
			if (!this._commentsUnreadTemplate) {
				this._commentsUnreadTemplate = Handlebars.compile(TEMPLATE_COMMENTS_UNREAD);
			}
			return this._commentsUnreadTemplate({
				count: count,
				countMessage: t('comments', '{count} unread comments', {count: count}),
				iconUrl: OC.imagePath('core', 'actions/comment')
			});
		},

		attach: function(fileList) {
			var self = this;
			if (this.ignoreLists.indexOf(fileList.id) >= 0) {
				return;
			}

			fileList.registerTabView(new OCA.Comments.CommentsTabView('commentsTabView'));

			var NS_OC = 'http://owncloud.org/ns';

			var oldGetWebdavProperties = fileList._getWebdavProperties;
			fileList._getWebdavProperties = function() {
				var props = oldGetWebdavProperties.apply(this, arguments);
				props.push('{' + NS_OC + '}comments-unread');
				return props;
			};

			fileList.filesClient.addFileInfoParser(function(response) {
				var data = {};
				var props = response.propStat[0].properties;
				var commentsUnread = props['{' + NS_OC + '}comments-unread'];
				if (!_.isUndefined(commentsUnread) && commentsUnread !== '') {
					data.commentsUnread = parseInt(commentsUnread, 10);
				}
				return data;
			});

			fileList.$el.addClass('has-comments');
			var oldCreateRow = fileList._createRow;
			fileList._createRow = function(fileData) {
				var $tr = oldCreateRow.apply(this, arguments);
				if (fileData.commentsUnread) {
					$tr.attr('data-comments-unread', fileData.commentsUnread);
				}
				return $tr;
			};

			// register "comment" action for reading comments
			fileList.fileActions.registerAction({
				name: 'Comment',
				displayName: t('comments', 'Comment'),
				mime: 'all',
				permissions: OC.PERMISSION_READ,
				type: OCA.Files.FileActions.TYPE_INLINE,
				render: function(actionSpec, isDefault, context) {
					var $file = context.$file;
					var unreadComments = $file.data('comments-unread');
					if (unreadComments) {
						var $actionLink = $(self._formatCommentCount(unreadComments));
						context.$file.find('a.name>span.fileactions').append($actionLink);
						return $actionLink;
					}
					return '';
				},
				actionHandler: function(fileName, context) {
					context.$file.find('.action-comment').tooltip('hide');
					// open sidebar in comments section
					context.fileList.showDetailsView(fileName, 'commentsTabView');
				}
			});

			// add attribute to "elementToFile"
			var oldElementToFile = fileList.elementToFile;
			fileList.elementToFile = function($el) {
				var fileInfo = oldElementToFile.apply(this, arguments);
				var commentsUnread = $el.data('comments-unread');
				if (commentsUnread) {
					fileInfo.commentsUnread = commentsUnread;
				}
				return fileInfo;
			};
		}
	};

})();

OC.Plugins.register('OCA.Files.FileList', OCA.Comments.FilesPlugin);
