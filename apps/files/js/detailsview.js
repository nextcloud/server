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
		'    <div>' +
		'        <ul class="tabHeaders">' +
		'        </ul>' +
		'        <div class="tabsContainer">' +
		'        </div>' +
		'    </div>' +
		'    <a class="close icon-close" href="#" alt="{{closeLabel}}"></a>' +
		'</div>';

	var TEMPLATE_TAB_HEADER =
		'<li class="tabHeader {{#if selected}}selected{{/if}}" data-tabid="{{tabId}}" data-tabindex="{{tabIndex}}"><a href="#">{{label}}</a></li>';

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
		 * Id of the currently selected tab
		 *
		 * @type string
		 */
		_currentTabId: null,

		/**
		 * Initialize the details view
		 */
		initialize: function() {
			this.$el = $('<div id="app-sidebar"></div>');
			this.fileInfo = null;
			this._tabViews = [];
			this._detailFileInfoViews = [];

			this.$el.on('click', 'a.close', function(event) {
				OC.Apps.hideAppSidebar();
				event.preventDefault();
			});

			this.$el.on('click', '.tabHeaders .tabHeader', _.bind(this._onClickTab, this));

			// uncomment to add some dummy tabs for testing
			//this._addTestTabs();
		},

		/**
		 * Destroy / uninitialize this instance.
		 */
		destroy: function() {
			if (this.$el) {
				this.$el.remove();
			}
		},

		_onClickTab: function(e) {
			var $target = $(e.target);
			if (!$target.hasClass('tabHeader')) {
				$target = $target.closest('.tabHeader');
			}
			var tabIndex = $target.attr('data-tabindex');
			var targetTab;
			if (_.isUndefined(tabIndex)) {
				return;
			}

			this.$el.find('.tabsContainer .tab').addClass('hidden');
			targetTab = this._tabViews[tabIndex];
			targetTab.$el.removeClass('hidden');

			this.$el.find('.tabHeaders li').removeClass('selected');
			$target.addClass('selected');

			e.preventDefault();
		},

		_addTestTabs: function() {
			for (var j = 0; j < 2; j++) {
				var testView = new OCA.Files.DetailTabView('testtab' + j);
				testView.index = j;
				testView.getLabel = function() { return 'Test tab ' + this.index; };
				testView.render = function() {
					this.$el.empty();
					for (var i = 0; i < 100; i++) {
						this.$el.append('<div>Test tab ' + this.index + ' row ' + i + '</div>');
					}
				};
				this._tabViews.push(testView);
			}
		},

		/**
		 * Renders this details view
		 */
		render: function() {
			var self = this;
			this.$el.empty();

			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}

			if (!this._templateTabHeader) {
				this._templateTabHeader = Handlebars.compile(TEMPLATE_TAB_HEADER);
			}

			var $el = $(this._template({
				closeLabel: t('files', 'Close')
			}));
			var $tabsContainer = $el.find('.tabsContainer');
			var $tabHeadsContainer = $el.find('.tabHeaders');
			var $detailsContainer = $el.find('.detailFileInfoContainer');

			// render details
			_.each(this._detailFileInfoViews, function(detailView) {
				$detailsContainer.append(detailView.get$());
			});

			if (this._tabViews.length > 0) {
				if (!this._currentTab) {
					this._currentTab = this._tabViews[0].getId();
				}

				// render tabs
				_.each(this._tabViews, function(tabView, i) {
					// hidden by default
					var $el = tabView.get$();
					var isCurrent = (tabView.getId() === self._currentTab);
					if (!isCurrent) {
						$el.addClass('hidden');
					}
					$tabsContainer.append($el);

					$tabHeadsContainer.append(self._templateTabHeader({
						tabId: tabView.getId(),
						tabIndex: i,
						label: tabView.getLabel(),
						selected: isCurrent
					}));
				});
			}

			// TODO: select current tab

			this.$el.append($el);
		},

		/**
		 * Sets the file info to be displayed in the view
		 *
		 * @param {OCA.Files.FileInfo} fileInfo file info to set
		 */
		setFileInfo: function(fileInfo) {
			this._fileInfo = fileInfo;

			this.render();

			// notify all panels
			_.each(this._tabViews, function(tabView) {
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

