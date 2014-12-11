/**
 * ownCloud - core
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright Bernhard Posselt 2014
 */

(function (exports) {

	'use strict';

	exports.Search = {
		/**
		 * contains closures that are called to format search results
		 */
		formatter:{},
		setFormatter: function(type, formatter) {
			this.formatter[type] = formatter;
		},
		hasFormatter: function(type) {
			return typeof this.formatter[type] !== 'undefined';
		},
		getFormatter: function(type) {
			return this.formatter[type];
		},
		/**
		 * contains closures that are called when a search result has been clicked
		 */
		handler:{},
		setHandler: function(type, handler) {
			this.handler[type] = handler;
		},
		hasHandler: function(type) {
			return typeof this.handler[type] !== 'undefined';
		},
		getHandler: function(type) {
			return this.handler[type];
		},
		currentResult:-1,
		lastQuery:'',
		lastResults:{},
		/**
		 * Do a search query and display the results
		 * @param {string} query the search query
		 */
		search: _.debounce(function(query, page, size) {
			if(query) {
				exports.addStyle('search','results');
				if (typeof page !== 'number') {
					page = 0;
				}
				if (typeof size !== 'number') {
					size = 30;
				}
				$.getJSON(OC.generateUrl('search/ajax/search.php'), {query:query, page:page, size:size }, function(results) {
					exports.Search.lastResults = results;
					exports.Search.showResults(results);
				});
			}
		}, 500)
	};


	$(document).ready(function () {
		$('form.searchbox').submit(function(event) {
			event.preventDefault();
		});
		$('#searchbox').keyup(function(event) {
			if (event.keyCode === 13) { //enter
				if(exports.Search.currentResult > -1) {
					var result = $('#searchresults tr.result a')[exports.Search.currentResult];
					window.location = $(result).attr('href');
				}
			} else if(event.keyCode === 38) { //up
				if(exports.Search.currentResult > 0) {
					exports.Search.currentResult--;
					exports.Search.renderCurrent();

				}
			} else if(event.keyCode === 40) { //down
				if(exports.Search.lastResults.length > exports.Search.currentResult + 1){
					exports.Search.currentResult++;
					exports.Search.renderCurrent();
				}
			} else if(event.keyCode === 27) { //esc
				exports.Search.hide();
				if (FileList && typeof FileList.unfilter === 'function') { //TODO add hook system
					FileList.unfilter();
				}
			} else {
				var query = $('#searchbox').val();
				if (exports.Search.lastQuery !== query) {
					exports.Search.lastQuery = query;
					exports.Search.currentResult = -1;
					if (FileList && typeof FileList.filter === 'function') { //TODO add hook system
						FileList.filter(query);
					}
					if (query.length > 2) {
						exports.Search.search(query);
					} else {
						if (exports.Search.hide) {
							exports.Search.hide();
						}
					}
				}
			}
		});
	});

}(OC));

/**
 * @deprecated use get/setFormatter() instead
 */
OC.search.customResults = {};
/**
 * @deprecated use get/setFormatter() instead
 */
OC.search.resultTypes = {};