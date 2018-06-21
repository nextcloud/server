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
		 */
		$sortingStrategy: 'alphabet',
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
			this.$el.on('click', 'li input', _.bind(this._onClickMenuItem, this));
			this.$el.on('click', 'div input', _.bind(this._onClickAppSettings, this));
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
		 * Event handler for when clicking on an three-dot-menu.
		 */
		_onClickMenuButton: function (ev) {
			var $target = $(ev.target);
			var itemId = $target.closest('button').attr('id');
			var collapsibleToggle = $("#favorites-toggle");

			if (itemId === 'button-collapseQuickAccess') {
				$.get(OC.generateUrl("/apps/files/api/v1/quickaccess/show"),
					{show: !collapsibleToggle.hasClass('open')},
					function (data, status) {
					});
				collapsibleToggle.toggleClass('open');
			}

			if (itemId === 'button-favorites') {
				document.getElementById('menu-favorites').classList.toggle('open');
			}
			ev.preventDefault();
		},

		/**
		 * Event handler for when clicking on an app setting.
		 */
		_onClickAppSettings: function (ev) {

			var itemId = $(ev.target).closest('input').attr('id');
			if (itemId === 'showQuickAccessSortingToggle') {

				var togglestate=false;
				var dotMenu = document.getElementById("quickaccessbutton");
				if (document.getElementById('showQuickAccessSortingToggle').checked) {
					if($("#favorites-toggle" ).hasClass('collapsible')){
						dotMenu.style.display='';
					}
					togglestate=true;
				} else {
					dotMenu.style.display='none';
					togglestate=false;

				}

				$.get(OC.generateUrl("/apps/files/api/v1/quickaccess/set/showsettings"),
					{show: togglestate},
					function (data, status) {
					});
				document.getElementById('showQuickAccessSortingToggle').checked=togglestate;
			}
		},

		/**
		 * Event handler for when clicking on a menuitem.
		 */
		_onClickMenuItem: function (ev) {
			var quickAccessKey = 'quickaccess-list';
			var itemId = $(ev.target).closest('input').attr('id');
			var list = document.getElementById(quickAccessKey).getElementsByTagName('li');

			if (itemId === 'enableQuickAccess') {
				$.get(OC.generateUrl("/apps/files/api/v1/quickaccess/show"),
					{show: document.getElementById('enableQuickAccess').checked},
					function (data, status) {
				});
				$("#favorites-toggle").toggleClass('open');
				document.getElementById('menu-favorites').classList.toggle('open');
			}

			if (itemId === 'sortByAlphabet') {
				//Prevents deselecting Group-Item
				if (!document.getElementById('sortByAlphabet').checked) {
					ev.preventDefault();
					return;
				}

				this.sortingStrategy = 'alphabet';
				document.getElementById('sortByDate').checked = false;
				$.get(OC.generateUrl("/apps/files/api/v1/quickaccess/set/SortingStrategy"),
					{strategy: this.sortingStrategy},
					function (data, status) {
				});

				this.QuickSort(list, 0, list.length - 1);
				if (document.getElementById('enableReverse').checked) {this.reverse(list);}
				document.getElementById('menu-favorites').classList.toggle('open');
			}

			if (itemId === 'sortByDate') {
				//Prevents deselecting Group-Item
				if (!document.getElementById('sortByDate').checked) {
					ev.preventDefault();
					return;
				}

				this.sortingStrategy = 'date';
				document.getElementById('sortByAlphabet').checked = false;
				$.get(OC.generateUrl("/apps/files/api/v1/quickaccess/set/SortingStrategy"),
					{strategy: this.sortingStrategy},
					function (data, status) {
				});

				this.QuickSort(list, 0, list.length - 1);
				if (document.getElementById('enableReverse').checked) {this.reverse(list);}
				document.getElementById('menu-favorites').classList.toggle('open');
			}

			if (itemId === 'enableReverse') {
				this.reverse(list);
				var state = document.getElementById('enableReverse').checked;
				$.get(OC.generateUrl("/apps/files/api/v1/quickaccess/set/ReverseList"),
					{reverse: state},
					function (data, status) {
				});
				document.getElementById('menu-favorites').classList.toggle('open');
			}
		},

		/**
		 * Sort initially as setup of sidebar for QuickAccess
		 */
		setInitialQuickaccessSettings: function () {
			var domRevState = document.getElementById('enableReverse').checked;
			var domSortAlphabetState = document.getElementById('sortByAlphabet').checked;
			var domSortDateState = document.getElementById('sortByDate').checked;

			var quickAccesKey = 'quickaccess-list';
			var list = document.getElementById(quickAccesKey).getElementsByTagName('li');

			if (domSortAlphabetState) {
				this.sortingStrategy = 'alphabet';
			}
			if (domSortDateState) {
				this.sortingStrategy = 'date';
			}

			this.QuickSort(list, 0, list.length - 1);

			if (domRevState) {
				this.reverse(list);
			}

			$.get(OC.generateUrl("/apps/files/api/v1/quickaccess/showsettings"),
				function (data, status) {
					document.getElementById('showQuickAccessSortingToggle').checked=data;
					if (data && $("#favorites-toggle" ).hasClass('collapsible')) {
						document.getElementById("quickaccessbutton").style.display='';
					} else {
						document.getElementById("quickaccessbutton").style.display='none';
					}
				});

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
		getCompareValue: function (nodes, int) {
			if (this.sortingStrategy === 'alphabet') {
				return nodes[int].getElementsByTagName('a')[0].innerHTML.toLowerCase();
			} else if (this.sortingStrategy === 'date') {
				return nodes[int].getAttribute('folderPos').toLowerCase();
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





