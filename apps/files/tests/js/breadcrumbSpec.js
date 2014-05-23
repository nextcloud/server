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

describe('OCA.Files.BreadCrumb tests', function() {
	var BreadCrumb = OCA.Files.BreadCrumb;

	describe('Rendering', function() {
		var bc;
		beforeEach(function() {
			bc = new BreadCrumb({
				getCrumbUrl: function(part, index) {
					// for testing purposes
					return part.dir + '#' + index;
				}
			});
		});
		afterEach(function() {
			bc = null;
		});
		it('Renders its own container', function() {
			bc.render();
			expect(bc.$el.hasClass('breadcrumb')).toEqual(true);
		});
		it('Renders root by default', function() {
			var $crumbs;
			bc.render();
			$crumbs = bc.$el.find('.crumb');
			expect($crumbs.length).toEqual(1);
			expect($crumbs.eq(0).find('a').attr('href')).toEqual('/#0');
			expect($crumbs.eq(0).find('img').length).toEqual(1);
			expect($crumbs.eq(0).attr('data-dir')).toEqual('/');
		});
		it('Renders root when switching to root', function() {
			var $crumbs;
			bc.setDirectory('/somedir');
			bc.setDirectory('/');
			$crumbs = bc.$el.find('.crumb');
			expect($crumbs.length).toEqual(1);
			expect($crumbs.eq(0).attr('data-dir')).toEqual('/');
		});
		it('Renders last crumb with "last" class', function() {
			bc.setDirectory('/abc/def');
			expect(bc.$el.find('.crumb:last').hasClass('last')).toEqual(true);
		});
		it('Renders single path section', function() {
			var $crumbs;
			bc.setDirectory('/somedir');
			$crumbs = bc.$el.find('.crumb');
			expect($crumbs.length).toEqual(2);
			expect($crumbs.eq(0).find('a').attr('href')).toEqual('/#0');
			expect($crumbs.eq(0).find('img').length).toEqual(1);
			expect($crumbs.eq(0).attr('data-dir')).toEqual('/');
			expect($crumbs.eq(1).find('a').attr('href')).toEqual('/somedir#1');
			expect($crumbs.eq(1).find('img').length).toEqual(0);
			expect($crumbs.eq(1).attr('data-dir')).toEqual('/somedir');
		});
		it('Renders multiple path sections and special chars', function() {
			var $crumbs;
			bc.setDirectory('/somedir/with space/abc');
			$crumbs = bc.$el.find('.crumb');
			expect($crumbs.length).toEqual(4);
			expect($crumbs.eq(0).find('a').attr('href')).toEqual('/#0');
			expect($crumbs.eq(0).find('img').length).toEqual(1);
			expect($crumbs.eq(0).attr('data-dir')).toEqual('/');

			expect($crumbs.eq(1).find('a').attr('href')).toEqual('/somedir#1');
			expect($crumbs.eq(1).find('img').length).toEqual(0);
			expect($crumbs.eq(1).attr('data-dir')).toEqual('/somedir');

			expect($crumbs.eq(2).find('a').attr('href')).toEqual('/somedir/with space#2');
			expect($crumbs.eq(2).find('img').length).toEqual(0);
			expect($crumbs.eq(2).attr('data-dir')).toEqual('/somedir/with space');

			expect($crumbs.eq(3).find('a').attr('href')).toEqual('/somedir/with space/abc#3');
			expect($crumbs.eq(3).find('img').length).toEqual(0);
			expect($crumbs.eq(3).attr('data-dir')).toEqual('/somedir/with space/abc');
		});
	});
	describe('Events', function() {
		it('Calls onClick handler when clicking on a crumb', function() {
			var handler = sinon.stub();
			var bc = new BreadCrumb({
				onClick: handler
			});
			bc.setDirectory('/one/two/three/four');
			bc.$el.find('.crumb:eq(3)').click();
			expect(handler.calledOnce).toEqual(true);
			expect(handler.getCall(0).thisValue).toEqual(bc.$el.find('.crumb').get(3));

			handler.reset();
			bc.$el.find('.crumb:eq(0) a').click();
			expect(handler.calledOnce).toEqual(true);
			expect(handler.getCall(0).thisValue).toEqual(bc.$el.find('.crumb').get(0));
		});
		it('Calls onDrop handler when dropping on a crumb', function() {
			var droppableStub = sinon.stub($.fn, 'droppable');
			var handler = sinon.stub();
			var bc = new BreadCrumb({
				onDrop: handler
			});
			bc.setDirectory('/one/two/three/four');
			expect(droppableStub.calledOnce).toEqual(true);

			expect(droppableStub.getCall(0).args[0].drop).toBeDefined();
			// simulate drop
			droppableStub.getCall(0).args[0].drop({dummy: true});

			expect(handler.calledOnce).toEqual(true);
			expect(handler.getCall(0).args[0]).toEqual({dummy: true});

			droppableStub.restore();
		});
	});
	describe('Resizing', function() {
		var bc, dummyDir, widths, oldUpdateTotalWidth;

		beforeEach(function() {
			dummyDir = '/short name/longer name/looooooooooooonger/' +
				'even longer long long long longer long/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/last one';

			// using hard-coded widths (pre-measured) to avoid getting different
			// results on different browsers due to font engine differences
			widths = [41, 106, 112, 160, 257, 251, 91];

			oldUpdateTotalWidth = BreadCrumb.prototype._updateTotalWidth;
			BreadCrumb.prototype._updateTotalWidth = function() {
				// pre-set a width to simulate consistent measurement
				$('div.crumb').each(function(index){
					$(this).css('width', widths[index]);
				});

				return oldUpdateTotalWidth.apply(this, arguments);
			};

			bc = new BreadCrumb();
			// append dummy navigation and controls
			// as they are currently used for measurements
			$('#testArea').append(
				'<div id="controls"></div>'
			);
			$('#controls').append(bc.$el);
		});
		afterEach(function() {
			BreadCrumb.prototype._updateTotalWidth = oldUpdateTotalWidth;
			bc = null;
		});
		it('Hides breadcrumbs to fit max allowed width', function() {
			var $crumbs;

			bc.setMaxWidth(500);
			// triggers resize implicitly
			bc.setDirectory(dummyDir);
			$crumbs = bc.$el.find('.crumb');

			// first one is always visible
			expect($crumbs.eq(0).hasClass('hidden')).toEqual(false);
			// second one has ellipsis
			expect($crumbs.eq(1).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(1).find('.ellipsis').length).toEqual(1);
			// there is only one ellipsis in total
			expect($crumbs.find('.ellipsis').length).toEqual(1);
			// subsequent elements are hidden
			expect($crumbs.eq(2).hasClass('hidden')).toEqual(true);
			expect($crumbs.eq(3).hasClass('hidden')).toEqual(true);
			expect($crumbs.eq(4).hasClass('hidden')).toEqual(true);
			expect($crumbs.eq(5).hasClass('hidden')).toEqual(true);
			expect($crumbs.eq(6).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(7).hasClass('hidden')).toEqual(false);
		});
		it('Updates the breadcrumbs when reducing max allowed width', function() {
			var $crumbs;

			// enough space
			bc.setMaxWidth(1800);

			expect(bc.$el.find('.ellipsis').length).toEqual(0);

			// triggers resize implicitly
			bc.setDirectory(dummyDir);

			// simulate increase
			bc.setMaxWidth(950);

			$crumbs = bc.$el.find('.crumb');
			// first one is always visible
			expect($crumbs.eq(0).hasClass('hidden')).toEqual(false);
			// second one has ellipsis
			expect($crumbs.eq(1).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(1).find('.ellipsis').length).toEqual(1);
			// there is only one ellipsis in total
			expect($crumbs.find('.ellipsis').length).toEqual(1);
			// subsequent elements are hidden
			expect($crumbs.eq(2).hasClass('hidden')).toEqual(true);
			expect($crumbs.eq(3).hasClass('hidden')).toEqual(true);
			// the rest is visible
			expect($crumbs.eq(4).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(5).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(6).hasClass('hidden')).toEqual(false);
		});
		it('Removes the ellipsis when there is enough space', function() {
			var $crumbs;

			bc.setMaxWidth(500);
			// triggers resize implicitly
			bc.setDirectory(dummyDir);
			$crumbs = bc.$el.find('.crumb');

			// ellipsis
			expect(bc.$el.find('.ellipsis').length).toEqual(1);

			// simulate increase
			bc.setMaxWidth(1800);

			// no ellipsis
			expect(bc.$el.find('.ellipsis').length).toEqual(0);

			// all are visible
			expect($crumbs.eq(0).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(1).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(2).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(3).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(4).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(5).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(6).hasClass('hidden')).toEqual(false);
		});
	});
});
