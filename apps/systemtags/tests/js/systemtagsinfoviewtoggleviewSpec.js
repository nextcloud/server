/**
 *
 * @copyright Copyright (c) 2017, Daniel Calviño Sánchez (danxuliu@gmail.com)
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

describe('OCA.SystemTags.SystemTagsInfoViewToggleView', function () {

	var systemTagsInfoView;
	var view;

	beforeEach(function() {
		systemTagsInfoView = new OCA.SystemTags.SystemTagsInfoView();
		view = new OCA.SystemTags.SystemTagsInfoViewToggleView({ systemTagsInfoView: systemTagsInfoView });
	});

	afterEach(function() {
		view.remove();
		systemTagsInfoView.remove();
	});

	describe('initialize', function() {
		it('fails if a "systemTagsInfoView" parameter is not provided', function() {
			var constructor = function() {
				return new OCA.SystemTags.SystemTagsInfoViewToggleView({});
			}

			expect(constructor).toThrow();
		});
	});

	describe('click on element', function() {

		var isVisibleStub;
		var showStub;
		var hideStub;
		var openDropdownStub;

		beforeEach(function() {
			isVisibleStub = sinon.stub(systemTagsInfoView, 'isVisible');
			showStub = sinon.stub(systemTagsInfoView, 'show');
			hideStub = sinon.stub(systemTagsInfoView, 'hide');
			openDropdownStub = sinon.stub(systemTagsInfoView, 'openDropdown');
		});

		afterEach(function() {
			isVisibleStub.restore();
			showStub.restore();
			hideStub.restore();
			openDropdownStub.restore();
		});

		it('shows a not visible SystemTagsInfoView', function() {
			isVisibleStub.returns(false);

			view.$el.click();

			expect(isVisibleStub.calledOnce).toBeTruthy();
			expect(showStub.calledOnce).toBeTruthy();
			expect(openDropdownStub.calledOnce).toBeTruthy();
			expect(openDropdownStub.calledAfter(showStub)).toBeTruthy();
			expect(hideStub.notCalled).toBeTruthy();
		});

		it('hides a visible SystemTagsInfoView', function() {
			isVisibleStub.returns(true);

			view.$el.click();

			expect(isVisibleStub.calledOnce).toBeTruthy();
			expect(hideStub.calledOnce).toBeTruthy();
			expect(showStub.notCalled).toBeTruthy();
			expect(openDropdownStub.notCalled).toBeTruthy();
		});

	});

});
