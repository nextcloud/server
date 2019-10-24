/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
describe('OCA.Versions.VersionsTabView', function() {
	var VersionCollection = OCA.Versions.VersionCollection;
	var VersionModel = OCA.Versions.VersionModel;
	var VersionsTabView = OCA.Versions.VersionsTabView;

	var fetchStub, fileInfoModel, tabView, testVersions, clock;

	beforeEach(function() {
		clock = sinon.useFakeTimers(Date.UTC(2015, 6, 17, 1, 2, 0, 3));
		var time1 = Date.UTC(2015, 6, 17, 1, 2, 0, 3) / 1000;
		var time2 = Date.UTC(2015, 6, 15, 1, 2, 0, 3) / 1000;

		var version1 = new VersionModel({
			id: time1,
			timestamp: time1,
			name: 'some file.txt',
			size: 140,
			fullPath: '/subdir/some file.txt',
			mimetype: 'text/plain'
		});
		var version2 = new VersionModel({
			id: time2,
			timestamp: time2,
			name: 'some file.txt',
			size: 150,
			fullPath: '/subdir/some file.txt',
			mimetype: 'text/plain'
		});

		testVersions = [version1, version2];

		fetchStub = sinon.stub(VersionCollection.prototype, 'fetch');
		fileInfoModel = new OCA.Files.FileInfoModel({
			id: 123,
			name: 'test.txt',
			permissions: OC.PERMISSION_READ | OC.PERMISSION_UPDATE
		});
		tabView = new VersionsTabView();
		tabView.render();
	});

	afterEach(function() {
		fetchStub.restore();
		tabView.remove();
		clock.restore();
	});

	describe('rendering', function() {
		it('reloads matching versions when setting file info model', function() {
			tabView.setFileInfo(fileInfoModel);
			expect(fetchStub.calledOnce).toEqual(true);
		});

		it('renders loading icon while fetching versions', function() {
			tabView.setFileInfo(fileInfoModel);
			tabView.collection.trigger('request');

			expect(tabView.$el.find('.loading').length).toEqual(1);
			expect(tabView.$el.find('.versions li').length).toEqual(0);
		});

		it('renders versions', function() {

			tabView.setFileInfo(fileInfoModel);
			tabView.collection.set(testVersions);

			var version1 = testVersions[0];
			var version2 = testVersions[1];
			var $versions = tabView.$el.find('.versions>li');
			expect($versions.length).toEqual(2);
			var $item = $versions.eq(0);
			expect($item.find('.downloadVersion').attr('href')).toEqual(version1.getDownloadUrl());
			expect($item.find('.versiondate').text()).toEqual('seconds ago');
			expect($item.find('.size').text()).toEqual('< 1 KB');
			expect($item.find('.revertVersion').length).toEqual(1);

			$item = $versions.eq(1);
			expect($item.find('.downloadVersion').attr('href')).toEqual(version2.getDownloadUrl());
			expect($item.find('.versiondate').text()).toEqual('2 days ago');
			expect($item.find('.size').text()).toEqual('< 1 KB');
			expect($item.find('.revertVersion').length).toEqual(1);
		});

		it('does not render revert button when no update permissions', function() {

			fileInfoModel.set('permissions', OC.PERMISSION_READ);
			tabView.setFileInfo(fileInfoModel);
			tabView.collection.set(testVersions);

			var version1 = testVersions[0];
			var version2 = testVersions[1];
			var $versions = tabView.$el.find('.versions>li');
			expect($versions.length).toEqual(2);
			var $item = $versions.eq(0);
			expect($item.find('.downloadVersion').attr('href')).toEqual(version1.getDownloadUrl());
			expect($item.find('.versiondate').text()).toEqual('seconds ago');
			expect($item.find('.revertVersion').length).toEqual(0);

			$item = $versions.eq(1);
			expect($item.find('.downloadVersion').attr('href')).toEqual(version2.getDownloadUrl());
			expect($item.find('.versiondate').text()).toEqual('2 days ago');
			expect($item.find('.revertVersion').length).toEqual(0);
		});
	});

	describe('Reverting', function() {
		var revertStub;

		beforeEach(function() {
			revertStub = sinon.stub(VersionModel.prototype, 'revert');
			tabView.setFileInfo(fileInfoModel);
			tabView.collection.set(testVersions);
		});
		
		afterEach(function() {
			revertStub.restore();
		});

		it('tells the model to revert when clicking "Revert"', function() {
			tabView.$el.find('.revertVersion').eq(1).click();

			expect(revertStub.calledOnce).toEqual(true);
		});
		it('triggers busy state during revert', function() {
			var busyStub = sinon.stub();
			fileInfoModel.on('busy', busyStub);

			tabView.$el.find('.revertVersion').eq(1).click();

			expect(busyStub.calledOnce).toEqual(true);
			expect(busyStub.calledWith(fileInfoModel, true)).toEqual(true);

			busyStub.reset();
			revertStub.getCall(0).args[0].success();

			expect(busyStub.calledOnce).toEqual(true);
			expect(busyStub.calledWith(fileInfoModel, false)).toEqual(true);
		});
		it('updates the file info model with the information from the reverted revision', function() {
			var changeStub = sinon.stub();
			fileInfoModel.on('change', changeStub);

			tabView.$el.find('.revertVersion').eq(1).click();

			expect(changeStub.notCalled).toEqual(true);

			revertStub.getCall(0).args[0].success();

			expect(changeStub.calledOnce).toEqual(true);
			var changes = changeStub.getCall(0).args[0].changed;
			expect(changes.size).toEqual(150);
			expect(changes.mtime).toEqual(testVersions[1].get('timestamp') * 1000);
			expect(changes.etag).toBeDefined();
		});
		it('shows notification on revert error', function() {
			var notificationStub = sinon.stub(OC.Notification, 'show');

			tabView.$el.find('.revertVersion').eq(1).click();

			revertStub.getCall(0).args[0].error();

			expect(notificationStub.calledOnce).toEqual(true);

			notificationStub.restore();
		});
	});
});
