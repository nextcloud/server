import Axios from 'nextcloud-axios'

export function getState () {
	const url = OC.generateUrl('/apps/twofactor_backupcodes/settings/state');

	return Axios.get(url).then(resp => resp.data);
}

export function generateCodes () {
	const url = OC.generateUrl('/apps/twofactor_backupcodes/settings/create');

	return Axios.post(url, {}).then(resp => resp.data)
}
