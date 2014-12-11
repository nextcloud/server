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
		 * Initialize the search box and results
		 *
		 * @param $searchBox container element with existing markup for the #searchbox form
		 * @private
		 */
		initialize: function($searchBox) {

			var that = this;

			/**
			 * contains closures that are called to format search results
			 */
			var formatters = {};
			this.setFormatter = function(type, formatter) {
				formatters[type] = formatter;
			};
			this.hasFormatter = function(type) {
				return typeof formatters[type] !== 'undefined';
			};
			this.getFormatter = function(type) {
				return formatters[type];
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

			/**
			 * Do a search query and display the results
			 * @param {string} query the search query
			 */
			this.search = _.debounce(function(query, page, size) {
				if(query) {
					OC.addStyle('search','results');
					if (typeof page !== 'number') {
						page = 0;
					}
					if (typeof size !== 'number') {
						size = 30;
					}
					// prevent double pages
					if (query === lastPage && page === lastPage && currentResult !== -1) {
						return;
					}
					$.getJSON(OC.generateUrl('search/ajax/search.php'), {query:query, page:page, size:size }, function(results) {
						lastQuery = query;
						lastPage = page;
						lastSize = size;
						lastResults = results;
						if (page === 0) {
							showResults(results);
						} else {
							addResults(results);
						}
					});
				}
			}, 500);
			var $searchResults = false;

			function showResults(results) {
				if (results.length === 0) {
					return;
				}
				if (!$searchResults) {
					var $parent = $('<div class="searchresults-wrapper"/>');
					$('#app-content').append($parent);
					$parent.load(OC.webroot + '/search/templates/part.results.html', function () {
						$searchResults = $parent.find('#searchresults');
						$searchResults.click(function (event) {
							that.hideResults();
							event.stopPropagation();
						});
						$(document).click(function (event) {
							that.hideResults();
							if (FileList && typeof FileList.unfilter === 'function') { //TODO add hook system
								FileList.unfilter();
							}
						});
						$searchResults.on('scroll', _.bind(onScroll, this));
						lastResults = results;
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

					$row.find('td.icon').css('background-image', 'url(' + OC.imagePath('core', 'places/link') + ')');
					/**
					 * Give plugins the ability to customize the search results. see result.js for examples
					 */
					if (that.hasFormatter(result.type)) {
						that.getFormatter(result.type)($row, result);
					} else {
						// for backward compatibility add text div
						$row.find('td.info div.name').addClass('result');
						$row.find('td.result div.name').after('<div class="text"></div>');
						$row.find('td.result div.text').text(result.name);
						if (OC.search.customResults && OC.search.customResults[result.type]) {
							OC.search.customResults[result.type]($row, result);
						}
					}
					$searchResults.find('tbody').append($row);
				});
			}
			function renderCurrent() {
				var result = $searchResults.find('tr.result')[currentResult];
				if (result) {
					var $result = $(result);
					var currentOffset = $searchResults.scrollTop();
					$searchResults.animate({
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
					if ($searchBox.val().length > 2) {
						$searchBox.val('');
						if (FileList && typeof FileList.unfilter === 'function') { //TODO add hook system
							FileList.unfilter();
						}
					}
					if ($searchBox.val().length === 0) {
						if (FileList && typeof FileList.unfilter === 'function') { //TODO add hook system
							FileList.unfilter();
						}
					}
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
					that.hideResults();
					if (FileList && typeof FileList.unfilter === 'function') { //TODO add hook system
						FileList.unfilter();
					}
				} else {
					var query = $searchBox.val();
					if (lastQuery !== query) {
						lastQuery = query;
						currentResult = -1;
						if (FileList && typeof FileList.filter === 'function') { //TODO add hook system
							FileList.filter(query);
						}
						if (query.length > 2) {
							that.search(query);
						} else {
							if (that.hideResults) {
								that.hideResults();
							}
						}
					}
				}
			});

			/**
			 * Event handler for when scrolling the list container.
			 * This appends/renders the next page of entries when reaching the bottom.
			 */
			function onScroll(e) {
				if ( $searchResults.scrollTop() + $searchResults.height() > $searchResults.find('table').height() - 300 ) {
					that.search(lastQuery, lastPage + 1);
				}
			}

			$('form.searchbox').submit(function(event) {
				event.preventDefault();
			});
		}
	};
	OCA.Search = Search;
})();

$(document).ready(function() {
	OC.Search = new OCA.Search($('#searchbox'));
});

/**
 * @deprecated use get/setFormatter() instead
 */
OC.search.customResults = {};
/**
 * @deprecated use get/setFormatter() instead
 */
OC.search.resultTypes = {};