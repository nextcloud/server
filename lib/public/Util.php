<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP;

use bantu\IniGetWrapper\IniGetWrapper;
use OC\AppScriptDependency;
use OC\AppScriptSort;
use OC\Security\CSRF\CsrfTokenManager;
use OCP\L10N\IFactory;
use OCP\Mail\IEmailValidator;
use Psr\Container\ContainerExceptionInterface;

/**
 * This class provides different helper functions to make the life of a developer easier
 *
 * @since 4.0.0
 */
class Util {
	/**
	 * List of early init script asset paths.
	 *
	 * @psalm-suppress ImpureStaticProperty
	 */
	private static array $scriptsInit = [];

	/**
	 * Script asset paths grouped by application ID.
	 *
	 * @psalm-suppress ImpureStaticProperty
	 */
	private static array $scripts = [];
	
	/**
	 * App-level script dependency metadata keyed by application ID.
	 *
	 * @psalm-suppress ImpureStaticProperty
	 */
	private static array $scriptDeps = [];

	/** @psalm-suppress ImpureStaticProperty */
	private static ?bool $needUpgradeCache = null;

	/**
	 * get the current installed version of Nextcloud
	 * @since 4.0.0
	 * @deprecated 31.0.0 Use \OCP\ServerVersion::getVersion
	 */
	public static function getVersion(): array {
		return Server::get(ServerVersion::class)->getVersion();
	}

	/**
	 * @since 17.0.0
	 */
	public static function hasExtendedSupport(): bool {
		try {
			/** @var \OCP\Support\Subscription\IRegistry */
			$subscriptionRegistry = Server::get(\OCP\Support\Subscription\IRegistry::class);
			return $subscriptionRegistry->delegateHasExtendedSupport();
		} catch (ContainerExceptionInterface $e) {
		}
		return Server::get(IConfig::class)->getSystemValueBool('extendedSupport', false);
	}

	/**
	 * Set current update channel
	 * @since 8.1.0
	 * @deprecated 33.0.0 Use \OCP\ServerVersion::setChannel
	 */
	public static function setChannel(string $channel): void {
		Server::get(IConfig::class)->setSystemValue('updater.release.channel', $channel);
	}

	/**
	 * Get current update channel
	 * @since 8.1.0
	 * @deprecated 31.0.0 Use \OCP\ServerVersion::getChannel
	 */
	public static function getChannel(): string {
		return Server::get(ServerVersion::class)->getChannel();
	}

	/**
	 * get l10n object
	 * @since 6.0.0
	 * @since 8.0.0 parameter $language was added
	 */
	public static function getL10N(string $application, ?string $language = null): IL10N {
		return Server::get(\OCP\L10N\IFactory::class)->get($application, $language);
	}

	/**
	 * Add a css file
	 *
	 * @param string $application application id
	 * @param ?string $file filename
	 * @param bool $prepend prepend the style to the beginning of the list
	 * @since 4.0.0
	 */
	public static function addStyle(string $application, ?string $file = null, bool $prepend = false): void {
		\OC_Util::addStyle($application, $file, $prepend);
	}

	/**
	 * Add an initialization JavaScript asset that should be emitted before
	 * regular app scripts.
	 *
	 * These scripts are loaded very early and can block initial page rendering.
	 * They should therefore stay small and only rely on assets that are guaranteed
	 * to be available at that stage, namely core/js/common and core/js/main.
	 *
	 * For non-core apps, the matching translation asset is added first so that
	 * translations are available when the init script executes.
	 *
	 * @since 28.0.0
	 */
	public static function addInitScript(string $application, string $file): void {
		if (!empty($application)) {
			$scriptPath = "$application/js/$file";
		} else {
			$scriptPath = "js/$file";
		}

		// Init scripts may access translations immediately on execution, so for
		// non-core apps load the translation asset before the init script itself.
		if ($application !== 'core' && !str_contains($file, 'l10n')) {
			self::addTranslations($application, null, true);
		}

		self::$scriptsInit[] = $scriptPath;
	}

	/**
	 * Add a JavaScript asset for an app.
	 *
	 * Scripts are grouped by app and app-level dependencies are tracked in
	 * self::$scriptDeps. The dependency sorter uses that information later when
	 * building the final script list.
	 *
	 * For non-core apps, the matching translation asset is added automatically
	 * unless the requested file already represents a translation asset.
	 *
	 * @param string $application Application ID. Use an empty string for unscoped assets.
	 * @param string $file JavaScript file name relative to the app's js/ directory.
	 * @param string $afterAppId App ID that should be ordered before this app's scripts.
	 * @param bool $prepend Whether to insert the script at the beginning of the app's script list.
	 * @since 4.0.0
	 * @since 35.0.0 $file make non-nullable (effectively already was but now enforced)
	 */
	public static function addScript(
		string $application,
		string $file,
		string $afterAppId = 'core',
		bool $prepend = false,
	): void {
		if (!empty($application)) {
			$scriptPath = "$application/js/$file";
		} else {
			$scriptPath = "js/$file";
		}

		// For non-core apps, ensure translations are registered together with the
		// app script unless this is already a translation asset.
		if ($application !== 'core' && !str_contains($file, 'l10n')) {
			self::addTranslations($application);
		}

		// Track app-level ordering dependencies used when building the final script list.
		if (!isset(self::$scriptDeps[$application])) {
			self::$scriptDeps[$application] = new AppScriptDependency($application, [$afterAppId]);
		} else {
			self::$scriptDeps[$application]->addDep($afterAppId);
		}

		if ($prepend) {
			if (!isset(self::$scripts[$application])) {
				self::$scripts[$application] = [];
			}
			array_unshift(self::$scripts[$application], $scriptPath);
		} else {
			self::$scripts[$application][] = $scriptPath;
		}
	}

	/**
	 * Return the final list of JavaScript assets to inject into the page.
	 *
	 * The result is built in four steps:
	 * 1. sort app script groups using app-level dependency information
	 * 2. prepend early init assets
	 * 3. flatten grouped assets into a single list and remove duplicates
	 * 4. apply explicit priority rules for core bootstrap assets
	 *
	 * @return array<int, string>
	 * @since 24.0.0
	 */
	public static function getScripts(): array {
		// Sort app script groups using the registered app-level dependencies.
		$scriptSorter = Server::get(AppScriptSort::class);
		$groupedScripts = $scriptSorter->sort(self::$scripts, self::$scriptDeps);

		// Prepend init assets, flatten the grouped arrays into a single list,
		// then remove duplicate asset paths.
		$groupedScripts = array_merge([self::$scriptsInit], $groupedScripts);
		$scriptList = array_merge(...array_values($groupedScripts));
		$scriptList = array_unique($scriptList);

		// Apply explicit bootstrap ordering for selected core assets.
		usort(
			$scriptList,
			fn (string $leftScript, string $rightScript) =>
				self::scriptPriority($rightScript) <=> self::scriptPriority($leftScript)
		);

		return $scriptList;
	}

	/**
	 * Return a relative priority for a script asset.
	 *
	 * Higher values are sorted earlier. This is used to force critical core
	 * bootstrap assets to the front of the final list.
	 *
	 * @param string $name Script asset path
	 */
	private static function scriptPriority(string $name): int {
		return match($name) {
			'core/js/common' => 3,
			'core/js/main' => 2,
			default => str_starts_with($name, 'core/l10n/')
				? 1 // Core translations must be available immediately after core/js/main.
				: 0, // No explicit priority; ordering is determined elsewhere.
		};
	}

	/**
	 * Add a JavaScript translation asset.
	 *
	 * If no language code is provided, the current language for the given app is
	 * resolved through the localization factory. Translation assets can either be
	 * registered as early init assets or as regular app scripts.
	 *
	 * @param string $application Application ID
	 * @param ?string $languageCode Language code; defaults to the current app language
	 * @param bool $init Whether to register the translation asset as an early init asset
	 * @since 8.0.0
	 */
	public static function addTranslations(string $application, ?string $languageCode = null, bool $init = false): void {
		if (is_null($languageCode)) {
			$languageCode = Server::get(IFactory::class)->findLanguage($application);
		}

		if (!empty($application)) {
			$translationPath = "$application/l10n/$languageCode";
		} else {
			$translationPath = "l10n/$languageCode";
		}

		if (!isset(self::$scripts[$application])) {
			self::$scripts[$application] = [];
		}

		if ($init) {
			self::appendInitScriptPathOnce($translationPath);
		} else {
			self::appendAppScriptPathOnce($application, $translationPath);
		}
	}

	private static function appendInitScriptPathOnce(string $scriptPath): void {
		if (!in_array($scriptPath, self::$scriptsInit, true)) {
			self::$scriptsInit[] = $scriptPath;
		}
	}

	private static function appendAppScriptPathOnce(string $application, string $scriptPath): void {
		if (!isset(self::$scripts[$application])) {
			self::$scripts[$application] = [];
		}

		if (!in_array($scriptPath, self::$scripts[$application], true)) {
			self::$scripts[$application][] = $scriptPath;
		}
	}

	/**
	 * Add a custom element to the header
	 * If $text is null then the element will be written as empty element.
	 * So use "" to get a closing tag.
	 * @param string $tag tag name of the element
	 * @param array $attributes array of attributes for the element
	 * @param ?string $text the text content for the element
	 * @since 4.0.0
	 */
	public static function addHeader(string $tag, array $attributes, ?string $text = null): void {
		\OC_Util::addHeader($tag, $attributes, $text);
	}

	/**
	 * Creates an absolute url to the given app and file.
	 * @param string $app app
	 * @param string $file file
	 * @param array $args array with param=>value, will be appended to the returned url
	 *                    The value of $args will be urlencoded
	 * @return string the url
	 * @since 4.0.0 - parameter $args was added in 4.5.0
	 * @deprecated 34.0.0 Use IUrlGenerator::getAbsoluteUrl and IUrlGenerator::linkTo
	 */
	public static function linkToAbsolute(string $app, string $file, array $args = []): string {
		$urlGenerator = Server::get(IURLGenerator::class);
		return $urlGenerator->getAbsoluteURL(
			$urlGenerator->linkTo($app, $file, $args)
		);
	}

	/**
	 * Creates an absolute url for remote use.
	 *
	 * @param string $service id
	 * @return string the url
	 * @since 4.0.0
	 * @deprecated 34.0.0 Use IURlGenerator::linkToRemote
	 */
	public static function linkToRemote(string $service): string {
		$urlGenerator = Server::get(IURLGenerator::class);
		return $urlGenerator->linkToRemote($service);
	}

	/**
	 * Returns the server host name without the port number.
	 *
	 * @return string The server hostname
	 * @since 5.0.0
	 *
	 * TODO: Move to IRequest
	 */
	public static function getServerHostName(): string {
		$host = Server::get(IRequest::class)->getServerHost();

		// Extract only the host part before the colon
		return explode(':', $host, 2)[0];
	}

	/**
	 * Returns the default email address
	 * @param string $user_part the user part of the address
	 * @return string the default email address
	 *
	 * Assembles a default email address (using the server hostname
	 * and the given user part, and returns it
	 * Example: when given lostpassword-noreply as $user_part param,
	 *     and is currently accessed via http(s)://example.com/,
	 *     it would return 'lostpassword-noreply@example.com'
	 *
	 * If the configuration value 'mail_from_address' is set in
	 * config.php, this value will override the $user_part that
	 * is passed to this function
	 * @since 5.0.0
	 */
	public static function getDefaultEmailAddress(string $user_part): string {
		$config = Server::get(IConfig::class);
		$user_part = $config->getSystemValueString('mail_from_address', $user_part);
		$host_name = self::getServerHostName();
		$host_name = $config->getSystemValueString('mail_domain', $host_name);
		$defaultEmailAddress = $user_part . '@' . $host_name;

		$emailValidator = Server::get(IEmailValidator::class);
		if ($emailValidator->isValid($defaultEmailAddress)) {
			return $defaultEmailAddress;
		}

		// in case we cannot build a valid email address from the hostname let's fallback to 'localhost.localdomain'
		return $user_part . '@localhost.localdomain';
	}

	/**
	 * Converts a numeric value to an integer or float.
	 * 
	 * Returns an integer if the value fits within the system's integer limits, 
	 * otherwise returns a float up to maximum hardware precision.
	 * 
	 * @param numeric-string|float|int $number The numeric value to convert.
	 * @return int|float An integer if it fits, otherwise a float.
	 * @since 26.0.0
	 */
	public static function numericToNumber(string|float|int $number): int|float {
		// Triggers native engine-level type coercion to int or float
		return +$number;
	}

	/**
	 * Make a human file size (2048 to 2 kB)
	 * @param int|float $bytes file size in bytes
	 * @return string a human readable file size
	 * @since 4.0.0
	 */
	public static function humanFileSize(int|float $bytes): string {
		if ($bytes < 0) {
			return '?';
		}
		if ($bytes < 1024) {
			return "$bytes B";
		}
		$bytes = round($bytes / 1024, 0);
		if ($bytes < 1024) {
			return "$bytes KB";
		}
		$bytes = round($bytes / 1024, 1);
		if ($bytes < 1024) {
			return "$bytes MB";
		}
		$bytes = round($bytes / 1024, 1);
		if ($bytes < 1024) {
			return "$bytes GB";
		}
		$bytes = round($bytes / 1024, 1);
		if ($bytes < 1024) {
			return "$bytes TB";
		}

		$bytes = round($bytes / 1024, 1);
		return "$bytes PB";
	}

	/**
	 * Make a computer file size (2 kB to 2048)
	 * Inspired by: https://www.php.net/manual/en/function.filesize.php#92418
	 *
	 * @param string $str file size in a fancy format
	 * @return false|int|float a file size in bytes
	 * @since 4.0.0
	 */
	public static function computerFileSize(string $str): false|int|float {
		$str = strtolower($str);
		if (is_numeric($str)) {
			return Util::numericToNumber($str);
		}

		$bytes_array = [
			'b' => 1,
			'k' => 1024,
			'kb' => 1024,
			'mb' => 1024 * 1024,
			'm' => 1024 * 1024,
			'gb' => 1024 * 1024 * 1024,
			'g' => 1024 * 1024 * 1024,
			'tb' => 1024 * 1024 * 1024 * 1024,
			't' => 1024 * 1024 * 1024 * 1024,
			'pb' => 1024 * 1024 * 1024 * 1024 * 1024,
			'p' => 1024 * 1024 * 1024 * 1024 * 1024,
		];

		$bytes = (float)$str;

		if (preg_match('#([kmgtp]?b?)$#si', $str, $matches) && isset($bytes_array[$matches[1]])) {
			$bytes *= $bytes_array[$matches[1]];
		} else {
			return false;
		}

		return Util::numericToNumber(round($bytes));
	}

	/**
	 * connects a function to a hook
	 *
	 * @param string $signalClass class name of emitter
	 * @param string $signalName name of signal
	 * @param string|object $slotClass class name of slot
	 * @param string $slotName name of slot
	 * @return bool
	 *
	 * This function makes it very easy to connect to use hooks.
	 *
	 * @since 4.0.0
	 * @deprecated 21.0.0 use \OCP\EventDispatcher\IEventDispatcher::addListener
	 */
	public static function connectHook($signalClass, $signalName, $slotClass, $slotName) {
		return \OC_Hook::connect($signalClass, $signalName, $slotClass, $slotName);
	}

	/**
	 * Emits a signal. To get data from the slot use references!
	 * @param string $signalclass class name of emitter
	 * @param string $signalname name of signal
	 * @param array $params default: array() array with additional data
	 * @return bool true if slots exists or false if not
	 *
	 * TODO: write example
	 * @since 4.0.0
	 * @deprecated 21.0.0 use \OCP\EventDispatcher\IEventDispatcher::dispatchTypedEvent
	 */
	public static function emitHook($signalclass, $signalname, $params = []) {
		return \OC_Hook::emit($signalclass, $signalname, $params);
	}

	/**
	 * Register an get/post call. This is important to prevent CSRF attacks
	 * @since 4.5.0
	 * @deprecated 32.0.0 directly use CsrfTokenManager instead
	 */
	public static function callRegister() {
		return Server::get(CsrfTokenManager::class)->getToken()->getEncryptedValue();
	}

	/**
	 * Used to sanitize HTML
	 *
	 * This function is used to sanitize HTML and should be applied on any
	 * string or array of strings before displaying it on a web page.
	 *
	 * @param string|string[]|null $value
	 * @return ($value is array ? string[] : string) an array of sanitized strings or a single sanitized string, depends on the input parameter.
	 * @since 4.5.0
	 */
	public static function sanitizeHTML(string|array|null $value): string|array {
		if (is_array($value)) {
			return array_map(function (string $value): string {
				return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
			}, $value);
		}
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Public function to encode url parameters
	 *
	 * This function is used to encode path to file before output.
	 * Encoding is done according to RFC 3986 with one exception:
	 * Character '/' is preserved as is.
	 *
	 * @param string $component part of URI to encode
	 * @return string
	 * @since 6.0.0
	 */
	public static function encodePath(string $component): string {
		$encoded = rawurlencode($component);
		return str_replace('%2F', '/', $encoded);
	}

	/**
	 * Returns an array with all keys from input lowercased or uppercased. Numbered indices are left as is.
	 *
	 * @param array $input The array to work on
	 * @param int $case Either MB_CASE_UPPER or MB_CASE_LOWER (default)
	 * @param string $encoding The encoding parameter is the character encoding. Defaults to UTF-8
	 * @return array
	 * @since 4.5.0
	 */
	public static function mb_array_change_key_case($input, $case = MB_CASE_LOWER, $encoding = 'UTF-8') {
		$case = ($case !== MB_CASE_UPPER) ? MB_CASE_LOWER : MB_CASE_UPPER;
		$ret = [];
		foreach ($input as $k => $v) {
			$ret[mb_convert_case($k, $case, $encoding)] = $v;
		}
		return $ret;
	}

	/**
	 * calculates the maximum upload size respecting system settings, free space and user quota
	 *
	 * @param string $dir the current folder where the user currently operates
	 * @param int|float|null $free the number of bytes free on the storage holding $dir, if not set this will be received from the storage directly
	 * @return int|float number of bytes representing
	 * @since 5.0.0
	 */
	public static function maxUploadFilesize(string $dir, int|float|null $free = null): int|float {
		if (is_null($free) || $free < 0) {
			$free = self::freeSpace($dir);
		}
		return min($free, self::uploadLimit());
	}

	/**
	 * Calculate free space left within user quota
	 * @param string $dir the current folder where the user currently operates
	 * @return int|float number of bytes representing
	 * @since 7.0.0
	 */
	public static function freeSpace(string $dir): int|float {
		$freeSpace = \OC\Files\Filesystem::free_space($dir);
		if ($freeSpace < \OCP\Files\FileInfo::SPACE_UNLIMITED) {
			$freeSpace = max($freeSpace, 0);
			return $freeSpace;
		} else {
			return (INF > 0)? INF: PHP_INT_MAX; // work around https://bugs.php.net/bug.php?id=69188
		}
	}

	/**
	 * Calculate PHP upload limit
	 *
	 * @return int|float number of bytes representing
	 * @since 7.0.0
	 */
	public static function uploadLimit(): int|float {
		$ini = Server::get(IniGetWrapper::class);
		$upload_max_filesize = self::computerFileSize($ini->get('upload_max_filesize')) ?: 0;
		$post_max_size = self::computerFileSize($ini->get('post_max_size')) ?: 0;
		if ($upload_max_filesize === 0 && $post_max_size === 0) {
			return INF;
		} elseif ($upload_max_filesize === 0 || $post_max_size === 0) {
			return max($upload_max_filesize, $post_max_size); //only the non 0 value counts
		} else {
			return min($upload_max_filesize, $post_max_size);
		}
	}

	/**
	 * Compare two strings to provide a natural sort
	 * @param string $a first string to compare
	 * @param string $b second string to compare
	 * @return int -1 if $b comes before $a, 1 if $a comes before $b
	 *             or 0 if the strings are identical
	 * @since 7.0.0
	 */
	public static function naturalSortCompare($a, $b) {
		return \OC\NaturalSort::getInstance()->compare($a, $b);
	}

	/**
	 * Check if a password is required for each public link
	 *
	 * @param bool $checkGroupMembership Check group membership exclusion
	 * @return boolean
	 * @since 7.0.0
	 * @deprecated 34.0.0 use OCP\Share\IManager's shareApiLinkEnforcePassword directly
	 */
	public static function isPublicLinkPasswordRequired(bool $checkGroupMembership = true) {
		return \OC_Util::isPublicLinkPasswordRequired($checkGroupMembership);
	}

	/**
	 * Check if share API enforces a default expire date
	 *
	 * @return boolean
	 * @since 8.0.0
	 * @deprecated 34.0.0 use OCP\Share\IManager's shareApiLinkDefaultExpireDateEnforced directly
	 */
	public static function isDefaultExpireDateEnforced() {
		return \OC_Util::isDefaultExpireDateEnforced();
	}

	/**
	 * Checks whether the current version needs upgrade.
	 *
	 * @return bool true if upgrade is needed, false otherwise
	 * @since 7.0.0
	 */
	public static function needUpgrade() {
		if (self::$needUpgradeCache === null) {
			self::$needUpgradeCache = \OC_Util::needUpgrade(Server::get(\OC\SystemConfig::class));
		}
		return self::$needUpgradeCache;
	}

	/**
	 * Sometimes a string has to be shortened to fit within a certain maximum
	 * data length in bytes. substr() you may break multibyte characters,
	 * because it operates on single byte level. mb_substr() operates on
	 * characters, so does not ensure that the shortened string satisfies the
	 * max length in bytes.
	 *
	 * For example, json_encode is messing with multibyte characters a lot,
	 * replacing them with something along "\u1234".
	 *
	 * This function shortens the string with by $accuracy (-5) from
	 * $dataLength characters, until it fits within $dataLength bytes.
	 *
	 * @since 23.0.0
	 */
	public static function shortenMultibyteString(string $subject, int $dataLength, int $accuracy = 5): string {
		$temp = mb_substr($subject, 0, $dataLength);
		// json encodes encapsulates the string in double quotes, they need to be substracted
		while ((strlen(json_encode($temp)) - 2) > $dataLength) {
			$temp = mb_substr($temp, 0, -$accuracy);
		}
		return $temp;
	}

	/**
	 * Check if a function is enabled in the php configuration
	 *
	 * @since 25.0.0
	 */
	public static function isFunctionEnabled(string $functionName): bool {
		if (!function_exists($functionName)) {
			return false;
		}
		$ini = Server::get(IniGetWrapper::class);
		$disabled = explode(',', $ini->get('disable_functions') ?: '');
		$disabled = array_map('trim', $disabled);
		if (in_array($functionName, $disabled)) {
			return false;
		}
		return true;
	}
}
