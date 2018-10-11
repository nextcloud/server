const {danger, fail, markdown, message, warn} = require('danger');
const {readFileSync} = require('fs');
const includes = require('lodash.includes');
const minimatch = require('minimatch');

function getImagePath(suite, test, type) {
	return 'https://ci-assets.nextcloud.com/nextcloud-ui-regression/nextcloud/server/' + 
		process.env.DRONE_PULL_REQUEST  + '/' + 
		suite + '/' + test.title + type + '.png';
}

function runDangerForSuite(suite) {
	const currentBuildResults = JSON.parse(
		readFileSync(`./out/${suite}.json`)
	);

	let failingTests = currentBuildResults.failures;
	if (failingTests.length > 0) {
		warn(`There are some ui comparison tests failing. Please have a look at ` +
		     `https://ci-assets.nextcloud.com/nextcloud-ui-regression/nextcloud/server/${danger.github.pr.number}/index.html to check if your PR introduces an UI regression.`)
	}


	failingTests.forEach(test => {
		const url = 'https://ci-assets.nextcloud.com/nextcloud-ui-regression/nextcloud/server/10507/index.html#' + 
			test.fullTitle.replace(/\W/g, '_');
		const jsonData = JSON.stringify(test, null, 2);

		const base = getImagePath(suite, test, '.base');
		const diff = getImagePath(suite, test, '.diff');
		const change = getImagePath(suite, test, '.change');
		warn(`[${test.fullTitle}](${url}) has visual differences
		
<details>
  <summary>Test details</summary>

<table><tr>
<td><img src="${base}" /></td>
<td><img src="${diff}" /></td>
<td><img src="${change}" /></td>
</tr></table>

\`\`\`
${jsonData}
\`\`\`

</details>

`);

	})
}

runDangerForSuite('install');
//runDangerForSuite('login');
//runDangerForSuite('files');
//runDangerForSuite('public');
//runDangerForSuite('settings');
//runDangerForSuite('apps');
