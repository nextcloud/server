/**
 * SPDX-FileCopyrightText: 2016 ownCloud Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
