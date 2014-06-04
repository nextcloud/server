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
			// horrible parameters
			$('#testArea').append('<input id="allowShareWithLink" type="hidden" value="yes">');
			$container = $('#shareContainer');
			/* jshint camelcase:false */
			oldAppConfig = _.extend({}, oc_appconfig.core);
			oc_appconfig.core.enforcePasswordForPublicLink = false;

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
		describe('Share with link', function() {
			// TODO: test ajax calls
			// TODO: test password field visibility (whenever enforced or not)
			// TODO: check public upload visibility based on config
			it('shows share with link checkbox when allowed', function() {
				$('#allowShareWithLink').val('yes');
				OC.Share.showDropDown(
					'file',
					123,
					$container,
					'http://localhost/dummylink',
					31,
					'shared_file_name.txt'
				);
				expect($('#dropdown #linkCheckbox').length).toEqual(1);
			});
			it('does not show share with link checkbox when not allowed', function() {
				$('#allowShareWithLink').val('no');
				OC.Share.showDropDown(
					'file',
					123,
					$container,
					'http://localhost/dummylink',
					31,
					'shared_file_name.txt'
				);
				expect($('#dropdown #linkCheckbox').length).toEqual(0);
			});
		});
		describe('"sharesChanged" event', function() {
			var autocompleteOptions;
			var handler;
			beforeEach(function() {
				handler = sinon.stub();
				loadItemStub.returns({
					reshare: [],
					shares: [{
						id: 100,
						item_source: 123,
						permissions: 31,
						share_type: OC.Share.SHARE_TYPE_USER,
						share_with: 'user1',
						share_with_displayname: 'User One'
					}]
				});
				OC.Share.showDropDown(
					'file',
					123,
					$container,
					'http://localhost/dummylink',
					31,
					'shared_file_name.txt'
				);
				$('#dropdown').on('sharesChanged', handler);
				autocompleteOptions = autocompleteStub.getCall(0).args[0];
			});
			afterEach(function() {
				autocompleteOptions = null;
				handler = null;
			});
			it('triggers "sharesChanged" event when adding shares', function() {
				// simulate autocomplete selection
				autocompleteOptions.select(new $.Event('select'), {
					item: {
						label: 'User Two',
						value: {
							shareType: OC.Share.SHARE_TYPE_USER,
							shareWith: 'user2'
						}
					}
				});
				fakeServer.requests[0].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({status: 'success'})
				);
				expect(handler.calledOnce).toEqual(true);
				var shares = handler.getCall(0).args[0].shares;
				expect(shares).toBeDefined();
				expect(shares[OC.Share.SHARE_TYPE_USER][0].share_with_displayname).toEqual('User One');
				expect(shares[OC.Share.SHARE_TYPE_USER][1].share_with_displayname).toEqual('User Two');
				expect(shares[OC.Share.SHARE_TYPE_GROUP]).not.toBeDefined();
			});
			it('triggers "sharesChanged" event when deleting shares', function() {
				$('#dropdown .unshare:eq(0)').click();
				fakeServer.requests[0].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({status: 'success'})
				);
				expect(handler.calledOnce).toEqual(true);
				var shares = handler.getCall(0).args[0].shares;
				expect(shares).toBeDefined();
				expect(shares[OC.Share.SHARE_TYPE_USER]).toEqual([]);
				expect(shares[OC.Share.SHARE_TYPE_GROUP]).not.toBeDefined();
			});
			it('triggers "sharesChanged" event when toggling link share', function() {
				// simulate autocomplete selection
				$('#dropdown #linkCheckbox').click();
				fakeServer.requests[0].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({status: 'success', data: { token: 'abc' }})
				);
				expect(handler.calledOnce).toEqual(true);
				var shares = handler.getCall(0).args[0].shares;
				expect(shares).toBeDefined();
				expect(shares[OC.Share.SHARE_TYPE_USER][0].share_with_displayname).toEqual('User One');
				expect(shares[OC.Share.SHARE_TYPE_GROUP]).not.toBeDefined();

				handler.reset();

				// uncheck checkbox
				$('#dropdown #linkCheckbox').click();
				fakeServer.requests[1].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({status: 'success'})
				);

				expect(handler.calledOnce).toEqual(true);
				shares = handler.getCall(0).args[0].shares;
				expect(shares).toBeDefined();
				expect(shares[OC.Share.SHARE_TYPE_USER][0].share_with_displayname).toEqual('User One');
				expect(shares[OC.Share.SHARE_TYPE_GROUP]).not.toBeDefined();
			});
		});
	});
});

