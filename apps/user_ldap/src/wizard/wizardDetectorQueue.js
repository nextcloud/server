
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {
	/**
	 * @classdesc only run detector is allowed to run at a time. Basically
	 * because we cannot have parallel LDAP connections per session. This
	 * queue is takes care of running all the detectors one after the other.
	 *
	 * @constructor
	 */
	const WizardDetectorQueue = OCA.LDAP.Wizard.WizardObject.subClass({
		/**
		 * initializes the instance. Always call it after creating the instance.
		 */
		init() {
			this.queue = []
			this.isRunning = false
		},

		/**
		 * empties the queue and cancels a possibly running request
		 */
		reset() {
			this.queue = []
			if (!_.isUndefined(this.runningRequest)) {
				this.runningRequest.abort()
				delete this.runningRequest
			}
			this.isRunning = false
		},

		/**
		 * a parameter-free callback that eventually executes the run method of
		 * the detector.
		 *
		 * @callback detectorCallBack
		 * @see OCA.LDAP.Wizard.ConfigModel._processSetResult
		 */

		/**
		 * adds a detector to the queue and attempts to trigger to run the
		 * next job, because it might be the first.
		 *
		 * @param {detectorCallBack} callback
		 */
		add(callback) {
			this.queue.push(callback)
			this.next()
		},

		/**
		 * Executes the next detector if none is running. This method is also
		 * automatically invoked after a detector finished.
		 */
		next() {
			if (this.isRunning === true || this.queue.length === 0) {
				return
			}

			this.isRunning = true
			const callback = this.queue.shift()
			const request = callback()

			// we receive either false or a jqXHR object
			// false in case the detector decided against executing
			if (request === false) {
				this.isRunning = false
				this.next()
				return
			}
			this.runningRequest = request

			const detectorQueue = this
			$.when(request).then(function() {
				detectorQueue.isRunning = false
				detectorQueue.next()
			})
		},
	})

	OCA.LDAP.Wizard.WizardDetectorQueue = WizardDetectorQueue
})()
