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
	/**
	 * @class OCA.Files.DetailFileInfoView
	 * @classdesc
	 *
	 * Displays a block of details about the file info.
	 *
	 */
	var DetailFileInfoView = OC.Backbone.View.extend({
		tagName: 'div',
		className: 'detailFileInfoView',

		_template: null,

		/**
		 * returns the jQuery object for HTML output
		 *
		 * @returns {jQuery}
		 */
		get$: function() {
			return this.$el;
		},

		/**
		 * Sets the file info to be displayed in the view
		 *
		 * @param {OCA.Files.FileInfo} fileInfo file info to set
		 */
		setFileInfo: function(fileInfo) {
			this.model = fileInfo;
			this.render();
		},

		/**
		 * Returns the file info.
		 *
		 * @return {OCA.Files.FileInfo} file info
		 */
		getFileInfo: function() {
			return this.model;
		}
	});

	OCA.Files.DetailFileInfoView = DetailFileInfoView;
})();

