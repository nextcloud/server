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
	 * @class OCA.Files.DetailTabView
	 * @classdesc
	 *
	 * Base class for tab views to display file information.
	 *
	 */
	var DetailTabView = function() {
		this.initialize();
	};

	/**
	 * @memberof OCA.Files
	 */
	DetailTabView.prototype = {
		/**
		 * jQuery element
		 */
		$el: null,

		/**
		 * Tab id
		 */
		_id: null,

		/**
		 * Tab label
		 */
		_label: null,

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
			this.$el = $('<div class="detailTabView"></div>');
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
		 * Returns the tab label
		 *
		 * @return {String} label
		 */
		getLabel: function() {
			return 'Tab ' + this._id;
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

	OCA.Files.DetailTabView = DetailTabView;
})();

