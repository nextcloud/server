/**
 * @copyright 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

describe('OCA.Sharing.ShareBreadCrumbView tests', function() {
	var BreadCrumb = OCA.Files.BreadCrumb;
	var SharedBreadCrum = OCA.Sharing.ShareBreadCrumbView;

	describe('Rendering', function() {
		var bc;
		var sbc;
		var shareTab;
		beforeEach(function() {
			bc = new BreadCrumb({
				getCrumbUrl: function(part, index) {
					// for testing purposes
					return part.dir + '#' + index;
				}
			});
			shareTab = new OCA.Sharing.ShareTabView();
			sbc = new SharedBreadCrum({
				shareTab: shareTab
			});
			bc.addDetailView(sbc);
		});
		afterEach(function() {
			bc = null;
			sbc = null;
			shareModel = null;
		});
		it('Do not render in root', function() {
			var dirInfo = new OC.Files.FileInfo({
				id: 42,
				path: '/',
				type: 'dir',
				name: ''
			});
			bc.setDirectoryInfo(dirInfo);
			bc.setDirectory('');
			bc.render();
			expect(bc.$el.hasClass('breadcrumb')).toEqual(true);
			expect(bc.$el.find('.icon-share').length).toEqual(0);
			expect(bc.$el.find('.shared').length).toEqual(0);
			expect(bc.$el.find('.icon-public').length).toEqual(0);
		});
		it('Render in dir', function() {
			var dirInfo = new OC.Files.FileInfo({
				id: 42,
				path: '/foo',
				type: 'dir'
			});
			bc.setDirectoryInfo(dirInfo);
			bc.setDirectory('/foo');
			bc.render();
			expect(bc.$el.hasClass('breadcrumb')).toEqual(true);
			expect(bc.$el.find('.icon-share').length).toEqual(1);
			expect(bc.$el.find('.shared').length).toEqual(0);
			expect(bc.$el.find('.icon-public').length).toEqual(0);
		});
		it('Render shared if dir is shared with user', function() {
			var dirInfo = new OC.Files.FileInfo({
				id: 42,
				path: '/foo',
				type: 'dir',
				shareTypes: [OC.Share.SHARE_TYPE_USER]
			});
			bc.setDirectoryInfo(dirInfo);
			bc.setDirectory('/foo');
			bc.render();
			expect(bc.$el.hasClass('breadcrumb')).toEqual(true);
			expect(bc.$el.find('.icon-share').length).toEqual(1);
			expect(bc.$el.find('.shared').length).toEqual(1);
			expect(bc.$el.find('.icon-public').length).toEqual(0);
		});
		it('Render shared if dir is shared with group', function() {
			var dirInfo = new OC.Files.FileInfo({
				id: 42,
				path: '/foo',
				type: 'dir',
				shareTypes: [OC.Share.SHARE_TYPE_GROUP]
			});
			bc.setDirectoryInfo(dirInfo);
			bc.setDirectory('/foo');
			bc.render();
			expect(bc.$el.hasClass('breadcrumb')).toEqual(true);
			expect(bc.$el.find('.icon-share').length).toEqual(1);
			expect(bc.$el.find('.shared').length).toEqual(1);
			expect(bc.$el.find('.icon-public').length).toEqual(0);
		});
		it('Render shared if dir is shared by link', function() {
			var dirInfo = new OC.Files.FileInfo({
				id: 42,
				path: '/foo',
				type: 'dir',
				shareTypes: [OC.Share.SHARE_TYPE_LINK]
			});
			bc.setDirectoryInfo(dirInfo);
			bc.setDirectory('/foo');
			bc.render();
			expect(bc.$el.hasClass('breadcrumb')).toEqual(true);
			expect(bc.$el.find('.icon-share').length).toEqual(0);
			expect(bc.$el.find('.shared').length).toEqual(1);
			expect(bc.$el.find('.icon-public').length).toEqual(1);
		});
		it('Render shared if dir is shared with remote', function() {
			var dirInfo = new OC.Files.FileInfo({
				id: 42,
				path: '/foo',
				type: 'dir',
				shareTypes: [OC.Share.SHARE_TYPE_REMOTE]
			});
			bc.setDirectoryInfo(dirInfo);
			bc.setDirectory('/foo');
			bc.render();
			expect(bc.$el.hasClass('breadcrumb')).toEqual(true);
			expect(bc.$el.find('.icon-share').length).toEqual(1);
			expect(bc.$el.find('.shared').length).toEqual(1);
			expect(bc.$el.find('.icon-public').length).toEqual(0);
		});
		it('Render link shared if at least one is a link share', function() {
			var dirInfo = new OC.Files.FileInfo({
				id: 42,
				path: '/foo',
				type: 'dir',
				shareTypes: [
					OC.Share.SHARE_TYPE_USER,
					OC.Share.SHARE_TYPE_GROUP,
					OC.Share.SHARE_TYPE_LINK,
					OC.Share.SHARE_TYPE_EMAIL,
					OC.Share.SHARE_TYPE_REMOTE
				]
			});
			bc.setDirectoryInfo(dirInfo);
			bc.setDirectory('/foo');
			bc.render();
			expect(bc.$el.hasClass('breadcrumb')).toEqual(true);
			expect(bc.$el.find('.icon-share').length).toEqual(0);
			expect(bc.$el.find('.shared').length).toEqual(1);
			expect(bc.$el.find('.icon-public').length).toEqual(1);
		});
		it('Remove shared status from user share', function() {
			var dirInfo = new OC.Files.FileInfo({
				id: 42,
				path: '/foo',
				type: 'dir',
				shareTypes: [OC.Share.SHARE_TYPE_USER]
			});

			bc.setDirectory('/foo');
			bc.setDirectoryInfo(dirInfo);
			bc.render();

			var mock = sinon.createStubInstance(OCA.Files.FileList);
			mock.showDetailsView = function() { };
			OCA.Files.App.fileList = mock;
			var spy = sinon.spy(mock, 'showDetailsView');
			bc.$el.find('.icon-share').click();

			expect(spy.calledOnce).toEqual(true);

			var model = sinon.createStubInstance(OC.Share.ShareItemModel);
			model.getSharesWithCurrentItem = function() { return [] };
			model.hasLinkShare = function() { return false; };

			shareTab.trigger('sharesChanged', model);

			expect(bc.$el.hasClass('breadcrumb')).toEqual(true);
			expect(bc.$el.find('.icon-share').length).toEqual(1);
			expect(bc.$el.find('.shared').length).toEqual(0);
			expect(bc.$el.find('.icon-public').length).toEqual(0);
		});
		it('Add link share to user share', function() {
			var dirInfo = new OC.Files.FileInfo({
				id: 42,
				path: '/foo',
				type: 'dir',
				shareTypes: [OC.Share.SHARE_TYPE_USER]
			});

			bc.setDirectory('/foo');
			bc.setDirectoryInfo(dirInfo);
			bc.render();

			var mock = sinon.createStubInstance(OCA.Files.FileList);
			mock.showDetailsView = function() { };
			OCA.Files.App.fileList = mock;
			var spy = sinon.spy(mock, 'showDetailsView');
			bc.$el.find('.icon-share').click();

			expect(spy.calledOnce).toEqual(true);

			var model = sinon.createStubInstance(OC.Share.ShareItemModel);
			model.getSharesWithCurrentItem = function() { return [
				{share_type: OC.Share.SHARE_TYPE_USER}
			] };
			model.hasLinkShare = function() { return true; };

			shareTab.trigger('sharesChanged', model);

			expect(bc.$el.hasClass('breadcrumb')).toEqual(true);
			expect(bc.$el.find('.icon-share').length).toEqual(0);
			expect(bc.$el.find('.shared').length).toEqual(1);
			expect(bc.$el.find('.icon-public').length).toEqual(1);
		});
	});
});
