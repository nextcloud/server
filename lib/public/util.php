<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Public interface of ownCloud for apps to use.
 * Utility Class.
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;
use DateTimeZone;

/**
 * This class provides different helper functions to make the life of a developer easier
 */
class Util {
	// consts for Logging
	const DEBUG=0;
	const INFO=1;
	const WARN=2;
	const ERROR=3;
	const FATAL=4;

	/**
	 * get the current installed version of ownCloud
	 * @return array
	 */
	public static function getVersion() {
		return(\OC_Util::getVersion());
	}

	/**
	 * send an email
	 * @param string $toaddress
	 * @param string $toname
	 * @param string $subject
	 * @param string $mailtext
	 * @param string $fromaddress
	 * @param string $fromname
	 * @param int $html
	 * @param string $altbody
	 * @param string $ccaddress
	 * @param string $ccname
	 * @param string $bcc
	 */
	public static function sendMail( $toaddress, $toname, $subject, $mailtext, $fromaddress, $fromname,
		$html = 0, $altbody = '', $ccaddress = '', $ccname = '', $bcc = '') {
		// call the internal mail class
		\OC_MAIL::send($toaddress, $toname, $subject, $mailtext, $fromaddress, $fromname,
			$html, $altbody, $ccaddress, $ccname, $bcc);
	}

	/**
	 * write a message in the log
	 * @param string $app
	 * @param string $message
	 * @param int $level
	 */
	public static function writeLog( $app, $message, $level ) {
		// call the internal log class
		\OC_LOG::write( $app, $message, $level );
	}

	/**
	 * write exception into the log
	 * @param string $app app name
	 * @param \Exception $ex exception to log
	 * @param int $level log level, defaults to \OCP\Util::FATAL
	 */
	public static function logException( $app, \Exception $ex, $level = \OCP\Util::FATAL ) {
		$exception = array(
			'Message' => $ex->getMessage(),
			'Code' => $ex->getCode(),
			'Trace' => $ex->getTraceAsString(),
			'File' => $ex->getFile(),
			'Line' => $ex->getLine(),
		);
		\OCP\Util::writeLog($app, 'Exception: ' . json_encode($exception), $level);
	}

	/**
	 * check if sharing is disabled for the current user
	 *
	 * @return boolean
	 */
	public static function isSharingDisabledForUser() {
		return \OC_Util::isSharingDisabledForUser();
	}

	/**
	 * get l10n object
	 * @param string $application
	 * @param string|null $language
	 * @return \OC_L10N
	 */
	public static function getL10N($application, $language = null) {
		return \OC::$server->getL10N($application, $language);
	}

	/**
	 * add a css file
	 * @param string $application
	 * @param string $file
	 */
	public static function addStyle( $application, $file = null ) {
		\OC_Util::addStyle( $application, $file );
	}

	/**
	 * add a javascript file
	 * @param string $application
	 * @param string $file
	 */
	public static function addScript( $application, $file = null ) {
		\OC_Util::addScript( $application, $file );
	}

	/**
	 * Add a translation JS file
	 * @param string $application application id
	 * @param string $languageCode language code, defaults to the current locale
	 */
	public static function addTranslations($application, $languageCode = null) {
		\OC_Util::addTranslations($application, $languageCode);
	}

	/**
	 * Add a custom element to the header
	 * If $text is null then the element will be written as empty element.
	 * So use "" to get a closing tag.
	 * @param string $tag tag name of the element
	 * @param array $attributes array of attributes for the element
	 * @param string $text the text content for the element
	 */
	public static function addHeader($tag, $attributes, $text=null) {
		\OC_Util::addHeader($tag, $attributes, $text);
	}

	/**
	 * formats a timestamp in the "right" way
	 * @param int $timestamp $timestamp
	 * @param bool $dateOnly option to omit time from the result
	 * @param DateTimeZone|string $timeZone where the given timestamp shall be converted to
	 * @return string timestamp
	 *
	 * @deprecated Use \OC::$server->query('DateTimeFormatter') instead
	 */
	public static function formatDate($timestamp, $dateOnly=false, $timeZone = null) {
		return(\OC_Util::formatDate($timestamp, $dateOnly, $timeZone));
	}

	/**
	 * check if some encrypted files are stored
	 * @return bool
	 */
	public static function encryptedFiles() {
		return \OC_Util::encryptedFiles();
	}

	/**
	 * Creates an absolute url to the given app and file.
	 * @param string $app app
	 * @param string $file file
	 * @param array $args array with param=>value, will be appended to the returned url
	 * 	The value of $args will be urlencoded
	 * @return string the url
	 */
	public static function linkToAbsolute( $app, $file, $args = array() ) {
		return(\OC_Helper::linkToAbsolute( $app, $file, $args ));
	}

	/**
	 * Creates an absolute url for remote use.
	 * @param string $service id
	 * @return string the url
	 */
	public static function linkToRemote( $service ) {
		return(\OC_Helper::linkToRemote( $service ));
	}

	/**
	 * Creates an absolute url for public use
	 * @param string $service id
	 * @return string the url
	 */
	public static function linkToPublic($service) {
		return \OC_Helper::linkToPublic($service);
	}

	/**
	 * Creates an url using a defined route
	 * @param string $route
	 * @param array $parameters
	 * @internal param array $args with param=>value, will be appended to the returned url
	 * @return string the url
	 */
	public static function linkToRoute( $route, $parameters = array() ) {
		return \OC_Helper::linkToRoute($route, $parameters);
	}

	/**
	* Creates an url to the given app and file
	* @param string $app app
	* @param string $file file
	* @param array $args array with param=>value, will be appended to the returned url
	* 	The value of $args will be urlencoded
	* @return string the url
	*/
	public static function linkTo( $app, $file, $args = array() ) {
		return(\OC_Helper::linkTo( $app, $file, $args ));
	}

	/**
	 * Returns the server host, even if the website uses one or more reverse proxy
	 * @return string the server host
	 */
	public static function getServerHost() {
		return(\OC_Request::serverHost());
	}

	/**
	 * Returns the server host name without an eventual port number
	 * @return string the server hostname
	 */
	public static function getServerHostName() {
		$host_name = self::getServerHost();
		// strip away port number (if existing)
		$colon_pos = strpos($host_name, ':');
		if ($colon_pos != FALSE) {
			$host_name = substr($host_name, 0, $colon_pos);
		}
		return $host_name;
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
	 */
	public static function getDefaultEmailAddress($user_part) {
		$user_part = \OC_Config::getValue('mail_from_address', $user_part);
		$host_name = self::getServerHostName();
		$host_name = \OC_Config::getValue('mail_domain', $host_name);
		$defaultEmailAddress = $user_part.'@'.$host_name;

		if (\OC_Mail::validateAddress($defaultEmailAddress)) {
			return $defaultEmailAddress;
		}

		// in case we cannot build a valid email address from the hostname let's fallback to 'localhost.localdomain'
		return $user_part.'@localhost.localdomain';
	}

	/**
	 * Returns the server protocol. It respects reverse proxy servers and load balancers
	 * @return string the server protocol
	 */
	public static function getServerProtocol() {
		return(\OC_Request::serverProtocol());
	}

	/**
	 * Returns the request uri, even if the website uses one or more reverse proxies
	 * @return string the request uri
	 */
	public static function getRequestUri() {
		return(\OC_Request::requestUri());
	}

	/**
	 * Returns the script name, even if the website uses one or more reverse proxies
	 * @return string the script name
	 */
	public static function getScriptName() {
		return(\OC_Request::scriptName());
	}

	/**
	 * Creates path to an image
	 * @param string $app app
	 * @param string $image image name
	 * @return string the url
	 */
	public static function imagePath( $app, $image ) {
		return(\OC_Helper::imagePath( $app, $image ));
	}

	/**
	 * Make a human file size (2048 to 2 kB)
	 * @param int $bytes file size in bytes
	 * @return string a human readable file size
	 */
	public static function humanFileSize( $bytes ) {
		return(\OC_Helper::humanFileSize( $bytes ));
	}

	/**
	 * Make a computer file size (2 kB to 2048)
	 * @param string $str file size in a fancy format
	 * @return int a file size in bytes
	 *
	 * Inspired by: http://www.php.net/manual/en/function.filesize.php#92418
	 */
	public static function computerFileSize( $str ) {
		return(\OC_Helper::computerFileSize( $str ));
	}

	/**
	 * connects a function to a hook
	 * @param string $signalclass class name of emitter
	 * @param string $signalname name of signal
	 * @param string $slotclass class name of slot
	 * @param string $slotname name of slot
	 * @return bool
	 *
	 * This function makes it very easy to connect to use hooks.
	 *
	 * TODO: write example
	 */
	static public function connectHook( $signalclass, $signalname, $slotclass, $slotname ) {
		return(\OC_Hook::connect( $signalclass, $signalname, $slotclass, $slotname ));
	}

	/**
	 * Emits a signal. To get data from the slot use references!
	 * @param string $signalclass class name of emitter
	 * @param string $signalname name of signal
	 * @param array $params default: array() array with additional data
	 * @return bool true if slots exists or false if not
	 *
	 * TODO: write example
	 */
	static public function emitHook( $signalclass, $signalname, $params = array()) {
		return(\OC_Hook::emit( $signalclass, $signalname, $params ));
	}

	/**
	 * Register an get/post call. This is important to prevent CSRF attacks
	 * TODO: write example
	 */
	public static function callRegister() {
		return(\OC_Util::callRegister());
	}

	/**
	 * Check an ajax get/post call if the request token is valid. exit if not.
	 * Todo: Write howto
	 */
	public static function callCheck() {
		\OC_Util::callCheck();
	}

	/**
	 * Used to sanitize HTML
	 *
	 * This function is used to sanitize HTML and should be applied on any
	 * string or array of strings before displaying it on a web page.
	 *
	 * @param string|array $value
	 * @return string|array an array of sanitized strings or a single sinitized string, depends on the input parameter.
	 */
	public static function sanitizeHTML( $value ) {
		return(\OC_Util::sanitizeHTML($value));
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
	 */
	public static function encodePath($component) {
		return(\OC_Util::encodePath($component));
	}

	/**
	 * Returns an array with all keys from input lowercased or uppercased. Numbered indices are left as is.
	 *
	 * @param array $input The array to work on
	 * @param int $case Either MB_CASE_UPPER or MB_CASE_LOWER (default)
	 * @param string $encoding The encoding parameter is the character encoding. Defaults to UTF-8
	 * @return array
	 */
	public static function mb_array_change_key_case($input, $case = MB_CASE_LOWER, $encoding = 'UTF-8') {
		return(\OC_Helper::mb_array_change_key_case($input, $case, $encoding));
	}

	/**
	 * replaces a copy of string delimited by the start and (optionally) length parameters with the string given in replacement.
	 *
	 * @param string $string The input string. Opposite to the PHP build-in function does not accept an array.
	 * @param string $replacement The replacement string.
	 * @param int $start If start is positive, the replacing will begin at the start'th offset into string. If start is negative, the replacing will begin at the start'th character from the end of string.
	 * @param int $length Length of the part to be replaced
	 * @param string $encoding The encoding parameter is the character encoding. Defaults to UTF-8
	 * @return string
	 */
	public static function mb_substr_replace($string, $replacement, $start, $length = null, $encoding = 'UTF-8') {
		return(\OC_Helper::mb_substr_replace($string, $replacement, $start, $length, $encoding));
	}

	/**
	 * Replace all occurrences of the search string with the replacement string
	 *
	 * @param string $search The value being searched for, otherwise known as the needle. String.
	 * @param string $replace The replacement string.
	 * @param string $subject The string or array being searched and replaced on, otherwise known as the haystack.
	 * @param string $encoding The encoding parameter is the character encoding. Defaults to UTF-8
	 * @param int $count If passed, this will be set to the number of replacements performed.
	 * @return string
	 */
	public static function mb_str_replace($search, $replace, $subject, $encoding = 'UTF-8', &$count = null) {
		return(\OC_Helper::mb_str_replace($search, $replace, $subject, $encoding, $count));
	}

	/**
	 * performs a search in a nested array
	 *
	 * @param array $haystack the array to be searched
	 * @param string $needle the search string
	 * @param int $index optional, only search this key name
	 * @return mixed the key of the matching field, otherwise false
	 */
	public static function recursiveArraySearch($haystack, $needle, $index = null) {
		return(\OC_Helper::recursiveArraySearch($haystack, $needle, $index));
	}

	/**
	 * calculates the maximum upload size respecting system settings, free space and user quota
	 *
	 * @param string $dir the current folder where the user currently operates
	 * @param int $free the number of bytes free on the storage holding $dir, if not set this will be received from the storage directly
	 * @return int number of bytes representing
	 */
	public static function maxUploadFilesize($dir, $free = null) {
		return \OC_Helper::maxUploadFilesize($dir, $free);
	}

	/**
	 * Calculate free space left within user quota
	 * @param string $dir the current folder where the user currently operates
	 * @return int number of bytes representing
	 */
	public static function freeSpace($dir) {
		return \OC_Helper::freeSpace($dir);
	}

	/**
	 * Calculate PHP upload limit
	 *
	 * @return int number of bytes representing
	 */
	public static function uploadLimit() {
		return \OC_Helper::uploadLimit();
	}

	/**
	 * Returns whether the given file name is valid
	 * @param string $file file name to check
	 * @return bool true if the file name is valid, false otherwise
	 */
	public static function isValidFileName($file) {
		return \OC_Util::isValidFileName($file);
	}

	/**
	 * Generates a cryptographic secure pseudo-random string
	 * @param int $length of the random string
	 * @return string
	 * @deprecated Use \OC::$server->getSecureRandom()->getMediumStrengthGenerator()->generate($length); instead
	 */
	public static function generateRandomBytes($length = 30) {
		return \OC_Util::generateRandomBytes($length);
	}

	/**
	 * Compare two strings to provide a natural sort
	 * @param string $a first string to compare
	 * @param string $b second string to compare
	 * @return -1 if $b comes before $a, 1 if $a comes before $b
	 * or 0 if the strings are identical
	 */
	public static function naturalSortCompare($a, $b) {
		return \OC\NaturalSort::getInstance()->compare($a, $b);
	}

	/**
	 * check if a password is required for each public link
	 * @return boolean
	 */
	public static function isPublicLinkPasswordRequired() {
		return \OC_Util::isPublicLinkPasswordRequired();
	}

	/**
	 * check if share API enforces a default expire date
	 * @return boolean
	 */
	public static function isDefaultExpireDateEnforced() {
		return \OC_Util::isDefaultExpireDateEnforced();
	}


	/**
	 * Checks whether the current version needs upgrade.
	 *
	 * @return bool true if upgrade is needed, false otherwise
	 */
	public static function needUpgrade() {
		return \OC_Util::needUpgrade(\OC::$server->getConfig());
	}
}
