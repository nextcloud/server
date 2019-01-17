/**
 *
 */
import loader from './loader'

/** @namespace OCP */
const OCP = {
	Loader: loader,
};

window['OCP'] = Object.assign({}, window.OCP, OCP)
