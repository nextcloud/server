/*
 * @Copyright 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * @author Vincent Petry
 * @author Felix Nüsse <felix.nuesse@t-online.de>
 *
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function () {

	/**
	 * @class OCA.Files.Navigation
	 * @classdesc Navigation control for the files app sidebar.
	 *
	 * @param $el element containing the navigation
	 */
	var Navigation = function ($el) {
		this.initialize($el);
	};

	/**
	 * @memberof OCA.Files
	 */
	Navigation.prototype = {

		/**
		 * Currently selected item in the list
		 */
		_activeItem: null,

		/**
		 * Currently selected container
		 */
		$currentContent: null,

		/**
		 * Strategy by which the quickaccesslist is sorted
		 *
		 * Possible Strategies:
		 * customorder
		 * datemodified
		 * date
		 * alphabet
		 *
		 */
		$sortingStrategy: 'customorder',

		/**
		 * Key for the quick-acces-list
		 */
		$quickAccessListKey: 'sublist-favorites',
		/**
		 * Initializes the navigation from the given container
		 *
		 * @private
		 * @param $el element containing the navigation
		 */
		initialize: function ($el) {
			this.$el = $el;
			this._activeItem = null;
			this.$currentContent = null;
			this._setupEvents();
			this.setInitialQuickaccessSettings();


		},

		/**
		 * Setup UI events
		 */
		_setupEvents: function () {
			this.$el.on('click', 'li a', _.bind(this._onClickItem, this))
			this.$el.on('click', 'li button', _.bind(this._onClickMenuButton, this));
			this._setOnDrag();

		},

		/**
		 * Returns the container of the currently active app.
		 *
		 * @return app container
		 */
		getActiveContainer: function () {
			return this.$currentContent;
		},

		/**
		 * Returns the currently active item
		 *
		 * @return item ID
		 */
		getActiveItem: function () {
			return this._activeItem;
		},

		/**
		 * Switch the currently selected item, mark it as selected and
		 * make the content container visible, if any.
		 *
		 * @param string itemId id of the navigation item to select
		 * @param array options "silent" to not trigger event
		 */
		setActiveItem: function (itemId, options) {
			var oldItemId = this._activeItem;
			if (itemId === this._activeItem) {
				if (!options || !options.silent) {
					this.$el.trigger(
						new $.Event('itemChanged', {
							itemId: itemId,
							previousItemId: oldItemId
						})
					);
				}
				return;
			}
			this.$el.find('li').removeClass('active');
			if (this.$currentContent) {
				this.$currentContent.addClass('hidden');
				this.$currentContent.trigger(jQuery.Event('hide'));
			}
			this._activeItem = itemId;
			this.$el.find('li[data-id=' + itemId + ']').addClass('active');
			this.$currentContent = $('#app-content-' + itemId);
			this.$currentContent.removeClass('hidden');
			if (!options || !options.silent) {
				this.$currentContent.trigger(jQuery.Event('show'));
				this.$el.trigger(
					new $.Event('itemChanged', {
						itemId: itemId,
						previousItemId: oldItemId
					})
				);
			}
		},

		/**
		 * Returns whether a given item exists
		 */
		itemExists: function (itemId) {
			return this.$el.find('li[data-id=' + itemId + ']').length;
		},

		/**
		 * Event handler for when clicking on an item.
		 */
		_onClickItem: function (ev) {
			var $target = $(ev.target);
			var itemId = $target.closest('li').attr('data-id');
			if (!_.isUndefined(itemId)) {
				this.setActiveItem(itemId);
			}
			ev.preventDefault();
		},

		/**
		 * Event handler for when dragging an item
		 */
		_setOnDrag: function () {
			var scope=this;
			$(function () {
				if (document.getElementById(scope.$quickAccessListKey.toString()).hasAttribute("draggable")) {
					$("#sublist-favorites").sortable({
						update: function (event, ui) {
							var list = document.getElementById(scope.$quickAccessListKey.toString()).getElementsByTagName('li');
							var string=[];
							for (var j = 0; j < list.length; j++) {
								var Object = {id:j, name:scope.getCompareValue(list,j,'alphabet') };
								string.push(Object);
							}
							var resultorder=JSON.stringify(string);
							console.log(resultorder);
							$.get(OC.generateUrl("/apps/files/api/v1/quickaccess/set/CustomSortingOrder"),{
							order: resultorder}, function (data, status) {});
						}
					});
				}else{
					if(scope.$sortingStrategy === 'customorder'){
						scope.$sortingStrategy = 'datemodified';
					}
				}
			});
		},

		/**
		 * Event handler for clicking a button
		 */
		_onClickMenuButton: function (ev) {
			var $target = $(ev.target);
			var itemId = $target.closest('button').attr('id');

			var collapsibleToggles = [];
			var dotmenuToggles = [];

			// The collapsibleToggles-Array consists of a list of Arrays. Every subarray must contain the Button to listen to at the 0th index,
			// and the parent, which should be toggled at the first arrayindex.
			collapsibleToggles.push(["#button-collapse-favorites", "#button-collapse-parent-favorites"]);

			// The dotmenuToggles-Array consists of a list of Arrays. Every subarray must contain the Button to listen to at the 0th index,
			// and the parent, which should be toggled at the first arrayindex.
			dotmenuToggles.push(["#dotmenu-button-favorites", "dotmenu-content-favorites"]);


			collapsibleToggles.forEach(function foundToggle (item) {
				if (item[0] === ("#" + itemId)) {
					$(item[1]).toggleClass('open');
					var show=1;
					if(!$(item[1]).hasClass('open')){
						show=0;
					}
					$.get(OC.generateUrl("/apps/files/api/v1/quickaccess/set/showList"), {show: show}, function (data, status) {
					});
				}
			});

			dotmenuToggles.forEach(function foundToggle (item) {
				if (item[0] === ("#" + itemId)) {
					document.getElementById(item[1]).classList.toggle('open');
				}
			});

			ev.preventDefault();
		},

		/**
		 * Sort initially as setup of sidebar for QuickAccess
		 */
		setInitialQuickaccessSettings: function () {

			var quickAccesKey = this.$quickAccessListKey;
			var list = document.getElementById(quickAccesKey).getElementsByTagName('li');

			var sort = true;
			var reverse = false;
			if (this.$sortingStrategy === 'datemodified') {
				sort = false;
				reverse = false;

				var scope = this;
				$.get(OC.generateUrl("/apps/files/api/v1/quickaccess/get/FavoriteFolders/"), function (data, status) {
					for (var i = 0; i < data.favoriteFolders.length; i++) {
						for (var j = 0; j < list.length; j++) {
							if (scope.getCompareValue(list, j, 'alphabet').toLowerCase() === data.favoriteFolders[i].name.toLowerCase()) {
								list[j].setAttribute("mtime", data.favoriteFolders[i].mtime);
							}
						}
					}
					scope.QuickSort(list, 0, list.length - 1);
					scope.reverse(list);
				});


			} else if (this.$sortingStrategy === 'alphabet') {
				sort = true;
			} else if (this.$sortingStrategy === 'date') {
				sort = true;
			} else if (this.$sortingStrategy === 'customorder') {
				var scope = this;
				$.get(OC.generateUrl("/apps/files/api/v1/quickaccess/get/CustomSortingOrder"), function (data, status) {
					console.log("load order:");
					var ordering=JSON.parse(data)
					console.log(ordering);
					for (var i = 0; i < ordering.length; i++) {
						for (var j = 0; j < list.length; j++) {
							if (scope.getCompareValue(list, j, 'alphabet').toLowerCase() === ordering[i].name.toLowerCase()) {
								list[j].setAttribute("folderPosition", ordering[i].id);
							}
						}
					}
					scope.QuickSort(list, 0, list.length - 1);
				});

				sort = false;
			}

			if (sort) {
				this.QuickSort(list, 0, list.length - 1);
			}
			if (reverse) {
				this.reverse(list);
			}

		},

		/**
		 * Sorting-Algorithm for QuickAccess
		 */
		QuickSort: function (list, start, end) {
			var lastMatch;
			if (list.length > 1) {
				lastMatch = this.quicksort_helper(list, start, end);
				if (start < lastMatch - 1) {
					this.QuickSort(list, start, lastMatch - 1);
				}
				if (lastMatch < end) {
					this.QuickSort(list, lastMatch, end);
				}
			}
		},

		/**
		 * Sorting-Algorithm-Helper for QuickAccess
		 */
		quicksort_helper: function (list, start, end) {
			var pivot = Math.floor((end + start) / 2);
			var pivotElement = this.getCompareValue(list, pivot);
			var i = start;
			var j = end;

			while (i <= j) {
				while (this.getCompareValue(list, i) < pivotElement) {
					i++;
				}
				while (this.getCompareValue(list, j) > pivotElement) {
					j--;
				}
				if (i <= j) {
					this.swap(list, i, j);
					i++;
					j--;
				}
			}
			return i;
		},

		/**
		 * Sorting-Algorithm-Helper for QuickAccess
		 * This method allows easy access to the element which is sorted by.
		 */
		getCompareValue: function (nodes, int, strategy) {

			if ((typeof strategy === 'undefined')) {
				strategy = this.$sortingStrategy;
			}

			if (strategy === 'alphabet') {
				return nodes[int].getElementsByTagName('a')[0].innerHTML.toLowerCase();
			} else if (strategy === 'date') {
				return nodes[int].getAttribute('folderPos').toLowerCase();
			} else if (strategy === 'datemodified') {
				return nodes[int].getAttribute('mtime');
			}else if (strategy === 'customorder') {
				return nodes[int].getAttribute('folderPosition');
			}
			return nodes[int].getElementsByTagName('a')[0].innerHTML.toLowerCase();
		},

		/**
		 * Sorting-Algorithm-Helper for QuickAccess
		 * This method allows easy swapping of elements.
		 */
		swap: function (list, j, i) {
			list[i].before(list[j]);
			list[j].before(list[i]);
		},

		/**
		 * Reverse QuickAccess-List
		 */
		reverse: function (list) {
			var len = list.length - 1;
			for (var i = 0; i < len / 2; i++) {
				this.swap(list, i, len - i);
			}
		}

	};

	OCA.Files.Navigation = Navigation;

})();





