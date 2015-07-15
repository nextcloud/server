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
		'<div>' +
		'    <div class="detailFileInfoContainer">' +
		'    </div>' +
		'    <div class="tabHeadsContainer">' +
		'    </div>' +
		'    <div class="tabContentsContainer">' +
		'    </div>' +
		'</div>';

	var TEMPLATE_TAB_HEADER =
		'<div class="tabHeaders">{{label}}</div>';

	/**
	 * @class OCA.Files.DetailsView
	 * @classdesc
	 *
	 * The details view show details about a selected file.
	 *
	 */
	var DetailsView = function() {
		this.initialize();
	};
	/**
	 * @memberof OCA.Files
	 */
	DetailsView.prototype = {

		/**
		 * jQuery element
		 */
		$el: null,

		_template: null,
		_templateTabHeader: null,

		/**
		 * Currently displayed file info
		 *
		 * @type OCA.Files.FileInfo
		 */
		_fileInfo: null,

		/**
		 * List of detail tab views
		 *
		 * @type Array<OCA.Files.DetailTabView>
		 */
		_tabViews: [],

		/**
		 * List of detail file info views
		 *
		 * @type Array<OCA.Files.DetailFileInfoView>
		 */
		_detailFileInfoViews: [],

		/**
		 * Initialize the details view
		 */
		initialize: function() {
			this.$el = $('<div class="detailsView"></div>');
			this.fileInfo = null;
			this._tabViews = [];
			this._detailFileInfoViews = [];
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
		 */
		render: function() {
			this.$el.empty();

			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}

			if (!this._templateTabHeader) {
				this._templateTabHeader = Handlebars.compile(TEMPLATE_TAB_HEADER);
			}

			var $el = $(this._template());
			var $tabHeadsContainer = $el.find('.tabHeadsContainer');
			var $tabsContainer = $el.find('.tabContentsContainer');
			var $detailsContainer = $el.find('.detailFileInfoContainer');

			// render tabs
			_.each(this._tabs, function(tabView) {
				tabView.render();
				// hidden by default
				tabView.$el.addClass('hidden');
				$tabsContainer.append(tabView.$el);

				$tabHeadsContainer.append(this._templateTabHeader({label: tabView.getLabel()}));
			});

			// render details
			_.each(this._detailFileInfoViews, function(detailView) {
				detailView.render();
				$detailsContainer.append(detailView.$el);
			});

			// select first tab
			$el.find('.tabContentsContainer:first').removeClass('hidden');

			this.$el.append($el);
		},

		/**
		 * Sets the file info to be displayed in the view
		 *
		 * @param {OCA.Files.FileInfo} fileInfo file info to set
		 */
		setFileInfo: function(fileInfo) {
			this._fileInfo = fileInfo;

			// notify all panels
			_.each(this._tabs, function(tabView) {
				tabView.setFileInfo(fileInfo);
			});
			_.each(this._detailFileInfoViews, function(detailView) {
				detailView.setFileInfo(fileInfo);
			});
		},

		/**
		 * Returns the file info.
		 *
		 * @return {OCA.Files.FileInfo} file info
		 */
		getFileInfo: function() {
			return this._fileInfo;
		},

		/**
		 * Adds a tab in the tab view
		 *
		 * @param {OCA.Files.DetailTabView} tab view
		 */
		addTabView: function(tabView) {
			this._tabViews.push(tabView);
		},

		/**
		 * Adds a detail view for file info.
		 *
		 * @param {OCA.Files.DetailFileInfoView} detail view
		 */
		addDetailView: function(detailView) {
			this._detailFileInfoViews.push(detailView);
		}
	};

	OCA.Files.DetailsView = DetailsView;
})();

