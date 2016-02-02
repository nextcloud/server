/*
 * Copyright (c) 2016
 *
 * This file is licensed under the Affero General Public License comment 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
describe('OCA.Comments.CommentCollection', function() {
	var CommentCollection = OCA.Comments.CommentCollection;
	var collection, syncStub;
	var comment1, comment2, comment3;

	beforeEach(function() {
		syncStub = sinon.stub(CommentCollection.prototype, 'sync');
		collection = new CommentCollection();
		collection.setObjectId(5);

		comment1 = {
			id: 1,
			actorType: 'users',
			actorId: 'user1',
			actorDisplayName: 'User One',
			objectType: 'files',
			objectId: 5,
			message: 'First',
			creationDateTime: Date.UTC(2016, 1, 3, 10, 5, 0)
		};
		comment2 = {
			id: 2,
			actorType: 'users',
			actorId: 'user2',
			actorDisplayName: 'User Two',
			objectType: 'files',
			objectId: 5,
			message: 'Second\nNewline',
			creationDateTime: Date.UTC(2016, 1, 3, 10, 0, 0)
		};
		comment3 = {
			id: 3,
			actorType: 'users',
			actorId: 'user3',
			actorDisplayName: 'User Three',
			objectType: 'files',
			objectId: 5,
			message: 'Third',
			creationDateTime: Date.UTC(2016, 1, 3, 5, 0, 0)
		};
	});
	afterEach(function() { 
		syncStub.restore(); 
	});

	it('fetches the next page', function() {
		collection._limit = 2;
		collection.fetchNext();

		expect(syncStub.calledOnce).toEqual(true);
		expect(syncStub.lastCall.args[0]).toEqual('REPORT');
		var options = syncStub.lastCall.args[2];
		expect(options.remove).toEqual(false);

        var parser = new DOMParser();
        var doc = parser.parseFromString(options.data, "application/xml");
		expect(doc.getElementsByTagNameNS('http://owncloud.org/ns', 'limit')[0].textContent).toEqual('3');
		expect(doc.getElementsByTagNameNS('http://owncloud.org/ns', 'offset')[0].textContent).toEqual('0');

		syncStub.yieldTo('success', [comment1, comment2, comment3]);

		expect(collection.length).toEqual(2);
		expect(collection.hasMoreResults()).toEqual(true);

		collection.fetchNext();

		expect(syncStub.calledTwice).toEqual(true);
		options = syncStub.lastCall.args[2];
        doc = parser.parseFromString(options.data, "application/xml");
		expect(doc.getElementsByTagNameNS('http://owncloud.org/ns', 'limit')[0].textContent).toEqual('3');
		expect(doc.getElementsByTagNameNS('http://owncloud.org/ns', 'offset')[0].textContent).toEqual('2');

		syncStub.yieldTo('success', [comment3]);

		expect(collection.length).toEqual(3);
		expect(collection.hasMoreResults()).toEqual(false);

		collection.fetchNext();

		// no further requests
		expect(syncStub.calledTwice).toEqual(true);
	});
	it('resets page counted when calling reset', function() {
		collection.fetchNext();

		syncStub.yieldTo('success', [comment1]);

		expect(collection.hasMoreResults()).toEqual(false);

		collection.reset();

		expect(collection.hasMoreResults()).toEqual(true);
	});
});

