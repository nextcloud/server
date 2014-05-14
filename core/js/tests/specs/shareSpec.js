/**
* ownCloud
*
* @author Vincent Petry
* @copyright 2014 Vincent Petry <pvince81@owncloud.com>
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

/* global oc_appconfig */
describe('OC.Share tests', function() {
	describe('dropdown', function() {
		var $container;
		var oldAppConfig;
		var loadItemStub;
		var autocompleteStub;
		beforeEach(function() {
			$('#testArea').append($('<div id="shareContainer"></div>'));
			$container = $('#shareContainer');
			/* jshint camelcase:false */
			oldAppConfig = oc_appconfig.core;
			loadItemStub = sinon.stub(OC.Share, 'loadItem');

			loadItemStub.returns({
				reshare: [],
				shares: []
			});

			autocompleteStub = sinon.stub($.fn, 'autocomplete', function() {
				// dummy container with the expected attributes
				var $el = $('<div></div>').data('ui-autocomplete', {});
				return $el;
			});
		});
		afterEach(function() {
			/* jshint camelcase:false */
			oc_appconfig.core = oldAppConfig;
			loadItemStub.restore();

			autocompleteStub.restore();
		});
		it('calls loadItem with the correct arguments', function() {
			OC.Share.showDropDown(
				'file',
			   	123,
			   	$container,
				'http://localhost/dummylink',
				31,
				'shared_file_name.txt'
			);
			expect(loadItemStub.calledOnce).toEqual(true);
			expect(loadItemStub.calledWith('file', 123)).toEqual(true);
		});
		it('shows the dropdown with default values', function() {
			var $el;
			OC.Share.showDropDown(
				'file',
			   	123,
			   	$container,
				'http://localhost/dummylink',
				31,
				'shared_file_name.txt'
			);
			$el = $container.find('#dropdown');
			expect($el.length).toEqual(1);
			expect($el.attr('data-item-type')).toEqual('file');
			expect($el.attr('data-item-source')).toEqual('123');
			// TODO: expect that other parts are rendered correctly
		});
		it('shows default expiration date when set', function() {
			oc_appconfig.core.defaultExpireDateEnabled = "yes";
			oc_appconfig.core.defaultExpireDate = '';
			// TODO: expect that default date was set
		});
		it('shows default expiration date is set but disabled', function() {
			oc_appconfig.core.defaultExpireDateEnabled = "no";
			oc_appconfig.core.defaultExpireDate = '';
			// TODO: expect that default date was NOT set
		});
		// TODO: test password field visibility (whenever enforced or not)
		// TODO: check link share field visibility based on whether it is allowed
		// TODO: check public upload visibility based on config
	});
});

