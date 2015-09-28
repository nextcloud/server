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
		detailsView.remove();
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
		var testView, testView2;

		beforeEach(function() {
			testView = new OCA.Files.DetailTabView({id: 'test1'});
			testView2 = new OCA.Files.DetailTabView({id: 'test2'});
			detailsView.addTabView(testView);
			detailsView.addTabView(testView2);
			detailsView.render();
		});
		it('initially renders only the selected tab', function() {
			expect(detailsView.$el.find('.tab').length).toEqual(1);
			expect(detailsView.$el.find('.tab').attr('id')).toEqual('test1');
		});
		it('updates tab model and rerenders on-demand as soon as it gets selected', function() {
			var tab1RenderStub = sinon.stub(testView, 'render');
			var tab2RenderStub = sinon.stub(testView2, 'render');
			var fileInfo1 = new OCA.Files.FileInfoModel({id: 5, name: 'test.txt'});
			var fileInfo2 = new OCA.Files.FileInfoModel({id: 8, name: 'test2.txt'});

			detailsView.setFileInfo(fileInfo1);

			// first tab renders, not the second one
			expect(tab1RenderStub.calledOnce).toEqual(true);
			expect(tab2RenderStub.notCalled).toEqual(true);

			// info got set only to the first visible tab
			expect(testView.getFileInfo()).toEqual(fileInfo1);
			expect(testView2.getFileInfo()).toBeUndefined();

			// select second tab for first render
			detailsView.$el.find('.tabHeader').eq(1).click();

			// second tab got rendered
			expect(tab2RenderStub.calledOnce).toEqual(true);
			expect(testView2.getFileInfo()).toEqual(fileInfo1);

			// select the first tab again
			detailsView.$el.find('.tabHeader').eq(0).click();

			// no re-render
			expect(tab1RenderStub.calledOnce).toEqual(true);
			expect(tab2RenderStub.calledOnce).toEqual(true);

			tab1RenderStub.reset();
			tab2RenderStub.reset();

			// switch to another file
			detailsView.setFileInfo(fileInfo2);

			// only the visible tab was updated and rerendered
			expect(tab1RenderStub.calledOnce).toEqual(true);
			expect(testView.getFileInfo()).toEqual(fileInfo2);

			// second/invisible tab still has old info, not rerendered
			expect(tab2RenderStub.notCalled).toEqual(true);
			expect(testView2.getFileInfo()).toEqual(fileInfo1);

			// reselect the second one
			detailsView.$el.find('.tabHeader').eq(1).click();

			// second tab becomes visible, updated and rendered
			expect(testView2.getFileInfo()).toEqual(fileInfo2);
			expect(tab2RenderStub.calledOnce).toEqual(true);

			tab1RenderStub.restore();
			tab2RenderStub.restore();
		});
		it('selects the first tab by default', function() {
			expect(detailsView.$el.find('.tabHeader').eq(0).hasClass('selected')).toEqual(true);
			expect(detailsView.$el.find('.tabHeader').eq(1).hasClass('selected')).toEqual(false);
			expect(detailsView.$el.find('.tab').eq(0).hasClass('hidden')).toEqual(false);
			expect(detailsView.$el.find('.tab').eq(1).length).toEqual(0);
		});
		it('switches the current tab when clicking on tab header', function() {
			detailsView.$el.find('.tabHeader').eq(1).click();
			expect(detailsView.$el.find('.tabHeader').eq(0).hasClass('selected')).toEqual(false);
			expect(detailsView.$el.find('.tabHeader').eq(1).hasClass('selected')).toEqual(true);
			expect(detailsView.$el.find('.tab').eq(0).hasClass('hidden')).toEqual(true);
			expect(detailsView.$el.find('.tab').eq(1).hasClass('hidden')).toEqual(false);
		});
		it('does not render tab headers when only one tab exists', function() {
			detailsView.remove();
			detailsView = new OCA.Files.DetailsView();
			testView = new OCA.Files.DetailTabView({id: 'test1'});
			detailsView.addTabView(testView);
			detailsView.render();

			expect(detailsView.$el.find('.tabHeader').length).toEqual(0);
		});
		it('sorts by order and then label', function() {
			detailsView.remove();
			detailsView = new OCA.Files.DetailsView();
			detailsView.addTabView(new OCA.Files.DetailTabView({id: 'abc', order: 20}));
			detailsView.addTabView(new OCA.Files.DetailTabView({id: 'def', order: 10}));
			detailsView.addTabView(new OCA.Files.DetailTabView({id: 'jkl'}));
			detailsView.addTabView(new OCA.Files.DetailTabView({id: 'ghi'}));
			detailsView.render();

			var tabs = detailsView.$el.find('.tabHeader').map(function() {
				return $(this).attr('data-tabid');
			}).toArray(); 

			expect(tabs).toEqual(['ghi', 'jkl', 'def', 'abc']);
		});
	});
});
