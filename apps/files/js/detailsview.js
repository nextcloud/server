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
	 * @class OCA.Files.DetailsView
	 * @classdesc
	 *
	 * The details view show details about a selected file.
	 *
	 */
	var DetailsView = OC.Backbone.View.extend({
		id: 'app-sidebar',
		tabName: 'div',
		className: 'detailsView scroll-container',

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
		 * Dirty flag, whether the view needs to be rerendered
		 */
		_dirty: false,

		events: {
			'click a.close': '_onClose',
			'click .tabHeaders .tabHeader': '_onClickTab',
			'keyup .tabHeaders .tabHeader': '_onKeyboardActivateTab'
		},

		/**
		 * Initialize the details view
		 */
		initialize: function() {
			this._tabViews = [];
			this._detailFileInfoViews = [];

			this._dirty = true;
		},

		_onClose: function(event) {
			OC.Apps.hideAppSidebar(this.$el);
			event.preventDefault();
		},

		_onClickTab: function(e) {
			var $target = $(e.target);
			e.preventDefault();
			if (!$target.hasClass('tabHeader')) {
				$target = $target.closest('.tabHeader');
			}
			var tabId = $target.attr('data-tabid');
			if (_.isUndefined(tabId)) {
				return;
			}

			this.selectTab(tabId);
		},

		_onKeyboardActivateTab: function (event) {
			if (event.key === " " || event.key === "Enter") {
				this._onClickTab(event);
			}
		},

		template: function(vars) {
			return OCA.Files.Templates['detailsview'](vars);
		},

		/**
		 * Renders this details view
		 */
		render: function() {
			var templateVars = {
				closeLabel: t('files', 'Close')
			};

			this._tabViews = this._tabViews.sort(function(tabA, tabB) {
				var orderA = tabA.order || 0;
				var orderB = tabB.order || 0;
				if (orderA === orderB) {
					return OC.Util.naturalSortCompare(tabA.getLabel(), tabB.getLabel());
				}
				return orderA - orderB;
			});

			templateVars.tabHeaders = _.map(this._tabViews, function(tabView, i) {
				return {
					tabId: tabView.id,
					label: tabView.getLabel(),
					tabIcon: tabView.getIcon()
				};
			});

			this.$el.html(this.template(templateVars));

			var $detailsContainer = this.$el.find('.detailFileInfoContainer');

			// render details
			_.each(this._detailFileInfoViews, function(detailView) {
				$detailsContainer.append(detailView.get$());
			});

			if (!this._currentTabId && this._tabViews.length > 0) {
				this._currentTabId = this._tabViews[0].id;
			}

			this.selectTab(this._currentTabId);

			this._updateTabVisibilities();

			this._dirty = false;
		},

		/**
		 * Selects the given tab by id
		 *
		 * @param {string} tabId tab id
		 */
		selectTab: function(tabId) {
			if (!tabId) {
				return;
			}

			var tabView = _.find(this._tabViews, function(tab) {
				return tab.id === tabId;
			});

			if (!tabView) {
				console.warn('Details view tab with id "' + tabId + '" not found');
				return;
			}

			this._currentTabId = tabId;

			var $tabsContainer = this.$el.find('.tabsContainer');
			var $tabEl = $tabsContainer.find('#' + tabId);

			// hide other tabs
			$tabsContainer.find('.tab').addClass('hidden');

			$tabsContainer.attr('class', 'tabsContainer');
			$tabsContainer.addClass(tabView.getTabsContainerExtraClasses());

			// tab already rendered ?
			if (!$tabEl.length) {
				// render tab
				$tabsContainer.append(tabView.$el);
				$tabEl = tabView.$el;
			}

			// this should trigger tab rendering
			tabView.setFileInfo(this.model);

			$tabEl.removeClass('hidden');

			// update tab headers
			var $tabHeaders = this.$el.find('.tabHeaders li');
			$tabHeaders.removeClass('selected');
			$tabHeaders.filterAttr('data-tabid', tabView.id).addClass('selected');
		},

		/**
		 * Sets the file info to be displayed in the view
		 *
		 * @param {OCA.Files.FileInfoModel} fileInfo file info to set
		 */
		setFileInfo: function(fileInfo) {
			this.model = fileInfo;

			if (this._dirty) {
				this.render();
			} else {
				this._updateTabVisibilities();
			}

			if (this._currentTabId) {
				// only update current tab, others will be updated on-demand
				var tabId = this._currentTabId;
				var tabView = _.find(this._tabViews, function(tab) {
					return tab.id === tabId;
				});
				tabView.setFileInfo(fileInfo);
			}

			_.each(this._detailFileInfoViews, function(detailView) {
				detailView.setFileInfo(fileInfo);
			});
		},

		/**
		 * Update tab headers based on the current model
		 */
		_updateTabVisibilities: function() {
			// update tab header visibilities
			var self = this;
			var deselect = false;
			var countVisible = 0;
			var $tabHeaders = this.$el.find('.tabHeaders li');
			_.each(this._tabViews, function(tabView) {
				var isVisible = tabView.canDisplay(self.model);
				if (isVisible) {
					countVisible += 1;
				}
				if (!isVisible && self._currentTabId === tabView.id) {
					deselect = true;
				}
				$tabHeaders.filterAttr('data-tabid', tabView.id).toggleClass('hidden', !isVisible);
			});

			// hide the whole container if there is only one tab
			this.$el.find('.tabHeaders').toggleClass('hidden', countVisible <= 1);

			if (deselect) {
				// select the first visible tab instead
				var visibleTabId = this.$el.find('.tabHeader:not(.hidden):first').attr('data-tabid');
				this.selectTab(visibleTabId);
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
		 * Adds a tab in the tab view
		 *
		 * @param {OCA.Files.DetailTabView} tab view
		 */
		addTabView: function(tabView) {
			this._tabViews.push(tabView);
			this._dirty = true;
		},

		/**
		 * Adds a detail view for file info.
		 *
		 * @param {OCA.Files.DetailFileInfoView} detail view
		 */
		addDetailView: function(detailView) {
			this._detailFileInfoViews.push(detailView);
			this._dirty = true;
		},

		/**
		 * Returns an array with the added DetailFileInfoViews.
		 *
		 * @return Array<OCA.Files.DetailFileInfoView> an array with the added
		 *         DetailFileInfoViews.
		 */
		getDetailViews: function() {
			return [].concat(this._detailFileInfoViews);
		}
	});

	OCA.Files.DetailsView = DetailsView;
})();
