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
	var DetailTabView = OC.Backbone.View.extend({
		tag: 'div',

		className: 'tab',

		/**
		 * Tab label
		 */
		_label: null,

		_template: null,

		initialize: function(options) {
			options = options || {};
			if (!this.id) {
				this.id = 'detailTabView' + DetailTabView._TAB_COUNT;
				DetailTabView._TAB_COUNT++;
			}
			if (options.order) {
				this.order = options.order || 0;
			}
		},

		/**
		 * Returns the extra CSS classes used by the tabs container when this
		 * tab is the selected one.
		 *
		 * In general you should not extend this method, as tabs should not
		 * modify the classes of its container; this is reserved as a last
		 * resort for very specific cases in which there is no other way to get
		 * the proper style or behaviour.
		 *
		 * @return {String} space-separated CSS classes
		 */
		getTabsContainerExtraClasses: function() {
			return '';
		},

		/**
		 * Returns the tab label
		 *
		 * @return {String} label
		 */
		getLabel: function() {
			return 'Tab ' + this.id;
		},

		/**
		 * Returns the tab label
		 *
		 * @return {String}|{null} icon class
		 */
		getIcon: function() {
			return null
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
		 * Renders this details view
		 *
		 * @abstract
		 */
		render: function() {
			// to be implemented in subclass
			// FIXME: code is only for testing
			this.$el.html('<div>Hello ' + this.id + '</div>');
		},

		/**
		 * Sets the file info to be displayed in the view
		 *
		 * @param {OCA.Files.FileInfoModel} fileInfo file info to set
		 */
		setFileInfo: function(fileInfo) {
			if (this.model !== fileInfo) {
				this.model = fileInfo;
				this.render();
			}
		},

		/**
		 * Returns the file info.
		 *
		 * @return {OCA.Files.FileInfoModel} file info
		 */
		getFileInfo: function() {
			return this.model;
		},

		/**
		 * Load the next page of results
		 */
		nextPage: function() {
			// load the next page, if applicable
		},

		/**
		 * Returns whether the current tab is able to display
		 * the given file info, for example based on mime type.
		 *
		 * @param {OCA.Files.FileInfoModel} fileInfo file info model
		 * @return {bool} whether to display this tab
		 */
		canDisplay: function(fileInfo) {
			return true;
		}
	});
	DetailTabView._TAB_COUNT = 0;

	OCA.Files = OCA.Files || {};

	OCA.Files.DetailTabView = DetailTabView;
})();

