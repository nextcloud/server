<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Individual IT Services <info@individual-it.net>
 * @author Jens-Christian Fischer <jens-christian.fischer@switch.ch>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Nicolas Grekas <nicolas.grekas@gmail.com>
 * @author Pellaeon Lin <nfsmwlin@gmail.com>
 * @author Randolph Carter <RandolphCarter@fantasymail.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Stefan Herbrechtsmeier <stefan@herbrechtsmeier.net>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
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
 * @since 4.0.0
 */
class Util {
	// consts for Logging
	const DEBUG=0;
	const INFO=1;
	const WARN=2;
	const ERROR=3;
	const FATAL=4;

	/** \OCP\Share\IManager */
	private static $shareManager;

	/**
	 * get the current installed version of ownCloud
	 * @return array
	 * @since 4.0.0
	 */
	public static function getVersion() {
		return(\OC_Util::getVersion());
	}
	
	/**
	 * Set current update channel
	 * @param string $channel
	 * @since 8.1.0
	 */
	public static function setChannel($channel) {
		//Flush timestamp to reload version.php
		\OC::$server->getSession()->set('OC_Version_Timestamp', 0);
		\OC::$server->getAppConfig()->setValue('core', 'OC_Channel', $channel);
		\OC::$server->getConfig()->setSystemValue('updater.release.channel', $channel);
	}
	
	/**
	 * Get current update channel
	 * @return string
	 * @since 8.1.0
	 */
	public static function getChannel() {
		return \OC_Util::getChannel();
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
	 * @deprecated 8.1.0 Use \OCP\Mail\IMailer instead
	 * @since 4.0.0
	 */
	public static function sendMail($toaddress, $toname, $subject, $mailtext, $fromaddress, $fromname,
		$html = 0, $altbody = '', $ccaddress = '', $ccname = '', $bcc = '') {
		$mailer = \OC::$server->getMailer();
		$message = $mailer->createMessage();
		$message->setTo([$toaddress => $toname]);
		$message->setSubject($subject);
		$message->setPlainBody($mailtext);
		$message->setFrom([$fromaddress => $fromname]);
		if($html === 1) {
			$message->setHTMLBody($altbody);
		}

		if($altbody === '') {
			$message->setHTMLBody($mailtext);
			$message->setPlainBody('');
		} else {
			$message->setHtmlBody($mailtext);
			$message->setPlainBody($altbody);
		}

		if(!empty($ccaddress)) {
			if(!empty($ccname)) {
				$message->setCc([$ccaddress => $ccname]);
			} else {
				$message->setCc([$ccaddress]);
			}
		}
		if(!empty($bcc)) {
			$message->setBcc([$bcc]);
		}

		$mailer->send($message);
	}

	/**
	 * write a message in the log
	 * @param string $app
	 * @param string $message
	 * @param int $level
	 * @since 4.0.0
	 */
	public static function writeLog( $app, $message, $level ) {
		$context = ['app' => $app];
		\OC::$server->getLogger()->log($level, $message, $context);
	}

	/**
	 * write exception into the log
	 * @param string $app app name
	 * @param \Exception $ex exception to log
	 * @param int $level log level, defaults to \OCP\Util::FATAL
	 * @since ....0.0 - parameter $level was added in 7.0.0
	 * @deprecated 8.2.0 use logException of \OCP\ILogger
	 */
	public static function logException( $app, \Exception $ex, $level = \OCP\Util::FATAL ) {
		\OC::$server->getLogger()->logException($ex, ['app' => $app]);
	}

	/**
	 * check if sharing is disabled for the current user
	 *
	 * @return boolean
	 * @since 7.0.0
	 * @deprecated 9.1.0 Use \OC::$server->getShareManager()->sharingDisabledForUser
	 */
	public static function isSharingDisabledForUser() {
		if (self::$shareManager === null) {
			self::$shareManager = \OC::$server->getShareManager();
		}

		$user = \OC::$server->getUserSession()->getUser();
		if ($user !== null) {
			$user = $user->getUID();
		}

		return self::$shareManager->sharingDisabledForUser($user);
	}

	/**
	 * get l10n object
	 * @param string $application
	 * @param string|null $language
	 * @return \OC_L10N
	 * @since 6.0.0 - parameter $language was added in 8.0.0
	 */
	public static function getL10N($application, $language = null) {
		return \OC::$server->getL10N($application, $language);
	}

	/**
	 * add a css file
	 * @param string $application
	 * @param string $file
	 * @since 4.0.0
	 */
	public static function addStyle( $application, $file = null ) {
		\OC_Util::addStyle( $application, $file );
	}

	/**
	 * add a javascript file
	 * @param string $application
	 * @param string $file
	 * @since 4.0.0
	 */
	public static function addScript( $application, $file = null ) {
		\OC_Util::addScript( $application, $file );
	}

	/**
	 * Add a translation JS file
	 * @param string $application application id
	 * @param string $languageCode language code, defaults to the current locale
	 * @since 8.0.0
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
	 * @since 4.0.0
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
	 * @deprecated 8.0.0 Use \OC::$server->query('DateTimeFormatter') instead
	 * @since 4.0.0
	 */
	public static function formatDate($timestamp, $dateOnly=false, $timeZone = null) {
		return(\OC_Util::formatDate($timestamp, $dateOnly, $timeZone));
	}

	/**
	 * check if some encrypted files are stored
	 * @return bool
	 *
	 * @deprecated 8.1.0 No longer required
	 * @since 6.0.0
	 */
	public static function encryptedFiles() {
		return false;
	}

	/**
	 * Creates an absolute url to the given app and file.
	 * @param string $app app
	 * @param string $file file
	 * @param array $args array with param=>value, will be appended to the returned url
	 * 	The value of $args will be urlencoded
	 * @return string the url
	 * @since 4.0.0 - parameter $args was added in 4.5.0
	 */
	public static function linkToAbsolute( $app, $file, $args = array() ) {
		$urlGenerator = \OC::$server->getURLGenerator();
		return $urlGenerator->getAbsoluteURL(
			$urlGenerator->linkTo($app, $file, $args)
		);
	}

	/**
	 * Creates an absolute url for remote use.
	 * @param string $service id
	 * @return string the url
	 * @since 4.0.0
	 */
	public static function linkToRemote( $service ) {
		$urlGenerator = \OC::$server->getURLGenerator();
		$remoteBase = $urlGenerator->linkTo('', 'remote.php') . '/' . $service;
		return $urlGenerator->getAbsoluteURL(
			$remoteBase . (($service[strlen($service) - 1] != '/') ? '/' : '')
		);
	}

	/**
	 * Creates an absolute url for public use
	 * @param string $service id
	 * @return string the url
	 * @since 4.5.0
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
	 * @deprecated 8.1.0 Use \OC::$server->getURLGenerator()->linkToRoute($route, $parameters)
	 * @since 5.0.0
	 */
	public static function linkToRoute( $route, $parameters = array() ) {
		return \OC::$server->getURLGenerator()->linkToRoute($route, $parameters);
	}

	/**
	 * Creates an url to the given app and file
	 * @param string $app app
	 * @param string $file file
	 * @param array $args array with param=>value, will be appended to the returned url
	 * 	The value of $args will be urlencoded
	 * @return string the url
	 * @deprecated 8.1.0 Use \OC::$server->getURLGenerator()->linkTo($app, $file, $args)
	 * @since 4.0.0 - parameter $args was added in 4.5.0
	 */
	public static function linkTo( $app, $file, $args = array() ) {
		return \OC::$server->getURLGenerator()->linkTo($app, $file, $args);
	}

	/**
	 * Returns the server host, even if the website uses one or more reverse proxy
	 * @return string the server host
	 * @deprecated 8.1.0 Use \OCP\IRequest::getServerHost
	 * @since 4.0.0
	 */
	public static function getServerHost() {
		return \OC::$server->getRequest()->getServerHost();
	}

	/**
	 * Returns the server host name without an eventual port number
	 * @return string the server hostname
	 * @since 5.0.0
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
	 * @since 5.0.0
	 */
	public static function getDefaultEmailAddress($user_part) {
		$config = \OC::$server->getConfig();
		$user_part = $config->getSystemValue('mail_from_address', $user_part);
		$host_name = self::getServerHostName();
		$host_name = $config->getSystemValue('mail_domain', $host_name);
		$defaultEmailAddress = $user_part.'@'.$host_name;

		$mailer = \OC::$server->getMailer();
		if ($mailer->validateMailAddress($defaultEmailAddress)) {
			return $defaultEmailAddress;
		}

		// in case we cannot build a valid email address from the hostname let's fallback to 'localhost.localdomain'
		return $user_part.'@localhost.localdomain';
	}

	/**
	 * Returns the server protocol. It respects reverse proxy servers and load balancers
	 * @return string the server protocol
	 * @deprecated 8.1.0 Use \OCP\IRequest::getServerProtocol
	 * @since 4.5.0
	 */
	public static function getServerProtocol() {
		return \OC::$server->getRequest()->getServerProtocol();
	}

	/**
	 * Returns the request uri, even if the website uses one or more reverse proxies
	 * @return string the request uri
	 * @deprecated 8.1.0 Use \OCP\IRequest::getRequestUri
	 * @since 5.0.0
	 */
	public static function getRequestUri() {
		return \OC::$server->getRequest()->getRequestUri();
	}

	/**
	 * Returns the script name, even if the website uses one or more reverse proxies
	 * @return string the script name
	 * @deprecated 8.1.0 Use \OCP\IRequest::getScriptName
	 * @since 5.0.0
	 */
	public static function getScriptName() {
		return \OC::$server->getRequest()->getScriptName();
	}

	/**
	 * Creates path to an image
	 * @param string $app app
	 * @param string $image image name
	 * @return string the url
	 * @deprecated 8.1.0 Use \OC::$server->getURLGenerator()->imagePath($app, $image)
	 * @since 4.0.0
	 */
	public static function imagePath( $app, $image ) {
		return \OC::$server->getURLGenerator()->imagePath($app, $image);
	}

	/**
	 * Make a human file size (2048 to 2 kB)
	 * @param int $bytes file size in bytes
	 * @return string a human readable file size
	 * @since 4.0.0
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
	 * @since 4.0.0
	 */
	public static function computerFileSize( $str ) {
		return(\OC_Helper::computerFileSize( $str ));
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
	 * TODO: write example
	 * @since 4.0.0
	 */
	static public function connectHook($signalClass, $signalName, $slotClass, $slotName ) {
		return(\OC_Hook::connect($signalClass, $signalName, $slotClass, $slotName ));
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
	 */
	static public function emitHook( $signalclass, $signalname, $params = array()) {
		return(\OC_Hook::emit( $signalclass, $signalname, $params ));
	}

	/**
	 * Cached encrypted CSRF token. Some static unit-tests of ownCloud compare
	 * multiple OC_Template elements which invoke `callRegister`. If the value
	 * would not be cached these unit-tests would fail.
	 * @var string
	 */
	private static $token = '';

	/**
	 * Register an get/post call. This is important to prevent CSRF attacks
	 * @since 4.5.0
	 */
	public static function callRegister() {
		if(self::$token === '') {
			self::$token = \OC::$server->getCsrfTokenManager()->getToken()->getEncryptedValue();
		}
		return self::$token;
	}

	/**
	 * Check an ajax get/post call if the request token is valid. exit if not.
	 * @since 4.5.0
	 * @deprecated 9.0.0 Use annotations based on the app framework.
	 */
	public static function callCheck() {
		if(!\OC::$server->getRequest()->passesStrictCookieCheck()) {
			header('Location: '.\OC::$WEBROOT);
			exit();
		}

		if (!(\OC::$server->getRequest()->passesCSRFCheck())) {
			exit();
		}
	}

	/**
	 * Used to sanitize HTML
	 *
	 * This function is used to sanitize HTML and should be applied on any
	 * string or array of strings before displaying it on a web page.
	 *
	 * @param string|array $value
	 * @return string|array an array of sanitized strings or a single sanitized string, depends on the input parameter.
	 * @since 4.5.0
	 */
	public static function sanitizeHTML($value) {
		return \OC_Util::sanitizeHTML($value);
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
	 * @since 4.5.0
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
	 * @since 4.5.0
	 * @deprecated 8.2.0 Use substr_replace() instead.
	 */
	public static function mb_substr_replace($string, $replacement, $start, $length = null, $encoding = 'UTF-8') {
		return substr_replace($string, $replacement, $start, $length);
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
	 * @since 4.5.0
	 * @deprecated 8.2.0 Use str_replace() instead.
	 */
	public static function mb_str_replace($search, $replace, $subject, $encoding = 'UTF-8', &$count = null) {
		return str_replace($search, $replace, $subject, $count);
	}

	/**
	 * performs a search in a nested array
	 *
	 * @param array $haystack the array to be searched
	 * @param string $needle the search string
	 * @param int $index optional, only search this key name
	 * @return mixed the key of the matching field, otherwise false
	 * @since 4.5.0
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
	 * @since 5.0.0
	 */
	public static function maxUploadFilesize($dir, $free = null) {
		return \OC_Helper::maxUploadFilesize($dir, $free);
	}

	/**
	 * Calculate free space left within user quota
	 * @param string $dir the current folder where the user currently operates
	 * @return int number of bytes representing
	 * @since 7.0.0
	 */
	public static function freeSpace($dir) {
		return \OC_Helper::freeSpace($dir);
	}

	/**
	 * Calculate PHP upload limit
	 *
	 * @return int number of bytes representing
	 * @since 7.0.0
	 */
	public static function uploadLimit() {
		return \OC_Helper::uploadLimit();
	}

	/**
	 * Returns whether the given file name is valid
	 * @param string $file file name to check
	 * @return bool true if the file name is valid, false otherwise
	 * @deprecated 8.1.0 use \OC\Files\View::verifyPath()
	 * @since 7.0.0
	 */
	public static function isValidFileName($file) {
		return \OC_Util::isValidFileName($file);
	}

	/**
	 * Generates a cryptographic secure pseudo-random string
	 * @param int $length of the random string
	 * @return string
	 * @deprecated 8.0.0 Use \OC::$server->getSecureRandom()->getMediumStrengthGenerator()->generate($length); instead
	 * @since 7.0.0
	 */
	public static function generateRandomBytes($length = 30) {
		return \OC::$server->getSecureRandom()->generate($length, \OCP\Security\ISecureRandom::CHAR_LOWER.\OCP\Security\ISecureRandom::CHAR_DIGITS);
	}

	/**
	 * Compare two strings to provide a natural sort
	 * @param string $a first string to compare
	 * @param string $b second string to compare
	 * @return -1 if $b comes before $a, 1 if $a comes before $b
	 * or 0 if the strings are identical
	 * @since 7.0.0
	 */
	public static function naturalSortCompare($a, $b) {
		return \OC\NaturalSort::getInstance()->compare($a, $b);
	}

	/**
	 * check if a password is required for each public link
	 * @return boolean
	 * @since 7.0.0
	 */
	public static function isPublicLinkPasswordRequired() {
		return \OC_Util::isPublicLinkPasswordRequired();
	}

	/**
	 * check if share API enforces a default expire date
	 * @return boolean
	 * @since 8.0.0
	 */
	public static function isDefaultExpireDateEnforced() {
		return \OC_Util::isDefaultExpireDateEnforced();
	}

	protected static $needUpgradeCache = null;

	/**
	 * Checks whether the current version needs upgrade.
	 *
	 * @return bool true if upgrade is needed, false otherwise
	 * @since 7.0.0
	 */
	public static function needUpgrade() {
		if (!isset(self::$needUpgradeCache)) {
			self::$needUpgradeCache=\OC_Util::needUpgrade(\OC::$server->getConfig());
		}		
		return self::$needUpgradeCache;
	}
}
