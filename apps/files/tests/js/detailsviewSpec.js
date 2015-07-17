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

describe('OCA.Files.DetailsView tests', function() {
	var detailsView;

	beforeEach(function() {
		detailsView = new OCA.Files.DetailsView();
	});
	afterEach(function() {
		detailsView.destroy();
		detailsView = undefined;
	});
	it('renders itself empty when nothing registered', function() {
		detailsView.render();
		expect(detailsView.$el.find('.detailFileInfoContainer').length).toEqual(1);
		expect(detailsView.$el.find('.tabsContainer').length).toEqual(1);
	});
	describe('file info detail view', function() {
		it('renders registered view', function() {
			var testView = new OCA.Files.DetailFileInfoView();
			var testView2 = new OCA.Files.DetailFileInfoView();
			detailsView.addDetailView(testView);
			detailsView.addDetailView(testView2);
			detailsView.render();

			expect(detailsView.$el.find('.detailFileInfoContainer .detailFileInfoView').length).toEqual(2);
		});
		it('updates registered tabs when fileinfo is updated', function() {
			var viewRenderStub = sinon.stub(OCA.Files.DetailFileInfoView.prototype, 'render');
			var testView = new OCA.Files.DetailFileInfoView();
			var testView2 = new OCA.Files.DetailFileInfoView();
			detailsView.addDetailView(testView);
			detailsView.addDetailView(testView2);
			detailsView.render();

			var fileInfo = {id: 5, name: 'test.txt'};
			viewRenderStub.reset();
			detailsView.setFileInfo(fileInfo);

			expect(testView.getFileInfo()).toEqual(fileInfo);
			expect(testView2.getFileInfo()).toEqual(fileInfo);

			expect(viewRenderStub.callCount).toEqual(2);
			viewRenderStub.restore();
		});
	});
	describe('tabs', function() {
		it('renders registered tabs', function() {
			var testView = new OCA.Files.DetailTabView('test1');
			var testView2 = new OCA.Files.DetailTabView('test2');
			detailsView.addTabView(testView);
			detailsView.addTabView(testView2);
			detailsView.render();

			expect(detailsView.$el.find('.tabsContainer .detailTabView').length).toEqual(2);
		});
		it('updates registered tabs when fileinfo is updated', function() {
			var tabRenderStub = sinon.stub(OCA.Files.DetailTabView.prototype, 'render');
			var testView = new OCA.Files.DetailTabView('test1');
			var testView2 = new OCA.Files.DetailTabView('test2');
			detailsView.addTabView(testView);
			detailsView.addTabView(testView2);
			detailsView.render();

			var fileInfo = {id: 5, name: 'test.txt'};
			tabRenderStub.reset();
			detailsView.setFileInfo(fileInfo);

			expect(testView.getFileInfo()).toEqual(fileInfo);
			expect(testView2.getFileInfo()).toEqual(fileInfo);

			expect(tabRenderStub.callCount).toEqual(2);
			tabRenderStub.restore();
		});
	});
});
