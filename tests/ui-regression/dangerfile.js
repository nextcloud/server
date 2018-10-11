const {danger, fail, markdown, message, warn} = require('danger');
const {readFileSync} = require('fs');
const includes = require('lodash.includes');
const minimatch = require('minimatch');

// Now do work against our lists of files
const currentBuildResults = JSON.parse(
	readFileSync('./out/install.json')
);

let failingTests = currentBuildResults.failures;
if (failingTests.length > 0) {
	warn(`There are some ui comparison tests failing. Please have a look at ` +
	     `https://ci-assets.nextcloud.com/nextcloud-ui-regression/nextcloud/server/${danger.github.pr.number}/index.html to check if your PR introduces an UI regression.`)
}

function getImagePath(test, type) {
	return 'https://ci-assets.nextcloud.com/nextcloud-ui-regression/nextcloud/server/' + 
		process.env.DRONE_PULL_REQUEST  + '/' + 
		test.suite + '/' + test.title + type + '.png';
}

failingTests.forEach(test => {
	const url = 'https://ci-assets.nextcloud.com/nextcloud-ui-regression/nextcloud/server/10507/index.html#' + 
		test.fullTitle.replace(/\W/g, '_');
	const jsonData = JSON.stringify(test, null, 2);

	const base = getImagePath(test, '.base');
	const diff = getImagePath(test, '.diff');
	const change = getImagePath(test, '.change');
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
</details>`);

})
