/*
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

describe('OCA.External.StatusManager tests', function() {
	var notificationStub;
	var fileList;
	var statusManager;
	var oldAppWebRoots;

	beforeEach(function() {
		notificationStub = sinon.stub(OC.Notification, 'showTemporary');
		var $content = $('<div id="content"></div>');
		$('#testArea').append($content);
		// dummy file list
		var $div = $(
			'<div id="listContainer">' +
			'<input id="dir" type="hidden" name="dir" value="/">' +
			'<table id="filestable">' +
			'<thead></thead>' +
			'<tbody id="fileList"></tbody>' +
			'</table>' +
			'</div>');
		$('#content').append($div);

		var fileActions = new OCA.Files.FileActions();
		fileList = new OCA.Files.FileList(
			$div, {
				fileActions : fileActions
			}
		);

		fileList.add({
			id: 1,
			type: 'folder',
			name: 'testmount',
			mountType: 'external-root',
			path: '/',
			mimetype: 'httpd/unix-directory',
			size: 12,
			permissions: OC.PERMISSION_ALL,
			etag: 'abc'
		});

		statusManager = OCA.External.StatusManager;

		oldAppWebRoots = _.extend({}, OC.appswebroots);
		OC.appswebroots['files_external'] = OC.webroot + '/apps/files_external';
	});
	afterEach(function() {
		statusManager.mountStatus = null;
		statusManager.mountPointList = null;

		notificationStub.restore();
		OC.appswebroots = oldAppWebRoots;
	});

	describe('getMountStatusForMount', function() {
		beforeEach(function() {
			statusManager.mountStatus = [];
		});

		it('retrieves mount status and passes it to callback', function() {
			var mountData = {
				id: 123,
				type: 'smb',
				mount_point: 'testmount',
				location: 3
			};

			var callbackStub = sinon.stub();
			statusManager.getMountStatusForMount(mountData, callbackStub);

			expect(fakeServer.requests.length).toEqual(1);

			var mountStatus = {
				type: 'smb',
				status: 0
			};

			var jsonData = JSON.stringify(mountStatus);
			var request = fakeServer.requests[0];

			expect(request.url).toEqual(OC.webroot + '/index.php/apps/files_external/globalstorages/123');

			fakeServer.requests[0].respond(
					200,
					{'Content-Type': 'application/json'},
					jsonData
			);


			expect(callbackStub.calledOnce).toEqual(true);
			expect(callbackStub.getCall(0).args[0]).toEqual(mountData);
			expect(callbackStub.getCall(0).args[1]).toEqual(mountStatus);

			// second call does not send request but returns known data
			statusManager.getMountStatusForMount(mountData, callbackStub);

			expect(fakeServer.requests.length).toEqual(1);

			expect(callbackStub.calledTwice).toEqual(true);
			expect(callbackStub.getCall(1).args[0]).toEqual(mountData);
			expect(callbackStub.getCall(1).args[1]).toEqual(mountStatus);
		});
		// TODO: case where status is not 0
		// TODO: error case
	});
	describe('getMountPointList', function() {
		// TODO
	});
	describe('processMountList', function() {
		var getActiveViewStub;
		var getCurrentAppContainerStub;

		beforeEach(function() {
			getActiveViewStub = sinon.stub(OCA.Files.App, 'getActiveView');
			getActiveViewStub.returns('files');
			getCurrentAppContainerStub = sinon.stub(OCA.Files.App, 'getCurrentAppContainer');
			getCurrentAppContainerStub.returns($('#testArea'));
		});
		afterEach(function() {
			getActiveViewStub.restore();
			getCurrentAppContainerStub.restore();
		});

		it('updates file list element with status', function() {
			var mountList = [{
				id: 123,
				mount_point: 'testmount',
				backend: 'smb',
				backendText: 'SMB',
				type: 'system',
				status: 0,
				location: ''
			}];
			statusManager.processMountList(mountList);

			var $tr = fileList.findFileEl('testmount');
			expect($tr.attr('data-external-backend')).toEqual('smb');
			expect($tr.attr('data-icon')).toEqual(
				OC.imagePath('windows_network_drive', 'folder-windows')
			);
			// TODO: thumbnail URL
			/*
			expect(OC.TestUtil.getImageUrl($tr.find('.thumbnail'))).toEqual(
				OC.imagePath('windows_network_drive', 'folder-windows')
			);
			*/
			// TODO: check CSS class

		});
	});
	describe('processMountStatus', function() {
		// TODO
	});
	describe('launchFullConnectivityCheckOneByOne', function() {
		var getMountPointListStub;
		var getMountStatusStub;
		var processMountStatusStub;
		var processMountListStub;

		beforeEach(function() {
			getMountPointListStub = sinon.stub(statusManager, 'getMountPointList');
			getMountStatusStub = sinon.stub(statusManager, 'getMountStatus');
			processMountStatusStub = sinon.stub(statusManager, 'processMountStatus');
			processMountListStub = sinon.stub(statusManager, 'processMountList');
		});
		afterEach(function() {
			getMountPointListStub.restore();
			getMountStatusStub.restore();
			processMountStatusStub.restore();
			processMountListStub.restore();
		});
		it('retrieves mount points then processes them', function() {
			statusManager.launchFullConnectivityCheck();

			expect(getMountPointListStub.calledOnce).toEqual(true);
			var mountList = [{
				id: 123,
				mount_point: 'testmount',
				backend: 'smb',
				backendText: 'SMB',
				type: 'system',
				status: 0,
				location: ''
			}];
			getMountPointListStub.yield(mountList);

			expect(processMountListStub.calledOnce).toEqual(true);
			expect(processMountListStub.calledWith(mountList)).toEqual(true);

			// TODO: continue checking getMountStatus, etc
		});
	});
	describe('recheckConnectivityForOne', function() {
		// TODO
	});
});
