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
		allowedLists: [
			'files',
			'favorites'
		],

		attach: function(fileList) {
			if (this.allowedLists.indexOf(fileList.id) < 0) {
				return;
			}

			fileList.registerTabView(new OCA.Comments.CommentsTabView('commentsTabView'));
		}
	};

})();

OC.Plugins.register('OCA.Files.FileList', OCA.Comments.FilesPlugin);

