/**
 * @copyright 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

const fs = require('fs')
const Mocha = require('mocha')

const testFolder = './test/'


var tests = [
	'install',
	'login',
	'files',
	'public',
	'settings',
	'apps',
]

var args = process.argv.slice(2);
if (args.length > 0) {
	tests = args
}

var config = {
	tests: tests,
	pr: process.env.DRONE_PULL_REQUEST,
	repoUrl: process.env.DRONE_REPO_LINK,
};

console.log('=> Write test config');
console.log(config);
fs.writeFile('out/config.json', JSON.stringify(config), 'utf8', () => {});

var mocha = new Mocha({
	timeout: 60000
});
let result = {};

tests.forEach(async function (test) {
	mocha.addFile('./test/' + test + 'Spec.js')
	result[test] = {
		failures: [],
		passes: [],
		tests: [],
		pending: [],
		stats: {}
	}

});

// fixme fail if installation failed
// write json to file

function clean (test) {
	return {
		title: test.title,
		fullTitle: test.fullTitle(),
		duration: test.duration,
		currentRetry: test.currentRetry(),
		failedAction: test.failedAction,
		err: errorJSON(test.err || {})
	};
}

function errorJSON (err) {
	var res = {};
	Object.getOwnPropertyNames(err).forEach(function (key) {
		res[key] = err[key];
	}, err);
	return res;
}

function updateGithubStatus(postData) {
	if (!process.env.GITHUB_TOKEN) {
		console.error('No GITHUB_TOKEN provided')
		return
	}
	const http = require('https');
	var options = {
		host: 'api.github.com',
		port: 443,
		path: '/repos/nextcloud/server/statuses/' + process.env.DRONE_COMMIT_SHA,
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'User-Agent': 'ui regression reporter',
			'Authorization': 'token ' + process.env.GITHUB_TOKEN
		}
	};

	const req = http.request(options, function(res) {
		res.setEncoding('utf8');
		res.on('data', function (chunk) {
			console.log('BODY: ' + chunk);
		});
	});
	req.end(JSON.stringify(postData));
}

const postData = {
	"state": "pending",
	"target_url": process.env.DRONE_BUILD_LINK,
	"description": "UI regression tests pending",
	"context": "continuous-integration/ui-regression"
};
updateGithubStatus(postData)

mocha.run()
	.on('test', function (test) {
	})
	.on('suite end', function(suite) {
		if (result[suite.title] === undefined)
			return;
		result[suite.title].stats = suite.stats;
	})
	.on('test end', function (test) {
		result[test.parent.title].tests.push(test);
	})
	.on('pass', function (test) {
		result[test.parent.title].passes.push(test);
	})
	.on('fail', function (test) {
		result[test.parent.title].failures.push(test);
	})
	.on('pending', function (test) {
		result[test.parent.title].pending.push(test);
	})
	.on('end', function () {
		let failures = 0;
		for (let test of tests) {
			var json = JSON.stringify({
				stats: result[test].stats,
				tests: result[test].tests.map(clean),
				pending: result[test].pending.map(clean),
				failures: result[test].failures.map(clean),
				passes: result[test].passes.map(clean)
			}, null, 2);
			fs.writeFile(`out/${test}.json`, json, 'utf8', function () {
				console.log(`Written test result to out/${test}.json`)
			});
			failures += result[test].failures.length;
		}

		if (process.env.GITHUB_TOKEN) {
			console.log('Publish test status to github')
			let url = `https://ci-assets.nextcloud.com/nextcloud-ui-regression/nextcloud/server/${process.env.DRONE_PULL_REQUEST}/index.html`;
			let status = failures > 0 ? 'failure' : 'success';
			let description = failures > 0 ? failures + ' possible UI regressions found' : 'UI regression tests passed';
			const postData = {
				"state": status,
				"target_url": url,
				"description": description,
				"context": "continuous-integration/ui-regression"
			};
			updateGithubStatus(postData)
		}
	});

