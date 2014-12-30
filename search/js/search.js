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
	 */
	var Search = function($searchBox) {
		this.initialize($searchBox);
	};
	/**
	 * @memberof OC
	 */
	Search.prototype = {

		/**
		 * Initialize the search box
		 *
		 * @param $searchBox container element with existing markup for the #searchbox form
		 * @private
		 */
		initialize: function($searchBox) {

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
			var lastPage = 0;
			var lastSize = 30;
			var lastResults = {};

			this.getLastQuery = function() {
				return lastQuery;
			};

			/**
			 * Do a search query and display the results
			 * @param {string} query the search query
			 */
			this.search = _.debounce(function(query, inApps, page, size) {
				if(query) {
					OC.addStyle('search','results');
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
					if ($searchResults && query === lastQuery && page === lastPage&& size === lastSize) {
						return;
					}
					lastQuery = query;
					lastPage = page;
					lastSize = size;
					$.getJSON(OC.generateUrl('search/ajax/search.php'), {query:query, inApps:inApps, page:page, size:size }, function(results) {
						lastResults = results;
						if (page === 1) {
							showResults(results);
						} else {
							addResults(results);
						}
					});
				}
			}, 500);

			function getCurrentApp() {
				var classList = document.getElementById('content').className.split(/\s+/);
				for (var i = 0; i < classList.length; i++) {
					if (classList[i].indexOf('app-') === 0) {
						return classList[i].substr(4);
					}
				}
				return false;
			}

			var $searchResults = false;
			var $wrapper = false;
			var $status = false;
			const summaryAndStatusHeight = 118;

			function isStatusOffScreen() {
				return $searchResults.position().top + summaryAndStatusHeight > window.innerHeight;
			}

			function placeStatus() {
				if (isStatusOffScreen()) {
					$status.addClass('fixed');
				} else {
					$status.removeClass('fixed');
				}
			}
			function showResults(results) {
				if (results.length === 0) {
					return;
				}
				if (!$searchResults) {
					$wrapper = $('<div class="searchresults-wrapper"/>');
					$('#app-content')
						.append($wrapper)
						.find('.viewcontainer').css('min-height', 'initial');
					$wrapper.load(OC.webroot + '/search/templates/part.results.html', function () {
						$searchResults = $wrapper.find('#searchresults');
						$searchResults.on('click', 'tr.result', function (event) {
							var $row = $(this);
							var item = $row.data('result');
							if(self.hasHandler(item.type)){
								var result = self.getHandler(item.type)($row, result, event);
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
						$(document).click(function (event) {
							$searchBox.val('');
							if(self.hasFilter(getCurrentApp())) {
								self.getFilter(getCurrentApp())('');
							}
							self.hideResults();
						});
						$('#app-content').on('scroll', _.bind(onScroll, this));
						lastResults = results;
						$status = $searchResults.find('#status')
							.data('count', results.length)
							.text(t('search', '{count} search results in other folders', {count:results.length}, results.length));
						placeStatus();
						showResults(results);
					});
				} else {
					$searchResults.find('tr.result').remove();
					$searchResults.show();
					addResults(results);
				}
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
					} else {
						// not showing result, decrease counter
						var count = $status.data('count') - 1;
						$status.data('count', count)
							.text(t('search', '{count} search results in other places', {count:count}, count));
					}
				});
			}
			function renderCurrent() {
				var result = $searchResults.find('tr.result')[currentResult];
				if (result) {
					var $result = $(result);
					var currentOffset = $searchResults.scrollTop();
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
				if ($searchResults) {
					$searchResults.hide();
					$wrapper.remove();
					$searchResults = false;
					$wrapper = false;
				}
			};

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
				} else if(event.keyCode === 27) { //esc
					$searchBox.val('');
					if(self.hasFilter(getCurrentApp())) {
						self.getFilter(getCurrentApp())('');
					}
					self.hideResults();
				} else {
					var query = $searchBox.val();
					if (lastQuery !== query) {
						currentResult = -1;
						if(self.hasFilter(getCurrentApp())) {
							self.getFilter(getCurrentApp())(query);
						}
						if (query.length > 2) {
							self.search(query);
						} else {
							self.hideResults();
						}
					}
				}
			});

			/**
			 * Event handler for when scrolling the list container.
			 * This appends/renders the next page of entries when reaching the bottom.
			 */
			function onScroll(e) {
				if ($searchResults) {
					//if ( $searchResults && $searchResults.scrollTop() + $searchResults.height() > $searchResults.find('table').height() - 300 ) {
					//	self.search(lastQuery, lastPage + 1);
					//}
					placeStatus();
				}
			}

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

			OC.Plugins.attach('OCA.Search', this);
		}
	};
	OCA.Search = Search;
})();

$(document).ready(function() {
	OC.Search = new OCA.Search($('#searchbox'));
});

/**
 * @deprecated use get/setRenderer() instead
 */
OC.search.customResults = {};
/**
 * @deprecated use get/setRenderer() instead
 */
OC.search.resultTypes = {};