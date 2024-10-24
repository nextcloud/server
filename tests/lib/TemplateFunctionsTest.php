<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

class TemplateFunctionsTest extends \Test\TestCase {
	protected function setUp(): void {
		parent::setUp();

		require_once \OC::$SERVERROOT . '/lib/private/legacy/OC_Template.php';
	}

	public function testPJavaScript(): void {
		$this->expectOutputString('&lt;img onload=&quot;alert(1)&quot; /&gt;');
		p('<img onload="alert(1)" />');
	}

	public function testPJavaScriptWithScriptTags(): void {
		$this->expectOutputString('&lt;script&gt;alert(&#039;Hacked!&#039;);&lt;/script&gt;');
		p("<script>alert('Hacked!');</script>");
	}

	public function testPNormalString(): void {
		$string = 'This is a good string without HTML.';
		$this->expectOutputString($string);
		p($string);
	}

	public function testPrintUnescaped(): void {
		$htmlString = "<script>alert('xss');</script>";
		$this->expectOutputString($htmlString);
		print_unescaped($htmlString);
	}

	public function testPrintUnescapedNormalString(): void {
		$string = 'This is a good string!';
		$this->expectOutputString($string);
		print_unescaped($string);
	}

	public function testEmitScriptTagWithContent(): void {
		$this->expectOutputRegex('/<script nonce="[^"]+">\nalert\(\)\n<\/script>\n?/');
		emit_script_tag('', 'alert()');
	}

	public function testEmitScriptTagWithSource(): void {
		$this->expectOutputRegex('/<script nonce=".*" defer src="some.js"><\/script>/');
		emit_script_tag('some.js');
	}

	public function testEmitScriptTagWithModuleSource(): void {
		$this->expectOutputRegex('/<script nonce=".*" defer src="some.mjs" type="module"><\/script>/');
		emit_script_tag('some.mjs', '', 'module');
	}

	public function testEmitScriptLoadingTags(): void {
		// Test mjs js and inline content
		$pattern = '/src="some\.mjs"[^>]+type="module"[^>]*>.+\n'; // some.mjs with type = module
		$pattern .= '<script[^>]+src="other\.js"[^>]*>.+\n'; // other.js as plain javascript
		$pattern .= '<script[^>]*>\n?.*inline.*\n?<\/script>'; // inline content
		$pattern .= '/'; // no flags

		$this->expectOutputRegex($pattern);
		emit_script_loading_tags([
			'jsfiles' => ['some.mjs', 'other.js'],
			'inline_ocjs' => '// inline'
		]);
	}

	public function testEmitScriptLoadingTagsWithVersion(): void {
		// Test mjs js and inline content
		$pattern = '/src="some\.mjs\?v=ab123cd"[^>]+type="module"[^>]*>.+\n'; // some.mjs with type = module
		$pattern .= '<script[^>]+src="other\.js\?v=12abc34"[^>]*>.+\n'; // other.js as plain javascript
		$pattern .= '/'; // no flags

		$this->expectOutputRegex($pattern);
		emit_script_loading_tags([
			'jsfiles' => ['some.mjs?v=ab123cd', 'other.js?v=12abc34'],
		]);
	}

	// ---------------------------------------------------------------------------
	// Test relative_modified_date with dates only
	// ---------------------------------------------------------------------------
	public function testRelativeDateToday(): void {
		$currentTime = 1380703592;
		$elementTime = $currentTime;
		$result = (string)relative_modified_date($elementTime, $currentTime, true);

		$this->assertEquals('today', $result);

		// 2 hours ago is still today
		$elementTime = $currentTime - 2 * 3600;
		$result = (string)relative_modified_date($elementTime, $currentTime, true);

		$this->assertEquals('today', $result);
	}

	public function testRelativeDateYesterday(): void {
		$currentTime = 1380703592;
		$elementTime = $currentTime - 24 * 3600;
		$result = (string)relative_modified_date($elementTime, $currentTime, true);

		$this->assertEquals('yesterday', $result);

		// yesterday - 2 hours is still yesterday
		$elementTime = $currentTime - 26 * 3600;
		$result = (string)relative_modified_date($elementTime, $currentTime, true);

		$this->assertEquals('yesterday', $result);
	}

	public function testRelativeDate2DaysAgo(): void {
		$currentTime = 1380703592;
		$elementTime = $currentTime - 48 * 3600;
		$result = (string)relative_modified_date($elementTime, $currentTime, true);

		$this->assertEquals('2 days ago', $result);

		// 2 days ago minus 4 hours is still 2 days ago
		$elementTime = $currentTime - 52 * 3600;
		$result = (string)relative_modified_date($elementTime, $currentTime, true);

		$this->assertEquals('2 days ago', $result);
	}

	public function testRelativeDateLastMonth(): void {
		$currentTime = 1380703592;
		$elementTime = $currentTime - 86400 * 31;
		$result = (string)relative_modified_date($elementTime, $currentTime, true);

		$this->assertEquals('last month', $result);

		$elementTime = $currentTime - 86400 * 35;
		$result = (string)relative_modified_date($elementTime, $currentTime, true);

		$this->assertEquals('last month', $result);
	}

	public function testRelativeDateMonthsAgo(): void {
		$currentTime = 1380703592;
		$elementTime = $currentTime - 86400 * 65;
		$result = (string)relative_modified_date($elementTime, $currentTime, true);

		$this->assertEquals('2 months ago', $result);

		$elementTime = $currentTime - 86400 * 130;
		$result = (string)relative_modified_date($elementTime, $currentTime, true);

		$this->assertEquals('4 months ago', $result);
	}

	public function testRelativeDateLastYear(): void {
		$currentTime = 1380703592;
		$elementTime = $currentTime - 86400 * 365;
		$result = (string)relative_modified_date($elementTime, $currentTime, true);

		$this->assertEquals('last year', $result);

		$elementTime = $currentTime - 86400 * 450;
		$result = (string)relative_modified_date($elementTime, $currentTime, true);

		$this->assertEquals('last year', $result);
	}

	public function testRelativeDateYearsAgo(): void {
		$currentTime = 1380703592;
		$elementTime = $currentTime - 86400 * 365.25 * 2;
		$result = (string)relative_modified_date($elementTime, $currentTime, true);

		$this->assertEquals('2 years ago', $result);

		$elementTime = $currentTime - 86400 * 365.25 * 3;
		$result = (string)relative_modified_date($elementTime, $currentTime, true);

		$this->assertEquals('3 years ago', $result);
	}

	// ---------------------------------------------------------------------------
	// Test relative_modified_date with timestamps only (date + time value)
	// ---------------------------------------------------------------------------

	public function testRelativeTimeSecondsAgo(): void {
		$currentTime = 1380703592;
		$elementTime = $currentTime - 5;
		$result = (string)relative_modified_date($elementTime, $currentTime, false);

		$this->assertEquals('seconds ago', $result);
	}

	public function testRelativeTimeMinutesAgo(): void {
		$currentTime = 1380703592;
		$elementTime = $currentTime - 190;
		$result = (string)relative_modified_date($elementTime, $currentTime, false);

		$this->assertEquals('3 minutes ago', $result);
	}

	public function testRelativeTimeHoursAgo(): void {
		$currentTime = 1380703592;
		$elementTime = $currentTime - 7500;
		$result = (string)relative_modified_date($elementTime, $currentTime, false);

		$this->assertEquals('2 hours ago', $result);
	}

	public function testRelativeTime2DaysAgo(): void {
		$currentTime = 1380703592;
		$elementTime = $currentTime - 48 * 3600;
		$result = (string)relative_modified_date($elementTime, $currentTime, false);

		$this->assertEquals('2 days ago', $result);

		// 2 days ago minus 4 hours is still 2 days ago
		$elementTime = $currentTime - 52 * 3600;
		$result = (string)relative_modified_date($elementTime, $currentTime, false);

		$this->assertEquals('2 days ago', $result);
	}

	public function testRelativeTimeLastMonth(): void {
		$currentTime = 1380703592;
		$elementTime = $currentTime - 86400 * 31;
		$result = (string)relative_modified_date($elementTime, $currentTime, false);

		$this->assertEquals('last month', $result);

		$elementTime = $currentTime - 86400 * 35;
		$result = (string)relative_modified_date($elementTime, $currentTime, false);

		$this->assertEquals('last month', $result);
	}

	public function testRelativeTimeMonthsAgo(): void {
		$currentTime = 1380703592;
		$elementTime = $currentTime - 86400 * 65;
		$result = (string)relative_modified_date($elementTime, $currentTime, false);

		$this->assertEquals('2 months ago', $result);

		$elementTime = $currentTime - 86400 * 130;
		$result = (string)relative_modified_date($elementTime, $currentTime, false);

		$this->assertEquals('4 months ago', $result);
	}

	public function testRelativeTimeLastYear(): void {
		$currentTime = 1380703592;
		$elementTime = $currentTime - 86400 * 365;
		$result = (string)relative_modified_date($elementTime, $currentTime, false);

		$this->assertEquals('last year', $result);

		$elementTime = $currentTime - 86400 * 450;
		$result = (string)relative_modified_date($elementTime, $currentTime, false);

		$this->assertEquals('last year', $result);
	}

	public function testRelativeTimeYearsAgo(): void {
		$currentTime = 1380703592;
		$elementTime = $currentTime - 86400 * 365.25 * 2;
		$result = (string)relative_modified_date($elementTime, $currentTime, false);

		$this->assertEquals('2 years ago', $result);

		$elementTime = $currentTime - 86400 * 365.25 * 3;
		$result = (string)relative_modified_date($elementTime, $currentTime, false);

		$this->assertEquals('3 years ago', $result);
	}
}
