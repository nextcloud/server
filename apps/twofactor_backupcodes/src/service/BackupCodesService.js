import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export function generateCodes() {
	const url = generateUrl('/apps/twofactor_backupcodes/settings/create')

	return Axios.post(url, {}).then(resp => resp.data)
}
