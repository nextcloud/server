/**
* ownCloud
*
* @author Vincent Petry
* @copyright 2016 Vincent Petry <pvince81@owncloud.com>
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

describe('OC.SystemTags.SystemTagsInputField tests', function() {
	var view, select2Stub, clock;

	beforeEach(function() {
		clock = sinon.useFakeTimers();
		var $container = $('<div class="testInputContainer"></div>');
		select2Stub = sinon.stub($.fn, 'select2');
		select2Stub.returnsThis();
		$('#testArea').append($container);
	});
	afterEach(function() {
		select2Stub.restore();
		OC.SystemTags.collection.reset();
		clock.restore();
		view.remove();
		view = undefined;
	});

	describe('general behavior', function() {
		var $dropdown;

		beforeEach(function() {
			view = new OC.SystemTags.SystemTagsInputField();
			$('.testInputContainer').append(view.$el);
			$dropdown = $('<div class="select2-dropdown"></div>');
			select2Stub.withArgs('dropdown').returns($dropdown);
			$('#testArea').append($dropdown);

			view.render();
		});
		describe('rendering', function() {
			it('calls select2 on rendering', function() {
				expect(view.$el.find('input[name=tags]').length).toEqual(1);
				expect(select2Stub.called).toEqual(true);
			});
			it('formatResult renders rename button', function() {
				var opts = select2Stub.getCall(0).args[0];
				var $el = $(opts.formatResult({id: '1', name: 'test'}));
				expect($el.find('.rename').length).toEqual(1);
			});
		});
		describe('tag selection', function() {
			beforeEach(function() {
				var $el = view.$el.find('input');
				$el.val('1');

				view.collection.add([
					new OC.SystemTags.SystemTagModel({id: '1', name: 'abc'}),
					new OC.SystemTags.SystemTagModel({id: '2', name: 'def'}),
					new OC.SystemTags.SystemTagModel({id: '3', name: 'abd', userAssignable: false, canAssign: false}),
				]);
			});
			it('does not create dummy tag when user types non-matching name', function() {
				var opts = select2Stub.getCall(0).args[0];
				var result = opts.createSearchChoice('abc');
				expect(result).not.toBeDefined();
			});
			it('creates dummy tag when user types non-matching name', function() {
				var opts = select2Stub.getCall(0).args[0];
				var result = opts.createSearchChoice('abnew');
				expect(result.id).toEqual(-1);
				expect(result.name).toEqual('abnew');
				expect(result.isNew).toEqual(true);
				expect(result.userVisible).toEqual(true);
				expect(result.userAssignable).toEqual(true);
				expect(result.canAssign).toEqual(true);
			});
			it('creates dummy tag when user types non-matching name even with prefix of existing tag', function() {
				var opts = select2Stub.getCall(0).args[0];
				var result = opts.createSearchChoice('ab');
				expect(result.id).toEqual(-1);
				expect(result.name).toEqual('ab');
				expect(result.isNew).toEqual(true);
				expect(result.userVisible).toEqual(true);
				expect(result.userAssignable).toEqual(true);
				expect(result.canAssign).toEqual(true);
			});
			it('creates the real tag and fires select event after user selects the dummy tag', function() {
				var selectHandler = sinon.stub();
				view.on('select', selectHandler);
				var createStub = sinon.stub(OC.SystemTags.SystemTagsCollection.prototype, 'create');
				view.$el.find('input').trigger(new $.Event('select2-selecting', {
					object: {
						id: -1,
						name: 'newname',
						isNew: true
					}
				}));

				expect(createStub.calledOnce).toEqual(true);
				expect(createStub.getCall(0).args[0]).toEqual({
					name: 'newname',
					userVisible: true,
					userAssignable: true,
					canAssign: true
				});

				var newModel = new OC.SystemTags.SystemTagModel({
					id: '123',
					name: 'newname',
					userVisible: true,
					userAssignable: true,
					canAssign: true
				});

				// not called yet
				expect(selectHandler.notCalled).toEqual(true);

				select2Stub.withArgs('data').returns([{
					id: '1',
					name: 'abc'
				}]);

				createStub.yieldTo('success', newModel);

				expect(select2Stub.lastCall.args[0]).toEqual('data');
				expect(select2Stub.lastCall.args[1]).toEqual([{
						id: '1',
						name: 'abc'
					},
					newModel.toJSON()
				]);

				expect(selectHandler.calledOnce).toEqual(true);
				expect(selectHandler.getCall(0).args[0]).toEqual(newModel);

				createStub.restore();
			});
			it('triggers select event after selecting an existing tag', function() {
				var selectHandler = sinon.stub();
				view.on('select', selectHandler);
				view.$el.find('input').trigger(new $.Event('select2-selecting', {
					object: {
						id: '2',
						name: 'def'
					}
				}));

				expect(selectHandler.calledOnce).toEqual(true);
				expect(selectHandler.getCall(0).args[0]).toEqual(view.collection.get('2'));
			});
			it('triggers deselect event after deselecting an existing tag', function() {
				var selectHandler = sinon.stub();
				view.on('deselect', selectHandler);
				view.$el.find('input').trigger(new $.Event('select2-removing', {
					choice: {
						id: '2',
						name: 'def'
					}
				}));

				expect(selectHandler.calledOnce).toEqual(true);
				expect(selectHandler.getCall(0).args[0]).toEqual('2');
			});
			it('triggers select event and still adds to list even in case of conflict', function() {
				var selectHandler = sinon.stub();
				view.on('select', selectHandler);
				var fetchStub = sinon.stub(OC.SystemTags.SystemTagsCollection.prototype, 'fetch');
				var createStub = sinon.stub(OC.SystemTags.SystemTagsCollection.prototype, 'create');
				view.$el.find('input').trigger(new $.Event('select2-selecting', {
					object: {
						id: -1,
						name: 'newname',
						isNew: true
					}
				}));

				expect(createStub.calledOnce).toEqual(true);
				expect(createStub.getCall(0).args[0]).toEqual({
					name: 'newname',
					userVisible: true,
					userAssignable: true,
					canAssign: true
				});

				var newModel = new OC.SystemTags.SystemTagModel({
					id: '123',
					name: 'newname',
					userVisible: true,
					userAssignable: true
				});

				// not called yet
				expect(selectHandler.notCalled).toEqual(true);

				select2Stub.withArgs('data').returns([{
					id: '1',
					name: 'abc'
				}]);

				// simulate conflict response for tag creation
				createStub.yieldTo('error', view.collection, {status: 409});

				// at this point it fetches from the server
				expect(fetchStub.calledOnce).toEqual(true);
				// simulate fetch result by adding model to the collection
				view.collection.add(newModel);
				fetchStub.yieldTo('success', view.collection);

				expect(select2Stub.lastCall.args[0]).toEqual('data');
				expect(select2Stub.lastCall.args[1]).toEqual([{
						id: '1',
						name: 'abc'
					},
					newModel.toJSON()
				]);

				// select event still called
				expect(selectHandler.calledOnce).toEqual(true);
				expect(selectHandler.getCall(0).args[0]).toEqual(newModel);

				createStub.restore();
				fetchStub.restore();
			});
		});
		describe('tag actions', function() {
			var opts;

			beforeEach(function() {

				opts = select2Stub.getCall(0).args[0];

				view.collection.add([
					new OC.SystemTags.SystemTagModel({id: '1', name: 'abc'}),
				]);

				$dropdown.append(opts.formatResult(view.collection.get('1').toJSON()));

			});
			it('displays rename form when clicking rename', function() {
				$dropdown.find('.rename').mouseup();
				expect($dropdown.find('form.systemtags-rename-form').length).toEqual(1);
				expect($dropdown.find('form.systemtags-rename-form input').val()).toEqual('abc');
			});
			it('renames model and submits change when submitting form', function() {
				var saveStub = sinon.stub(OC.SystemTags.SystemTagModel.prototype, 'save');
				$dropdown.find('.rename').mouseup();
				$dropdown.find('form input').val('abc_renamed');
				$dropdown.find('form').trigger(new $.Event('submit'));

				expect(saveStub.calledOnce).toEqual(true);
				expect(saveStub.getCall(0).args[0]).toEqual({'name': 'abc_renamed'});

				expect($dropdown.find('.label').text()).toEqual('abc_renamed');
				expect($dropdown.find('form').length).toEqual(0);

				saveStub.restore();
			});
		});
		describe('setting data', function() {
			it('sets value when calling setValues', function() {
				var vals = ['1', '2'];
				view.setValues(vals);
				expect(select2Stub.lastCall.args[0]).toEqual('val');
				expect(select2Stub.lastCall.args[1]).toEqual(vals);
			});
			it('sets data when calling setData', function() {
				var vals = [{id: '1', name: 'test1'}, {id: '2', name: 'test2'}];
				view.setData(vals);
				expect(select2Stub.lastCall.args[0]).toEqual('data');
				expect(select2Stub.lastCall.args[1]).toEqual(vals);
			});
		});
	});

	describe('as admin', function() {
		var $dropdown;

		beforeEach(function() {
			view = new OC.SystemTags.SystemTagsInputField({
				isAdmin: true
			});
			$('.testInputContainer').append(view.$el);
			$dropdown = $('<div class="select2-dropdown"></div>');
			select2Stub.withArgs('dropdown').returns($dropdown);
			$('#testArea').append($dropdown);

			view.render();
		});
		it('formatResult renders tag name with visibility', function() {
			var opts = select2Stub.getCall(0).args[0];
			var $el = $(opts.formatResult({id: '1', name: 'test', userVisible: false, userAssignable: false}));
			expect($el.find('.label').text()).toEqual('test (invisible)');
		});
		it('formatSelection renders tag name with visibility', function() {
			var opts = select2Stub.getCall(0).args[0];
			var $el = $(opts.formatSelection({id: '1', name: 'test', userVisible: false, userAssignable: false}));
			expect($el.text().trim()).toEqual('test (invisible)');
		});
		describe('initSelection', function() {
			var fetchStub;
			var testTags;

			beforeEach(function() {
				fetchStub = sinon.stub(OC.SystemTags.SystemTagsCollection.prototype, 'fetch');
				testTags = [
					new OC.SystemTags.SystemTagModel({id: '1', name: 'test1'}),
					new OC.SystemTags.SystemTagModel({id: '2', name: 'test2'}),
					new OC.SystemTags.SystemTagModel({id: '3', name: 'test3', userAssignable: false, canAssign: false}),
					new OC.SystemTags.SystemTagModel({id: '4', name: 'test4', userAssignable: false, canAssign: true})
				];
			});
			afterEach(function() {
				fetchStub.restore();
			});
			it('grabs values from the full collection', function() {
				var $el = view.$el.find('input');
				$el.val('1,3,4');
				var opts = select2Stub.getCall(0).args[0];
				var callback = sinon.stub();
				opts.initSelection($el, callback);

				expect(fetchStub.calledOnce).toEqual(true);
				view.collection.add(testTags);
				fetchStub.yieldTo('success', view.collection);

				expect(callback.calledOnce).toEqual(true);
				var models = callback.getCall(0).args[0];
				expect(models.length).toEqual(3);
				expect(models[0].id).toEqual('1');
				expect(models[0].name).toEqual('test1');
				expect(models[0].locked).toBeFalsy();
				expect(models[1].id).toEqual('3');
				expect(models[1].name).toEqual('test3');
				expect(models[1].locked).toBeFalsy();
				expect(models[2].id).toEqual('4');
				expect(models[2].name).toEqual('test4');
				expect(models[2].locked).toBeFalsy();
			});
		});
		describe('autocomplete', function() {
			var fetchStub, opts;

			beforeEach(function() {
				fetchStub = sinon.stub(OC.SystemTags.SystemTagsCollection.prototype, 'fetch');
				opts = select2Stub.getCall(0).args[0];

				view.collection.add([
					new OC.SystemTags.SystemTagModel({id: '1', name: 'abc'}),
					new OC.SystemTags.SystemTagModel({id: '2', name: 'def'}),
					new OC.SystemTags.SystemTagModel({id: '3', name: 'abd', userAssignable: false, canAssign: false}),
					new OC.SystemTags.SystemTagModel({id: '4', name: 'Deg'}),
				]);
			});
			afterEach(function() {
				fetchStub.restore();
			});
			it('completes results', function() {
				var callback = sinon.stub();
				opts.query({
					term: 'ab',
					callback: callback
				});
				expect(fetchStub.calledOnce).toEqual(true);

				fetchStub.yieldTo('success', view.collection);

				expect(callback.calledOnce).toEqual(true);
				expect(callback.getCall(0).args[0].results).toEqual([
					{
						id: '1',
						name: 'abc',
						userVisible: true,
						userAssignable: true,
						canAssign: true
					},
					{
						id: '3',
						name: 'abd',
						userVisible: true,
						userAssignable: false,
						canAssign: false
					}
				]);
			});
			it('completes case insensitive', function() {
				var callback = sinon.stub();
				opts.query({
					term: 'de',
					callback: callback
				});
				expect(fetchStub.calledOnce).toEqual(true);

				fetchStub.yieldTo('success', view.collection);

				expect(callback.calledOnce).toEqual(true);
				expect(callback.getCall(0).args[0].results).toEqual([
					{
						id: '2',
						name: 'def',
						userVisible: true,
						userAssignable: true,
						canAssign: true
					},
					{
						id: '4',
						name: 'Deg',
						userVisible: true,
						userAssignable: true,
						canAssign: true
					}
				]);
			});
		});
		describe('tag actions', function() {
			var opts;

			beforeEach(function() {

				opts = select2Stub.getCall(0).args[0];

				view.collection.add([
					new OC.SystemTags.SystemTagModel({id: '1', name: 'abc'}),
				]);

				$dropdown.append(opts.formatResult(view.collection.get('1').toJSON()));

			});
			it('deletes model and submits change when clicking delete', function() {
				var destroyStub = sinon.stub(OC.SystemTags.SystemTagModel.prototype, 'destroy');

				expect($dropdown.find('.delete').length).toEqual(0);
				$dropdown.find('.rename').mouseup();
				// delete button appears
				expect($dropdown.find('.delete').length).toEqual(1);
				$dropdown.find('.delete').mouseup();

				expect(destroyStub.calledOnce).toEqual(true);
				expect(destroyStub.calledOn(view.collection.get('1')));

				destroyStub.restore();
			});
		});
	});

	describe('as user', function() {
		var $dropdown;

		beforeEach(function() {
			view = new OC.SystemTags.SystemTagsInputField({
				isAdmin: false
			});
			$('.testInputContainer').append(view.$el);
			$dropdown = $('<div class="select2-dropdown"></div>');
			select2Stub.withArgs('dropdown').returns($dropdown);
			$('#testArea').append($dropdown);

			view.render();
		});
		it('formatResult renders tag name only', function() {
			var opts = select2Stub.getCall(0).args[0];
			var $el = $(opts.formatResult({id: '1', name: 'test'}));
			expect($el.find('.label').text()).toEqual('test');
		});
		it('formatSelection renders tag name only', function() {
			var opts = select2Stub.getCall(0).args[0];
			var $el = $(opts.formatSelection({id: '1', name: 'test'}));
			expect($el.text().trim()).toEqual('test');
		});
		describe('initSelection', function() {
			var fetchStub;
			var testTags;

			beforeEach(function() {
				fetchStub = sinon.stub(OC.SystemTags.SystemTagsCollection.prototype, 'fetch');
				testTags = [
					new OC.SystemTags.SystemTagModel({id: '1', name: 'test1'}),
					new OC.SystemTags.SystemTagModel({id: '2', name: 'test2'}),
					new OC.SystemTags.SystemTagModel({id: '3', name: 'test3', userAssignable: false, canAssign: false}),
					new OC.SystemTags.SystemTagModel({id: '4', name: 'test4', userAssignable: false, canAssign: true})
				];
				view.render();
			});
			afterEach(function() {
				fetchStub.restore();
			});
			it('grabs values from the full collection', function() {
				var $el = view.$el.find('input');
				$el.val('1,3,4');
				var opts = select2Stub.getCall(0).args[0];
				var callback = sinon.stub();
				opts.initSelection($el, callback);

				expect(fetchStub.calledOnce).toEqual(true);
				view.collection.add(testTags);
				fetchStub.yieldTo('success', view.collection);

				expect(callback.calledOnce).toEqual(true);
				var models = callback.getCall(0).args[0];
				expect(models.length).toEqual(3);
				expect(models[0].id).toEqual('1');
				expect(models[0].name).toEqual('test1');
				expect(models[0].locked).toBeFalsy();
				expect(models[1].id).toEqual('3');
				expect(models[1].name).toEqual('test3');
				// restricted / cannot assign locks the entry
				expect(models[1].locked).toEqual(true);
				expect(models[2].id).toEqual('4');
				expect(models[2].name).toEqual('test4');
				expect(models[2].locked).toBeFalsy();
			});
		});
		describe('autocomplete', function() {
			var fetchStub, opts;

			beforeEach(function() {
				fetchStub = sinon.stub(OC.SystemTags.SystemTagsCollection.prototype, 'fetch');
				view.render();
				opts = select2Stub.getCall(0).args[0];

				view.collection.add([
					new OC.SystemTags.SystemTagModel({id: '1', name: 'abc'}),
					new OC.SystemTags.SystemTagModel({id: '2', name: 'def'}),
					new OC.SystemTags.SystemTagModel({id: '3', name: 'abd', userAssignable: false, canAssign: false}),
					new OC.SystemTags.SystemTagModel({id: '4', name: 'Deg'}),
					new OC.SystemTags.SystemTagModel({id: '5', name: 'abe', userAssignable: false, canAssign: true})
				]);
			});
			afterEach(function() {
				fetchStub.restore();
			});
			it('completes results excluding non-assignable tags', function() {
				var callback = sinon.stub();
				opts.query({
					term: 'ab',
					callback: callback
				});
				expect(fetchStub.calledOnce).toEqual(true);

				fetchStub.yieldTo('success', view.collection);

				expect(callback.calledOnce).toEqual(true);
				expect(callback.getCall(0).args[0].results).toEqual([
					{
						id: '1',
						name: 'abc',
						userVisible: true,
						userAssignable: true,
						canAssign: true
					},
					{
						id: '5',
						name: 'abe',
						userVisible: true,
						userAssignable: false,
						canAssign: true
					}
				]);
			});
			it('completes case insensitive', function() {
				var callback = sinon.stub();
				opts.query({
					term: 'de',
					callback: callback
				});
				expect(fetchStub.calledOnce).toEqual(true);

				fetchStub.yieldTo('success', view.collection);

				expect(callback.calledOnce).toEqual(true);
				expect(callback.getCall(0).args[0].results).toEqual([
					{
						id: '2',
						name: 'def',
						userVisible: true,
						userAssignable: true,
						canAssign: true
					},
					{
						id: '4',
						name: 'Deg',
						userVisible: true,
						userAssignable: true,
						canAssign: true
					}
				]);
			});
		});
		describe('tag actions', function() {
			var opts;

			beforeEach(function() {

				opts = select2Stub.getCall(0).args[0];

				view.collection.add([
					new OC.SystemTags.SystemTagModel({id: '1', name: 'abc'}),
				]);

				$dropdown.append(opts.formatResult(view.collection.get('1').toJSON()));

			});
			it('deletes model and submits change when clicking delete', function() {
				var destroyStub = sinon.stub(OC.SystemTags.SystemTagModel.prototype, 'destroy');

				expect($dropdown.find('.delete').length).toEqual(0);
				$dropdown.find('.rename').mouseup();
				// delete button appears only for admins
				expect($dropdown.find('.delete').length).toEqual(0);
				$dropdown.find('.delete').mouseup();

				expect(destroyStub.notCalled).toEqual(true);

				destroyStub.restore();
			});
		});
	});
});
