/*
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

describe('OCA.Sharing external tests', function() {
	var plugin;
	var urlQueryStub;
	var promptDialogStub;
	var confirmDialogStub;

	function dummyShowDialog() {
		var deferred = $.Deferred();
		deferred.resolve();
		return deferred.promise();
	}

	beforeEach(function() {
		plugin = OCA.Sharing.ExternalShareDialogPlugin;
		urlQueryStub = sinon.stub(OC.Util.History, 'parseUrlQuery');

		confirmDialogStub = sinon.stub(OC.dialogs, 'confirm', dummyShowDialog);
		promptDialogStub = sinon.stub(OC.dialogs, 'prompt', dummyShowDialog);

		plugin.filesApp = {
			fileList: {
				reload: sinon.stub()
			}
		}
	});
	afterEach(function() {
		urlQueryStub.restore();
		confirmDialogStub.restore();
		promptDialogStub.restore();
		plugin = null;
	});
	describe('confirmation dialog from URL', function() {
		var testShare;

		/**
		 * Checks that the server call's query matches what is
		 * expected.
		 *
		 * @param {Object} expectedQuery expected query params
		 */
		function checkRequest(expectedQuery) {
			var request = fakeServer.requests[0];
			var query = OC.parseQueryString(request.requestBody);
			expect(request.method).toEqual('POST');
			expect(query).toEqual(expectedQuery);

			request.respond(
				200,
				{'Content-Type': 'application/json'},
				JSON.stringify({status: 'success'})
			);
			expect(plugin.filesApp.fileList.reload.calledOnce).toEqual(true);
		}

		beforeEach(function() {
			testShare = {
				remote: 'http://example.com/owncloud',
				token: 'abcdefg',
				owner: 'theowner',
				name: 'the share name'
			};
		});
		it('does nothing when no share was passed in URL', function() {
			urlQueryStub.returns({});
			plugin.processIncomingShareFromUrl();
			expect(promptDialogStub.notCalled).toEqual(true);
			expect(confirmDialogStub.notCalled).toEqual(true);
			expect(fakeServer.requests.length).toEqual(0);
		});
		it('sends share info to server on confirm', function() {
			urlQueryStub.returns(testShare);
			plugin.processIncomingShareFromUrl();
			expect(promptDialogStub.notCalled).toEqual(true);
			expect(confirmDialogStub.calledOnce).toEqual(true);
			confirmDialogStub.getCall(0).args[2](true);
			expect(fakeServer.requests.length).toEqual(1);
			checkRequest({
				remote: 'http://example.com/owncloud',
				token: 'abcdefg',
				owner: 'theowner',
				name: 'the share name',
				password: ''
			});
		});
		it('sends share info with password to server on confirm', function() {
			testShare = _.extend(testShare, {protected: 1});
			urlQueryStub.returns(testShare);
			plugin.processIncomingShareFromUrl();
			expect(promptDialogStub.calledOnce).toEqual(true);
			expect(confirmDialogStub.notCalled).toEqual(true);
			promptDialogStub.getCall(0).args[2](true, 'thepassword');
			expect(fakeServer.requests.length).toEqual(1);
			checkRequest({
				remote: 'http://example.com/owncloud',
				token: 'abcdefg',
				owner: 'theowner',
				name: 'the share name',
				password: 'thepassword'
			});
		});
		it('does not send share info on cancel', function() {
			urlQueryStub.returns(testShare);
			plugin.processIncomingShareFromUrl();
			expect(promptDialogStub.notCalled).toEqual(true);
			expect(confirmDialogStub.calledOnce).toEqual(true);
			confirmDialogStub.getCall(0).args[2](false);
			expect(fakeServer.requests.length).toEqual(0);
		});
	});
	describe('show dialog for each share to confirm', function() {
		// TODO test plugin.processSharesToConfirm()
	});
});
