/**
* ownCloud
*
* @author Vincent Petry
* @copyright 2015 Vincent Petry <pvince81@owncloud.com>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

describe('OC.Settings.Apps tests', function() {
	var Apps;

	beforeEach(function() {
		var $el = $('<div id="apps-list"></div>' +
			'<div id="apps-list-empty" class="hidden"></div>' +
			'<div id="app-template">' +
			// dummy template for testing
			'<div id="app-{{id}}" data-id="{{id}}" class="section">{{name}}</div>' +
			'</div>'
		);
		$('#testArea').append($el);

		Apps = OC.Settings.Apps;
	});
	afterEach(function() {
		Apps.State.apps = null;
		Apps.State.currentCategory = null;
	});

	describe('Filtering apps', function() {
		var oldApps;

		function loadApps(appList) {
			Apps.State.apps = appList;

			_.each(appList, function(appSpec) {
				Apps.renderApp(appSpec);
			});
		}

		function getResultsFromDom() {
			var results = [];
			$('#apps-list .section:not(.hidden)').each(function() {
				results.push($(this).attr('data-id'));
			});
			return results;
		}

		beforeEach(function() {
			loadApps([
				{id: 'appone', name: 'App One', description: 'The first app', author: 'author1', level: 200},
				{id: 'apptwo', name: 'App Two', description: 'The second app', author: 'author2', level: 100},
				{id: 'appthree', name: 'App Three', description: 'Third app', author: 'author3', level: 0},
				{id: 'somestuff', name: 'Some Stuff', description: 'whatever', author: 'author4', level: 0}
			]);
		});

		it('returns no results when query does not match anything', function() {
			expect(getResultsFromDom().length).toEqual(4);
			expect($('#apps-list:not(.hidden)').length).toEqual(1);
			expect($('#apps-list-empty:not(.hidden)').length).toEqual(0);

			Apps.filter('absurdity');
			expect(getResultsFromDom().length).toEqual(0);
			expect($('#apps-list:not(.hidden)').length).toEqual(0);
			expect($('#apps-list-empty:not(.hidden)').length).toEqual(1);

			Apps.filter('');
			expect(getResultsFromDom().length).toEqual(4);
			expect($('#apps-list:not(.hidden)').length).toEqual(1);
			expect($('#apps-list-empty:not(.hidden)').length).toEqual(0);
			expect(getResultsFromDom().length).toEqual(4);
		});
		it('returns relevant results when query matches name', function() {
			expect($('#apps-list:not(.hidden)').length).toEqual(1);
			expect($('#apps-list-empty:not(.hidden)').length).toEqual(0);

			var results;
			Apps.filter('app');
			results = getResultsFromDom();
			expect(results.length).toEqual(3);
			expect(results[0]).toEqual('appone');
			expect(results[1]).toEqual('apptwo');
			expect(results[2]).toEqual('appthree');

			expect($('#apps-list:not(.hidden)').length).toEqual(1);
			expect($('#apps-list-empty:not(.hidden)').length).toEqual(0);
		});
		it('returns relevant result when query matches name', function() {
			var results;
			Apps.filter('TWO');
			results = getResultsFromDom();
			expect(results.length).toEqual(1);
			expect(results[0]).toEqual('apptwo');
		});
		it('returns relevant result when query matches description', function() {
			var results;
			Apps.filter('ever');
			results = getResultsFromDom();
			expect(results.length).toEqual(1);
			expect(results[0]).toEqual('somestuff');
		});
		it('returns relevant results when query matches author name', function() {
			var results;
			Apps.filter('author');
			results = getResultsFromDom();
			expect(results.length).toEqual(4);
			expect(results[0]).toEqual('appone');
			expect(results[1]).toEqual('apptwo');
			expect(results[2]).toEqual('appthree');
			expect(results[3]).toEqual('somestuff');
		});
		it('returns relevant result when query matches author name', function() {
			var results;
			Apps.filter('thor3');
			results = getResultsFromDom();
			expect(results.length).toEqual(1);
			expect(results[0]).toEqual('appthree');
		});
		it('returns relevant result when query matches level name', function() {
			var results;
			Apps.filter('Offic');
			results = getResultsFromDom();
			expect(results.length).toEqual(1);
			expect(results[0]).toEqual('appone');
		});
		it('returns relevant result when query matches level name', function() {
			var results;
			Apps.filter('Appro');
			results = getResultsFromDom();
			expect(results.length).toEqual(1);
			expect(results[0]).toEqual('apptwo');
		});
		it('returns relevant result when query matches level name', function() {
			var results;
			Apps.filter('Exper');
			results = getResultsFromDom();
			expect(results.length).toEqual(2);
			expect(results[0]).toEqual('appthree');
			expect(results[1]).toEqual('somestuff');
		});
	});

	describe('loading categories', function() {
		var suite = this;

		beforeEach( function(){
			suite.server = sinon.fakeServer.create();
		});

		afterEach( function(){
			suite.server.restore();
		});

		function getResultsFromDom() {
			var results = [];
			$('#apps-list .section:not(.hidden)').each(function() {
				results.push($(this).attr('data-id'));
			});
			return results;
		}

		it('sorts all applications using the level', function() {
			Apps.loadCategory('TestId');

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify({
					apps: [
						{
							id: 'foo',
							name: 'Foo app',
							level: 0
						},
						{
							id: 'alpha',
							name: 'Alpha app',
							level: 300
						},
						{
							id: 'nolevel',
							name: 'No level'
						},
						{
							id: 'zork',
							name: 'Some famous adventure game',
							level: 200
						},
						{
							id: 'delta',
							name: 'Mathematical symbol',
							level: 200
						}
					]
				})
			);

			var results = getResultsFromDom();
			expect(results.length).toEqual(5);
			expect(results).toEqual(['alpha', 'delta', 'zork', 'foo', 'nolevel']);
			expect(OC.Settings.Apps.State.apps).toEqual({
				'foo': {
					id: 'foo',
					name: 'Foo app',
					level: 0
				},
				'alpha': {
					id: 'alpha',
					name: 'Alpha app',
					level: 300
				},
				'nolevel': {
					id: 'nolevel',
					name: 'No level'
				},
				'zork': {
					id: 'zork',
					name: 'Some famous adventure game',
					level: 200
				},
				'delta': {
					id: 'delta',
					name: 'Mathematical symbol',
					level: 200
				}
			});
		});
	});

});
