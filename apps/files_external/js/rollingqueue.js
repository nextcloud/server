/**
 * ownCloud
 *
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @author Jesus Macias Portela <jesus@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function(){
/**
 * Launch several functions at thee same time. The number of functions
 * running at the same time is controlled by the queueWindow param
 *
 * The function list come in the following format:
 *
 * var flist = [
 *   {
 *       funcName: function () {
 *             var d = $.Deferred();
 *             setTimeout(function(){d.resolve();}, 1000);
 *             return d;
 *       }
 *   },
 *   {
 *       funcName: $.get,
 *       funcArgs: [
 *                  OC.filePath('files_external', 'ajax', 'connectivityCheck.php'),
 *                  {},
 *                  function () {
 *                      console.log('titoooo');
 *                  }
 *                 ]
 *   },
 *   {
 *       funcName: $.get,
 *       funcArgs: [
 *                  OC.filePath('files_external', 'ajax', 'connectivityCheck.php')
 *                 ],
 *       done: function () {
 *             console.log('yuupi');
 *       },
 *       always: function () {
 *             console.log('always done');
 *       }
 *   }
 *];
 *
 * functions MUST implement the deferred interface
 *
 * @param functionList list of functions that the queue will run
 * (check example above for the expected format)
 * @param queueWindow specify the number of functions that will
 * be executed at the same time
 */
var RollingQueue = function (functionList, queueWindow, callback) {
	this.queueWindow = queueWindow || 1;
	this.functionList = functionList;
	this.callback = callback;
	this.counter = 0;
	this.runQueue = function() {
		this.callbackCalled = false;
		this.deferredsList = [];
		if (!$.isArray(this.functionList)) {
			throw "functionList must be an array";
		}

		for (var i = 0; i < this.queueWindow; i++) {
			this.launchNext();
		}
	};

	this.hasNext = function() {
		return (this.counter in this.functionList);
	};

	this.launchNext = function() {
		var currentCounter = this.counter++;
		if (currentCounter in this.functionList) {
			var funcData = this.functionList[currentCounter];
			if ($.isFunction(funcData.funcName)) {
				var defObj = funcData.funcName.apply(funcData.funcName, funcData.funcArgs);
				this.deferredsList.push(defObj);
				if ($.isFunction(funcData.done)) {
					defObj.done(funcData.done);
				}

				if ($.isFunction(funcData.fail)) {
					defObj.fail(funcData.fail);
				}

				if ($.isFunction(funcData.always)) {
					defObj.always(funcData.always);
				}

				if (this.hasNext()) {
					var self = this;
					defObj.always(function(){
							_.defer($.proxy(function(){
								self.launchNext();
						}, self));
					});
				} else {
					if (!this.callbackCalled) {
						this.callbackCalled = true;
						if ($.isFunction(this.callback)) {
							$.when.apply($, this.deferredsList)
								.always($.proxy(function(){
									this.callback();
								}, this)
							);
						}
					}
				}
				return defObj;
			}
		}
		return false;
	};
};

if (!OCA.External) {
	OCA.External = {};
}

if (!OCA.External.StatusManager) {
	OCA.External.StatusManager = {};
}

OCA.External.StatusManager.RollingQueue = RollingQueue;

})();
