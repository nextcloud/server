import axios from 'axios';

const requestToken = document.getElementsByTagName('head')[0].getAttribute('data-requesttoken');
const tokenHeaders = { headers: { requesttoken: requestToken } };

const sanitize = function(url) {
	return url.replace(/\/$/, ''); // Remove last url slash
};

export default {

	/**
	 * This Promise is used to chain a request that require an admin password confirmation
	 * Since chaining Promise have a very precise behavior concerning catch and then,
	 * you'll need to be careful when using it.
	 * e.g
	 * // store
	 * 	action(context) {
	 *		return api.requireAdmin().then((response) => {
	 *			return api.get('url')
	 *				.then((response) => {API success})
	 *				.catch((error) => {API failure});
	 *		}).catch((error) => {requireAdmin failure});
	 *	}
	 * // vue
	 *	this.$store.dispatch('action').then(() => {always executed})
	 *
	 * Since Promise.then().catch().then() will always execute the last then
	 * this.$store.dispatch('action').then will always be executed
	 * 
	 * If you want requireAdmin failure to also catch the API request failure
	 * you will need to throw a new error in the api.get.catch()
	 * 
	 * e.g
	 *	api.requireAdmin().then((response) => {
	 *		api.get('url')
	 *			.then((response) => {API success})
	 *			.catch((error) => {throw error;});
	 *	}).catch((error) => {requireAdmin OR API failure});
	 * 
	 * @returns {Promise}
	 */
	requireAdmin() {
		return new Promise(function(resolve, reject) {
			// TODO: migrate the OC.dialog to Vue and avoid this mess
			// wait for password confirmation
			let passwordTimeout;
			let waitForpassword = function() {
				if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
					passwordTimeout = setTimeout(waitForpassword, 500);
					return;
				}
				clearTimeout(passwordTimeout);
				clearTimeout(promiseTimeout);
				resolve();
			};

			// automatically reject after 5s if not resolved
			let promiseTimeout = setTimeout(() => {
				clearTimeout(passwordTimeout);
				// close dialog
				if (document.getElementsByClassName('oc-dialog-close').length>0) {
					document.getElementsByClassName('oc-dialog-close')[0].click();
				}
				OC.Notification.showTemporary(t('settings', 'You did not enter the password in time'));
				reject('Password request cancelled');
			}, 7000); 

			// request password
			OC.PasswordConfirmation.requirePasswordConfirmation();
			waitForpassword();
		});
	},
	get(url, data) {
		return axios.get(sanitize(url), tokenHeaders)
			.then((response) => Promise.resolve(response))
			.catch((error) => Promise.reject(error));
	},
	post(url, data) {
		return axios.post(sanitize(url), data, tokenHeaders)
			.then((response) => Promise.resolve(response))
			.catch((error) => Promise.reject(error));
	},
	patch(url, data) {
		return axios.patch(sanitize(url), data, tokenHeaders)
			.then((response) => Promise.resolve(response))
			.catch((error) => Promise.reject(error));
	},
	put(url, data) {
		return axios.put(sanitize(url), data, tokenHeaders)
			.then((response) => Promise.resolve(response))
			.catch((error) => Promise.reject(error));
	},
	delete(url, data) {
		return axios.delete(sanitize(url), { data: data, headers: tokenHeaders.headers })
			.then((response) => Promise.resolve(response))
			.catch((error) => Promise.reject(error));
	}
};