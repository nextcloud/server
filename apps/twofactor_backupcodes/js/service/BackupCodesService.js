import axio from 'axios';

export function getState () {
	const url = OC.generateUrl('/apps/twofactor_backupcodes/settings/state');

	return axio.get(url, {
		headers: {
			requesttoken: OC.requestToken
		}
	}).then(resp => resp.data);
}

export function generateCodes () {
	const url = OC.generateUrl('/apps/twofactor_backupcodes/settings/create');

	return axio.post(url, {}, {
		headers: {
			requesttoken: OC.requestToken
		}
	}).then(resp => resp.data)
}
