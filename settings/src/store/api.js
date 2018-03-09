import axios from 'axios';

const requestToken = document.getElementsByTagName('head')[0].getAttribute('data-requesttoken');
const tokenHeaders = { headers: { requesttoken: requestToken } };

const sanitize = function(url) {
    return url.replace(/\/$/, ''); // Remove last slash of url
}

export default {
    requireAdmin() {
        return new Promise(function(resolve, reject) {
            setTimeout(reject, 5000); // automatically reject 5s if not ok
            function waitForpassword() {
                if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
                    setTimeout(waitForpassword, 500);
                    return;
                }
                resolve();
            }
            waitForpassword();
            OC.PasswordConfirmation.requirePasswordConfirmation();
        }).catch((error) => console.log('Required password not entered'));
    },
    get(url) {
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
        return axios.patch(sanitize(url), { data: data, headers: tokenHeaders.headers })
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