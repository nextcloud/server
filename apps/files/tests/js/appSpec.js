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

describe('OCA.Files.App tests', function() {
	var App = OCA.Files.App;
	var pushStateStub;
	var parseUrlQueryStub;

	beforeEach(function() {
		$('#testArea').append(
			'<div id="app-navigation">' +
			'<ul><li data-id="files"><a>Files</a></li>' +
			'<li data-id="other"><a>Other</a></li>' +
			'</div>' +
			'<div id="app-content">' +
			'<div id="app-content-files" class="hidden">' +
			'</div>' +
			'<div id="app-content-other" class="hidden">' +
			'</div>' +
			'</div>' +
			'</div>'
		);

		pushStateStub = sinon.stub(OC.Util.History, 'pushState');
		parseUrlQueryStub = sinon.stub(OC.Util.History, 'parseUrlQuery');
		parseUrlQueryStub.returns({});

		App.initialize();
	});
	afterEach(function() {
		App.navigation = null;
		App.fileList = null;
		App.files = null;
		App.fileActions.clear();
		App.fileActions = null;

		pushStateStub.restore();
		parseUrlQueryStub.restore();
	});

	describe('initialization', function() {
		it('initializes the default file list with the default file actions', function() {
			expect(App.fileList).toBeDefined();
			expect(App.fileList.fileActions.actions.all).toBeDefined();
			expect(App.fileList.$el.is('#app-content-files')).toEqual(true);
		});
	});

	describe('URL handling', function() {
		it('pushes the state to the URL when current app changed directory', function() {
			$('#app-content-files').trigger(new $.Event('changeDirectory', {dir: 'subdir'}));
			expect(pushStateStub.calledOnce).toEqual(true);
			expect(pushStateStub.getCall(0).args[0].dir).toEqual('subdir');
			expect(pushStateStub.getCall(0).args[0].view).not.toBeDefined();

			$('li[data-id=other]>a').click();
			pushStateStub.reset();

			$('#app-content-other').trigger(new $.Event('changeDirectory', {dir: 'subdir'}));
			expect(pushStateStub.calledOnce).toEqual(true);
			expect(pushStateStub.getCall(0).args[0].dir).toEqual('subdir');
			expect(pushStateStub.getCall(0).args[0].view).toEqual('other');
		});
		describe('onpopstate', function() {
			it('sends "urlChanged" event to current app', function() {
				var handler = sinon.stub();
				$('#app-content-files').on('urlChanged', handler);
				App._onPopState({view: 'files', dir: '/somedir'});
				expect(handler.calledOnce).toEqual(true);
				expect(handler.getCall(0).args[0].view).toEqual('files');
				expect(handler.getCall(0).args[0].dir).toEqual('/somedir');
			});
			it('sends "show" event to current app and sets navigation', function() {
				var handlerFiles = sinon.stub();
				var handlerOther = sinon.stub();
				$('#app-content-files').on('show', handlerFiles);
				$('#app-content-other').on('show', handlerOther);
				App._onPopState({view: 'other', dir: '/somedir'});
				expect(handlerFiles.notCalled).toEqual(true);
				expect(handlerOther.calledOnce).toEqual(true);

				handlerFiles.reset();
				handlerOther.reset();

				App._onPopState({view: 'files', dir: '/somedir'});
				expect(handlerFiles.calledOnce).toEqual(true);
				expect(handlerOther.notCalled).toEqual(true);

				expect(App.navigation.getActiveItem()).toEqual('files');
				expect($('#app-content-files').hasClass('hidden')).toEqual(false);
				expect($('#app-content-other').hasClass('hidden')).toEqual(true);
			});
			it('does not send "show" event to current app when already visible', function() {
				var handler = sinon.stub();
				$('#app-content-files').on('show', handler);
				App._onPopState({view: 'files', dir: '/somedir'});
				expect(handler.notCalled).toEqual(true);
			});
			it('state defaults to files app with root dir', function() {
				var handler = sinon.stub();
				parseUrlQueryStub.returns({});
				$('#app-content-files').on('urlChanged', handler);
				App._onPopState();
				expect(handler.calledOnce).toEqual(true);
				expect(handler.getCall(0).args[0].view).toEqual('files');
				expect(handler.getCall(0).args[0].dir).toEqual('/');
			});
		});
		describe('navigation', function() {
			it('switches the navigation item and panel visibility when onpopstate', function() {
				App._onPopState({view: 'other', dir: '/somedir'});
				expect(App.navigation.getActiveItem()).toEqual('other');
				expect($('#app-content-files').hasClass('hidden')).toEqual(true);
				expect($('#app-content-other').hasClass('hidden')).toEqual(false);
				expect($('li[data-id=files]').hasClass('selected')).toEqual(false);
				expect($('li[data-id=other]').hasClass('selected')).toEqual(true);

				App._onPopState({view: 'files', dir: '/somedir'});

				expect(App.navigation.getActiveItem()).toEqual('files');
				expect($('#app-content-files').hasClass('hidden')).toEqual(false);
				expect($('#app-content-other').hasClass('hidden')).toEqual(true);
				expect($('li[data-id=files]').hasClass('selected')).toEqual(true);
				expect($('li[data-id=other]').hasClass('selected')).toEqual(false);
			});
			it('clicking on navigation switches the panel visibility', function() {
				$('li[data-id=other]>a').click();
				expect(App.navigation.getActiveItem()).toEqual('other');
				expect($('#app-content-files').hasClass('hidden')).toEqual(true);
				expect($('#app-content-other').hasClass('hidden')).toEqual(false);
				expect($('li[data-id=files]').hasClass('selected')).toEqual(false);
				expect($('li[data-id=other]').hasClass('selected')).toEqual(true);

				$('li[data-id=files]>a').click();
				expect(App.navigation.getActiveItem()).toEqual('files');
				expect($('#app-content-files').hasClass('hidden')).toEqual(false);
				expect($('#app-content-other').hasClass('hidden')).toEqual(true);
				expect($('li[data-id=files]').hasClass('selected')).toEqual(true);
				expect($('li[data-id=other]').hasClass('selected')).toEqual(false);
			});
			it('clicking on navigation sends "urlChanged" event', function() {
				var handler = sinon.stub();
				$('#app-content-other').on('urlChanged', handler);
				$('li[data-id=other]>a').click();
				expect(handler.calledOnce).toEqual(true);
				expect(handler.getCall(0).args[0].view).toEqual('other');
				expect(handler.getCall(0).args[0].dir).toEqual('/');
			});
		});
	});
});
