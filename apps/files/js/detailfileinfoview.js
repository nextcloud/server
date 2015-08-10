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
	var DetailFileInfoView = function() {
		this.initialize();
	};
	/**
	 * @memberof OCA.Files
	 */
	DetailFileInfoView.prototype = {
		/**
		 * jQuery element
		 */
		$el: null,

		_template: null,

		/**
		 * Currently displayed file info
		 *
		 * @type OCA.Files.FileInfo
		 */
		_fileInfo: null,

		/**
		 * Initialize the details view
		 */
		initialize: function() {
			this.$el = $('<div class="detailFileInfoView"></div>');
		},

		/**
		 * returns the jQuery object for HTML output
		 *
		 * @returns {jQuery}
		 */
		get$: function() {
			return this.$el;
		},

		/**
		 * Destroy / uninitialize this instance.
		 */
		destroy: function() {
			if (this.$el) {
				this.$el.remove();
			}
		},

		/**
		 * Renders this details view
		 *
		 * @abstract
		 */
		render: function() {
			// to be implemented in subclass
		},

		/**
		 * Sets the file info to be displayed in the view
		 *
		 * @param {OCA.Files.FileInfo} fileInfo file info to set
		 */
		setFileInfo: function(fileInfo) {
			this._fileInfo = fileInfo;
			this.render();
		},

		/**
		 * Returns the file info.
		 *
		 * @return {OCA.Files.FileInfo} file info
		 */
		getFileInfo: function() {
			return this._fileInfo;
		}
	};

	OCA.Files.DetailFileInfoView = DetailFileInfoView;
})();

