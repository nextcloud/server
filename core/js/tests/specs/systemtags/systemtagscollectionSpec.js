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

describe('OC.SystemTags.SystemTagsCollection tests', function() {
	var collection;

	beforeEach(function() {
		collection = new OC.SystemTags.SystemTagsCollection();
	});
	it('fetches only once, until reset', function() {
		var syncStub = sinon.stub(collection, 'sync');
		var callback = sinon.stub();
		var callback2 = sinon.stub();
		var callback3 = sinon.stub();
		var eventHandler = sinon.stub();

		collection.on('sync', eventHandler);

		collection.fetch({
			success: callback
		});

		expect(callback.notCalled).toEqual(true);
		expect(syncStub.calledOnce).toEqual(true);
		expect(eventHandler.notCalled).toEqual(true);

		syncStub.yieldTo('success', collection);

		expect(callback.calledOnce).toEqual(true);
		expect(callback.firstCall.args[0]).toEqual(collection);
		expect(eventHandler.calledOnce).toEqual(true);
		expect(eventHandler.firstCall.args[0]).toEqual(collection);

		collection.fetch({
			success: callback2
		});

		expect(eventHandler.calledTwice).toEqual(true);
		expect(eventHandler.secondCall.args[0]).toEqual(collection);

		// not re-called
		expect(syncStub.calledOnce).toEqual(true);

		expect(callback.calledOnce).toEqual(true);
		expect(callback2.calledOnce).toEqual(true);
		expect(callback2.firstCall.args[0]).toEqual(collection);

		expect(collection.fetched).toEqual(true);

		collection.reset();

		expect(collection.fetched).toEqual(false);

		collection.fetch({
			success: callback3
		});

		expect(syncStub.calledTwice).toEqual(true);

		syncStub.yieldTo('success', collection);
		expect(callback3.calledOnce).toEqual(true);
		expect(callback3.firstCall.args[0]).toEqual(collection);

		syncStub.restore();
	});
});
