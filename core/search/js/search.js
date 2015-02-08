/**
 * ownCloud - core
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Jörn Friedrich Dreyer <jfd@owncloud.com>
 * @copyright Jörn Friedrich Dreyer 2014
 */

(function () {
	/**
	 * @class OCA.Search
	 * @classdesc
	 *
	 * The Search class manages a search queries and their results
	 *
	 * @param $searchBox container element with existing markup for the #searchbox form
	 * @param $searchResults container element for results und status message
	 */
	var Search = function($searchBox, $searchResults) {
		this.initialize($searchBox, $searchResults);
	};
	/**
	 * @memberof OC
	 */
	Search.prototype = {

		/**
		 * Initialize the search box
		 *
		 * @param $searchBox container element with existing markup for the #searchbox form
		 * @param $searchResults container element for results und status message
		 * @private
		 */
		initialize: function($searchBox, $searchResults) {

			var self = this;

			/**
			 * contains closures that are called to filter the current content
			 */
			var filters = {};
			this.setFilter = function(type, filter) {
				filters[type] = filter;
			};
			this.hasFilter = function(type) {
				return typeof filters[type] !== 'undefined';
			};
			this.getFilter = function(type) {
				return filters[type];
			};

			/**
			 * contains closures that are called to render search results
			 */
			var renderers = {};
			this.setRenderer = function(type, renderer) {
				renderers[type] = renderer;
			};
			this.hasRenderer = function(type) {
				return typeof renderers[type] !== 'undefined';
			};
			this.getRenderer = function(type) {
				return renderers[type];
			};

			/**
			 * contains closures that are called when a search result has been clicked
			 */
			var handlers = {};
			this.setHandler = function(type, handler) {
				handlers[type] = handler;
			};
			this.hasHandler = function(type) {
				return typeof handlers[type] !== 'undefined';
			};
			this.getHandler = function(type) {
				return handlers[type];
			};

			var currentResult = -1;
			var lastQuery = '';
			var lastInApps = [];
			var lastPage = 0;
			var lastSize = 30;
			var lastResults = [];
			var timeoutID = null;

			this.getLastQuery = function() {
				return lastQuery;
			};

			/**
			 * Do a search query and display the results
			 * @param {string} query the search query
			 */
			this.search = function(query, inApps, page, size) {
				if (query) {
					OC.addStyle('core/search','results');
					if (typeof page !== 'number') {
						page = 1;
					}
					if (typeof size !== 'number') {
						size = 30;
					}
					if (typeof inApps !== 'object') {
						var currentApp = getCurrentApp();
						if(currentApp) {
							inApps = [currentApp];
						} else {
							inApps = [];
						}
					}
					// prevent double pages
					if ($searchResults && query === lastQuery && page === lastPage && size === lastSize) {
						return;
					}
					window.clearTimeout(timeoutID);
					timeoutID = window.setTimeout(function() {
						lastQuery = query;
						lastInApps = inApps;
						lastPage = page;
						lastSize = size;

						//show spinner
						$searchResults.removeClass('hidden');
						$status.html(t('core', 'Searching other places')+'<img class="spinner" alt="search in progress" src="'+OC.webroot+'/core/img/loading.gif" />');

						// do the actual search query
						$.getJSON(OC.generateUrl('core/search'), {query:query, inApps:inApps, page:page, size:size }, function(results) {
							lastResults = results;
							if (page === 1) {
								showResults(results);
							} else {
								addResults(results);
							}
						});
					}, 500);
				}
			};

			//TODO should be a core method, see https://github.com/owncloud/core/issues/12557
			function getCurrentApp() {
				var content = document.getElementById('content');
				if (content) {
					var classList = document.getElementById('content').className.split(/\s+/);
					for (var i = 0; i < classList.length; i++) {
						if (classList[i].indexOf('app-') === 0) {
							return classList[i].substr(4);
						}
					}
				}
				return false;
			}

			var $status = $searchResults.find('#status');
			// summaryAndStatusHeight is a constant
			var summaryAndStatusHeight = 118;

			function isStatusOffScreen() {
				return $searchResults.position() && ($searchResults.position().top + summaryAndStatusHeight > window.innerHeight);
			}

			function placeStatus() {
				if (isStatusOffScreen()) {
					$status.addClass('fixed');
				} else {
					$status.removeClass('fixed');
				}
			}
			function showResults(results) {
				lastResults = results;
				$searchResults.find('tr.result').remove();
				$searchResults.removeClass('hidden');
				addResults(results);
			}
			function addResults(results) {
				var $template = $searchResults.find('tr.template');
				jQuery.each(results, function (i, result) {
					var $row = $template.clone();
					$row.removeClass('template');
					$row.addClass('result');

					$row.data('result', result);

					// generic results only have four attributes
					$row.find('td.info div.name').text(result.name);
					$row.find('td.info a').attr('href', result.link);

					/**
					 * Give plugins the ability to customize the search results. see result.js for examples
					 */
					if (self.hasRenderer(result.type)) {
						$row = self.getRenderer(result.type)($row, result);
					} else {
						// for backward compatibility add text div
						$row.find('td.info div.name').addClass('result');
						$row.find('td.result div.name').after('<div class="text"></div>');
						$row.find('td.result div.text').text(result.name);
						if (OC.search.customResults && OC.search.customResults[result.type]) {
							OC.search.customResults[result.type]($row, result);
						}
					}
					if ($row) {
						$searchResults.find('tbody').append($row);
					}
				});
				var count = $searchResults.find('tr.result').length;
				$status.data('count', count);
				if (count === 0) {
					$status.text(t('core', 'No search result in other places'));
				} else {
					$status.text(n('core', '{count} search result in other places', '{count} search results in other places', count, {count:count}));
				}
			}
			function renderCurrent() {
				var result = $searchResults.find('tr.result')[currentResult];
				if (result) {
					var $result = $(result);
					var currentOffset = $('#app-content').scrollTop();
					$('#app-content').animate({
						// Scrolling to the top of the new result
						scrollTop: currentOffset + $result.offset().top - $result.height() * 2
					}, {
						duration: 100
					});
					$searchResults.find('tr.result.current').removeClass('current');
					$result.addClass('current');
				}
			}
			this.hideResults = function() {
				$searchResults.addClass('hidden');
				$searchResults.find('tr.result').remove();
				lastQuery = false;
			};
			this.clear = function() {
				self.hideResults();
				if(self.hasFilter(getCurrentApp())) {
					self.getFilter(getCurrentApp())('');
				}
				$searchBox.val('');
				$searchBox.blur();
			};

			/**
			 * Event handler for when scrolling the list container.
			 * This appends/renders the next page of entries when reaching the bottom.
			 */
			function onScroll(e) {
				if ($searchResults && lastQuery !== false && lastResults.length > 0) {
					var resultsBottom = $searchResults.offset().top + $searchResults.height();
					var containerBottom = $searchResults.offsetParent().offset().top + $searchResults.offsetParent().height();
					if ( resultsBottom < containerBottom * 1.2 ) {
						self.search(lastQuery, lastInApps, lastPage + 1);
					}
					placeStatus();
				}
			}

			$('#app-content').on('scroll', _.bind(onScroll, this));

			/**
			 * scrolls the search results to the top
			 */
			function scrollToResults() {
				setTimeout(function() {
					if (isStatusOffScreen()) {
						var newScrollTop = $('#app-content').prop('scrollHeight') - $searchResults.height();
						console.log('scrolling to ' + newScrollTop);
						$('#app-content').animate({
							scrollTop: newScrollTop
						}, {
							duration: 100,
							complete: function () {
								scrollToResults();
							}
						});
					}
				}, 150);
			}

			$('form.searchbox').submit(function(event) {
				event.preventDefault();
			});

			$searchBox.on('search', function (event) {
				if($searchBox.val() === '') {
					if(self.hasFilter(getCurrentApp())) {
						self.getFilter(getCurrentApp())('');
					}
					self.hideResults();
				}
			});
			$searchBox.keyup(function(event) {
				if (event.keyCode === 13) { //enter
					if(currentResult > -1) {
						var result = $searchResults.find('tr.result a')[currentResult];
						window.location = $(result).attr('href');
					}
				} else if(event.keyCode === 38) { //up
					if(currentResult > 0) {
						currentResult--;
						renderCurrent();
					}
				} else if(event.keyCode === 40) { //down
					if(lastResults.length > currentResult + 1){
						currentResult++;
						renderCurrent();
					}
				} else {
					var query = $searchBox.val();
					if (lastQuery !== query) {
						currentResult = -1;
						if (query.length > 2) {
							self.search(query);
						} else {
							self.hideResults();
						}
						if(self.hasFilter(getCurrentApp())) {
							self.getFilter(getCurrentApp())(query);
						}
					}
				}
			});
			$(document).keyup(function(event) {
				if(event.keyCode === 27) { //esc
					$searchBox.val('');
					if(self.hasFilter(getCurrentApp())) {
						self.getFilter(getCurrentApp())('');
					}
					self.hideResults();
				}
			});

			$searchResults.on('click', 'tr.result', function (event) {
				var $row = $(this);
				var item = $row.data('result');
				if(self.hasHandler(item.type)){
					var result = self.getHandler(item.type)($row, item, event);
					$searchBox.val('');
					if(self.hasFilter(getCurrentApp())) {
						self.getFilter(getCurrentApp())('');
					}
					self.hideResults();
					return result;
				}
			});
			$searchResults.on('click', '#status', function (event) {
				event.preventDefault();
				scrollToResults();
				return false;
			});
			placeStatus();

			OC.Plugins.attach('OCA.Search', this);
		}
	};
	OCA.Search = Search;
})();

$(document).ready(function() {
	var $searchResults = $('#searchresults');
	if ($searchResults.length) {
		$searchResults.addClass('hidden');
		$('#app-content')
			.find('.viewcontainer').css('min-height', 'initial');
	} else {
		$searchResults = $('<div id="searchresults" class="hidden"/>');
		$('#app-content')
			.append($searchResults)
			.find('.viewcontainer').css('min-height', 'initial');
	}
	$searchResults.load(OC.webroot + '/core/search/templates/part.results.html', function () {
		OC.Search = new OCA.Search($('#searchbox'), $('#searchresults'));
	});
});

/**
 * @deprecated use get/setRenderer() instead
 */
OC.search.customResults = {};
/**
 * @deprecated use get/setRenderer() instead
 */
OC.search.resultTypes = {};