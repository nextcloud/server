<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Mail;

use OCP\Defaults;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Mail\EMailDetails;
use OCP\Mail\EMailDetailsRow;
use OCP\Mail\IEMailTemplate;

/**
 * Class EMailTemplate
 *
 * addBodyText and addBodyButtonGroup automatically opens the body
 * addFooter, renderHtml, renderText automatically closes the body and the HTML if opened
 *
 * @package OC\Mail
 */
class EMailTemplate implements IEMailTemplate {
	protected string $subject = '';
	protected string $htmlBody = '';
	protected string $plainBody = '';
	/** indicated if the header is added */
	protected bool $headerAdded = false;
	/** indicated if the body is already opened */
	protected bool $bodyOpened = false;
	/** indicated if there is a list open in the body */
	protected bool $bodyListOpened = false;
	/** indicated if the footer is added */
	protected bool $footerAdded = false;
	/** @var array<array{name: string, content: string, mimeType: string}> images to embed inline, referenced via cid: */
	protected array $inlineImages = [];

	protected string $head = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" style="-webkit-font-smoothing:antialiased">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width">
	<meta name="color-scheme" content="light dark">
	<meta name="supported-color-schemes" content="light dark">
	<title></title>
	<style type="text/css">
		:root{color-scheme:light dark;supported-color-schemes:light dark}
		body{margin:0;padding:0;width:100%!important}
		@media only screen and (max-width:640px){
			.nc-card{border-radius:0!important;width:100%!important}
			.nc-pad{padding-left:20px!important;padding-right:20px!important}
			.nc-button{display:block!important;margin:0 0 12px 0!important}
		}
		@media (prefers-color-scheme:dark){
			.nc-page{background:#181818!important}
			.nc-card{background:#222222!important}
			.nc-text{color:#ebebeb!important}
			.nc-muted{color:#a6a6a6!important}
			.nc-link{color:#ebebeb!important}
			.nc-border{border-color:#3b3b3b!important}
			.nc-note-neutral,.nc-secondary{background:#2c2c2c!important}
			.nc-note-info{background:#10303f!important}
			.nc-note-warning{background:#3a2e10!important}
			.nc-note-error{background:#3d1717!important}
		}
	</style>
</head>
<body class="nc-page" style="-moz-box-sizing:border-box;-ms-text-size-adjust:100%;-webkit-box-sizing:border-box;-webkit-text-size-adjust:100%;Margin:0;background:#f4f4f5;box-sizing:border-box;margin:0;min-width:100%;padding:0;width:100%!important">
<table role="presentation" class="nc-page" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;border-collapse:collapse;border-spacing:0;width:100%">
	<tr>
		<td align="center" style="padding:32px 0">
			<table role="presentation" class="nc-card" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-collapse:separate;border-radius:16px;border-spacing:0;max-width:600px;overflow:hidden;text-align:left;width:600px">
EOF;

	protected string $tail = <<<EOF
			</table>
		</td>
	</tr>
</table>
</body>
</html>
EOF;

	protected string $header = <<<EOF
				<tr>
					<td class="nc-pad" style="background:%1\$s;padding:24px 36px">
						<img class="logo" src="%2\$s" alt="%3\$s"%4\$s style="-ms-interpolation-mode:bicubic;border:none;display:block;max-height:48px;max-width:200px;outline:0;text-decoration:none;width:auto">
					</td>
				</tr>
EOF;

	protected string $heading = <<<EOF
				<tr>
					<td class="nc-pad" style="padding:32px 36px 8px">
						<h1 class="nc-text" style="Margin:0;color:#222222;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:24px;font-weight:700;line-height:1.3;margin:0">%s</h1>
					</td>
				</tr>
EOF;

	protected string $bodyBegin = <<<EOF
				<tr>
					<td class="nc-pad" style="padding:16px 36px 12px">
EOF;

	protected string $bodyText = <<<EOF
						<p class="nc-muted" style="Margin:0 0 20px;color:#5c5c5c;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.5;margin:0 0 20px">%s</p>
EOF;

	protected string $listBegin = <<<EOF
						<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="Margin:0 0 20px;border-collapse:collapse;border-spacing:0;margin:0 0 20px;width:100%">
EOF;

	protected string $listItem = <<<EOF
							<tr>
								<td style="padding:0 12px 12px 0;vertical-align:top;width:16px">%s</td>
								<td class="nc-text" style="color:#222222;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;line-height:1.5;padding:0 0 12px;text-align:left;vertical-align:top">%s</td>
							</tr>
EOF;

	protected string $listEnd = <<<EOF
						</table>
EOF;

	protected string $buttonGroup = <<<EOF
						<table role="presentation" cellpadding="0" cellspacing="0" style="Margin:8px 0 20px;border-collapse:collapse;border-spacing:0;margin:8px 0 20px">
							<tr>
								<td>
									<a class="nc-button" href="%3\$s" style="background:%1\$s;border:1px solid %2\$s;border-radius:8px;color:%5\$s;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.2;margin:0 12px 12px 0;padding:13px 24px;text-align:center;text-decoration:none">%7\$s</a><a class="nc-button nc-secondary nc-border nc-text" href="%8\$s" style="background:#ffffff;border:1px solid #d1d1d1;border-radius:8px;color:#222222;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.2;margin:0 0 12px 0;padding:13px 24px;text-align:center;text-decoration:none">%9\$s</a>
								</td>
							</tr>
						</table>
EOF;

	protected string $button = <<<EOF
						<table role="presentation" cellpadding="0" cellspacing="0" style="Margin:8px 0 20px;border-collapse:collapse;border-spacing:0;margin:8px 0 20px">
							<tr>
								<td>
									<a class="nc-button" href="%3\$s" style="background:%1\$s;border:1px solid %2\$s;border-radius:8px;color:%5\$s;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.2;padding:13px 24px;text-align:center;text-decoration:none">%7\$s</a>
								</td>
							</tr>
						</table>
EOF;

	protected string $bodyEnd = <<<EOF
					</td>
				</tr>
EOF;

	protected string $footer = <<<EOF
				<tr>
					<td class="nc-pad nc-border nc-muted" style="border-top:1px solid #e5e5e5;color:#767676;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:13px;line-height:1.6;padding:24px 36px 32px">%s</td>
				</tr>
EOF;

	protected string $sender = <<<EOF
				<tr>
					<td class="nc-pad" style="padding:32px 36px 0">
						<table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border-spacing:0">
							<tr>
								<td style="padding:0;vertical-align:middle;width:44px">%1\$s</td>
								<td class="nc-text" style="color:#222222;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;line-height:1.4;padding:0 0 0 14px;vertical-align:middle"><strong>%2\$s</strong>%3\$s</td>
							</tr>
						</table>
					</td>
				</tr>
EOF;

	protected string $senderSubline = <<<EOF
<br><span class="nc-muted" style="color:#767676;font-size:14px">%s</span>
EOF;

	protected string $initials = <<<EOF
<div style="background:%1\$s;border-radius:50%%;color:%2\$s;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:700;height:44px;line-height:44px;text-align:center;width:44px">%3\$s</div>
EOF;

	protected string $note = <<<EOF
						<table role="presentation" width="100%%" cellpadding="0" cellspacing="0" style="Margin:0 0 20px;border-collapse:separate;border-spacing:0;margin:0 0 20px;width:100%%">
							<tr>
								<td class="nc-note-%4\$s nc-text" style="background:%1\$s;border-left:4px solid %2\$s;border-radius:12px;color:#222222;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;line-height:1.5;padding:16px 20px">%3\$s</td>
							</tr>
						</table>
EOF;

	protected string $noteLabel = <<<EOF
<span class="nc-muted" style="color:#767676;font-size:14px">%s</span><br>
EOF;

	/** @var array<string, array{background: string, border: string}> */
	protected array $noteColors = [
		IEMailTemplate::NOTE_NEUTRAL => ['background' => '#f4f4f5', 'border' => 'transparent'],
		IEMailTemplate::NOTE_INFO => ['background' => '#e5f0f5', 'border' => '#0071ad'],
		IEMailTemplate::NOTE_WARNING => ['background' => '#fdf3dc', 'border' => '#a37200'],
		IEMailTemplate::NOTE_ERROR => ['background' => '#fbe5e5', 'border' => '#c50000'],
	];

	protected string $detailsBegin = <<<EOF
						<table role="presentation" class="nc-border" width="100%%" cellpadding="0" cellspacing="0" style="Margin:0 0 20px;border:1px solid #e5e5e5;border-collapse:separate;border-radius:12px;border-spacing:0;margin:0 0 20px;width:100%%">
							<tr>
								<td class="nc-border" colspan="2" style="%1\$spadding:20px">
									<table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border-spacing:0">
										<tr>
											%2\$s<td class="nc-text" style="color:#222222;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;line-height:1.4;padding:0;vertical-align:middle"><strong style="font-size:18px">%3\$s</strong>%4\$s</td>
										</tr>
									</table>
								</td>
							</tr>
EOF;

	protected string $detailsLeading = <<<EOF
<td style="padding:0 16px 0 0;vertical-align:middle">%s</td>
EOF;

	protected string $detailsDateBadge = <<<EOF
<table role="presentation" class="nc-border" cellpadding="0" cellspacing="0" style="border:1px solid #e5e5e5;border-collapse:separate;border-radius:8px;border-spacing:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;text-align:center;width:52px">
	<tr><td style="background:%1\$s;border-radius:7px 7px 0 0;color:%2\$s;font-size:11px;font-weight:700;letter-spacing:1px;padding:3px 0;text-transform:uppercase">%3\$s</td></tr>
	<tr><td class="nc-text" style="color:#222222;font-size:22px;font-weight:700;padding:4px 0 6px">%4\$s</td></tr>
</table>
EOF;

	protected string $detailsRow = <<<EOF
							<tr>
								<td class="nc-border nc-muted" style="%1\$scolor:#767676;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;line-height:1.5;padding:12px 20px;vertical-align:top;width:30%%">%2\$s</td>
								<td class="nc-border nc-text" style="%1\$scolor:#222222;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;line-height:1.5;padding:12px 20px 12px 0;vertical-align:top">%3\$s</td>
							</tr>
EOF;

	protected string $detailsEnd = <<<EOF
						</table>
EOF;

	protected string $buttonsBegin = <<<EOF
						%s<table role="presentation" cellpadding="0" cellspacing="0" style="Margin:8px 0 20px;border-collapse:collapse;border-spacing:0;margin:8px 0 20px">
							<tr>
								<td>
EOF;

	protected string $buttonsLabel = <<<EOF
<p class="nc-text" style="Margin:8px 0 12px;color:#222222;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.4;margin:8px 0 12px">%s</p>
EOF;

	protected string $buttonsPrimary = <<<EOF
<a class="nc-button" href="%2\$s" style="background:%1\$s;border:1px solid %1\$s;border-radius:8px;color:%3\$s;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.2;margin:0 12px 12px 0;padding:13px 24px;text-align:center;text-decoration:none">%4\$s</a>
EOF;

	protected string $buttonsSecondary = <<<EOF
<a class="nc-button nc-secondary nc-border nc-text" href="%1\$s" style="background:#ffffff;border:1px solid #d1d1d1;border-radius:8px;color:#222222;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.2;margin:0 12px 12px 0;padding:13px 24px;text-align:center;text-decoration:none">%2\$s</a>
EOF;

	protected string $buttonsEnd = <<<EOF
								</td>
							</tr>
						</table>
EOF;

	public function __construct(
		protected Defaults $themingDefaults,
		protected IURLGenerator $urlGenerator,
		protected IFactory $l10nFactory,
		protected ?int $logoWidth,
		protected ?int $logoHeight,
		protected string $emailId,
		protected array $data,
	) {
		$this->htmlBody .= $this->head;
	}

	/**
	 * Sets the subject of the email
	 */
	#[\Override]
	public function setSubject(string $subject): void {
		$this->subject = $subject;
	}

	/**
	 * Adds a header to the email
	 */
	#[\Override]
	public function addHeader(): void {
		if ($this->headerAdded) {
			return;
		}
		$this->headerAdded = true;

		$logoSizeDimensions = '';
		if ($this->logoWidth && $this->logoHeight) {
			// Provide a logo size when we have the dimensions so that it displays nicely in Outlook
			$logoSizeDimensions = ' width="' . $this->logoWidth . '" height="' . $this->logoHeight . '"';
		}

		$logoImage = $this->themingDefaults->getLogoImage();
		if ($logoImage !== null) {
			// Embed the logo directly in the message instead of linking to it, so mail
			// clients don't have to fetch it from the internet (some (e.g. gmail) block that).
			$logoSrc = 'cid:logo';
			$this->inlineImages[] = ['name' => 'logo'] + $logoImage;
		} else {
			$logoSrc = $this->urlGenerator->getAbsoluteURL($this->themingDefaults->getLogo(false));
		}
		$this->htmlBody .= vsprintf($this->header, [$this->themingDefaults->getDefaultColorPrimary(), $logoSrc, $this->themingDefaults->getName(), $logoSizeDimensions]);
	}

	/**
	 * Images that must be embedded inline in the message, referenced via `cid:<name>` in the HTML body
	 *
	 * @return array<array{name: string, content: string, mimeType: string}>
	 */
	public function getInlineImages(): array {
		return $this->inlineImages;
	}

	/**
	 * Adds a heading to the email
	 *
	 * @param string|bool $plainTitle Title that is used in the plain text email
	 *                                if empty the $title is used, if false none will be used
	 */
	#[\Override]
	public function addHeading(string $title, $plainTitle = ''): void {
		if ($this->footerAdded) {
			return;
		}
		if ($plainTitle === '') {
			$plainTitle = $title;
		}

		$this->ensureBodyIsClosed();
		$this->htmlBody .= vsprintf($this->heading, [htmlspecialchars($title)]);
		if ($plainTitle !== false) {
			$this->plainBody .= $plainTitle . PHP_EOL . PHP_EOL;
		}
	}

	/**
	 * Open the HTML body when it is not already
	 */
	protected function ensureBodyIsOpened(): void {
		if ($this->bodyOpened) {
			return;
		}

		$this->htmlBody .= $this->bodyBegin;
		$this->bodyOpened = true;
	}

	/**
	 * Adds a paragraph to the body of the email
	 *
	 * @param string $text Note: When $plainText falls back to this, HTML is automatically escaped in the HTML email
	 * @param string|bool $plainText Text that is used in the plain text email
	 *                               if empty the $text is used, if false none will be used
	 */
	#[\Override]
	public function addBodyText(string $text, $plainText = ''): void {
		if ($this->footerAdded) {
			return;
		}
		if ($plainText === '') {
			$plainText = $text;
			$text = htmlspecialchars($text);
		}

		$this->ensureBodyListClosed();
		$this->ensureBodyIsOpened();

		$this->htmlBody .= vsprintf($this->bodyText, [$text]);
		if ($plainText !== false) {
			$this->plainBody .= $plainText . PHP_EOL . PHP_EOL;
		}
	}

	/**
	 * Adds a list item to the body of the email
	 *
	 * @param string $text Note: When $plainText falls back to this, HTML is automatically escaped in the HTML email
	 * @param string $metaInfo Note: When $plainMetaInfo falls back to this, HTML is automatically escaped in the HTML email
	 * @param string $icon Absolute path, must be 16*16 pixels
	 * @param string|bool $plainText Text that is used in the plain text email
	 *                               if empty or true the $text is used, if false none will be used
	 * @param string|bool $plainMetaInfo Meta info that is used in the plain text email
	 *                                   if empty or true the $metaInfo is used, if false none will be used
	 * @param integer $plainIndent plainIndent If > 0, Indent plainText by this amount.
	 * @since 12.0.0
	 */
	#[\Override]
	public function addBodyListItem(
		string $text,
		string $metaInfo = '',
		string $icon = '',
		$plainText = '',
		$plainMetaInfo = '',
		$plainIndent = 0,
	): void {
		$this->ensureBodyListOpened();

		if ($plainText === '' || $plainText === true) {
			$plainText = $text;
			$text = htmlspecialchars($text);
			$text = str_replace("\n", '<br/>', $text); // convert newlines to HTML breaks
		}
		if ($plainMetaInfo === '' || $plainMetaInfo === true) {
			$plainMetaInfo = $metaInfo;
			$metaInfo = htmlspecialchars($metaInfo);
		}

		$htmlText = $text;
		if ($metaInfo) {
			$htmlText = '<em class="nc-muted" style="color:#767676;">' . $metaInfo . '</em><br>' . $htmlText;
		}
		if ($icon !== '') {
			$icon = '<img src="' . htmlspecialchars($icon) . '" alt="&bull;">';
		} else {
			$icon = '&bull;';
		}
		$this->htmlBody .= vsprintf($this->listItem, [$icon, $htmlText]);
		if ($plainText !== false) {
			if ($plainIndent === 0) {
				/*
				 * If plainIndent is not set by caller, this is the old NC17 layout code.
				 */
				$this->plainBody .= '  * ' . $plainText;
				if ($plainMetaInfo !== false) {
					$this->plainBody .= ' (' . $plainMetaInfo . ')';
				}
				$this->plainBody .= PHP_EOL;
			} else {
				/*
				 * Caller can set plainIndent > 0 to format plainText in tabular fashion.
				 * with plainMetaInfo in column 1, and plainText in column 2.
				 * The plainMetaInfo label is right justified in a field of width
				 * "plainIndent". Multilines after the first are indented plainIndent+1
				 * (to account for space after label).  Fixes: #12391
				 */
				/** @var string $label */
				$label = ($plainMetaInfo !== false)? $plainMetaInfo : '';
				$this->plainBody .= sprintf("%{$plainIndent}s %s\n",
					$label,
					str_replace("\n", "\n" . str_repeat(' ', $plainIndent + 1), $plainText));
			}
		}
	}

	protected function ensureBodyListOpened(): void {
		if ($this->bodyListOpened) {
			return;
		}

		$this->ensureBodyIsOpened();
		$this->bodyListOpened = true;
		$this->htmlBody .= $this->listBegin;
	}

	protected function ensureBodyListClosed(): void {
		if (!$this->bodyListOpened) {
			return;
		}

		$this->bodyListOpened = false;
		$this->htmlBody .= $this->listEnd;
	}

	/**
	 * Adds a button group of two buttons to the body of the email
	 *
	 * @param string $textLeft Text of left button; Note: When $plainTextLeft falls back to this, HTML is automatically escaped in the HTML email
	 * @param string $urlLeft URL of left button
	 * @param string $textRight Text of right button; Note: When $plainTextRight falls back to this, HTML is automatically escaped in the HTML email
	 * @param string $urlRight URL of right button
	 * @param string $plainTextLeft Text of left button that is used in the plain text version - if unset the $textLeft is used
	 * @param string $plainTextRight Text of right button that is used in the plain text version - if unset the $textRight is used
	 */
	#[\Override]
	public function addBodyButtonGroup(
		string $textLeft,
		string $urlLeft,
		string $textRight,
		string $urlRight,
		string $plainTextLeft = '',
		string $plainTextRight = '',
	): void {
		if ($this->footerAdded) {
			return;
		}
		if ($plainTextLeft === '') {
			$plainTextLeft = $textLeft;
			$textLeft = htmlspecialchars($textLeft);
		}

		if ($plainTextRight === '') {
			$plainTextRight = $textRight;
			$textRight = htmlspecialchars($textRight);
		}

		$this->ensureBodyIsOpened();
		$this->ensureBodyListClosed();

		$color = $this->themingDefaults->getDefaultColorPrimary();
		$textColor = $this->themingDefaults->getDefaultTextColorPrimary();

		$this->htmlBody .= vsprintf($this->buttonGroup, [$color, $color, htmlspecialchars($urlLeft), $color, $textColor, $textColor, $textLeft, htmlspecialchars($urlRight), $textRight]);
		$this->plainBody .= PHP_EOL . $plainTextLeft . ': ' . $urlLeft . PHP_EOL;
		$this->plainBody .= $plainTextRight . ': ' . $urlRight . PHP_EOL . PHP_EOL;
	}

	/**
	 * Adds a button to the body of the email
	 *
	 * @param string $text Text of button; Note: When $plainText falls back to this, HTML is automatically escaped in the HTML email
	 * @param string $url URL of button
	 * @param string|false $plainText Text of button in plain text version
	 *                                if empty the $text is used, if false none will be used
	 *
	 * @since 12.0.0
	 */
	#[\Override]
	public function addBodyButton(string $text, string $url, $plainText = ''): void {
		if ($this->footerAdded) {
			return;
		}

		$this->ensureBodyIsOpened();
		$this->ensureBodyListClosed();

		if ($plainText === '') {
			$plainText = $text;
			$text = htmlspecialchars($text);
		}

		$color = $this->themingDefaults->getDefaultColorPrimary();
		$textColor = $this->themingDefaults->getDefaultTextColorPrimary();
		$this->htmlBody .= vsprintf($this->button, [$color, $color, htmlspecialchars($url), $color, $textColor, $textColor, $text]);

		if ($plainText !== false) {
			$this->plainBody .= $plainText . ': ';
		}

		$this->plainBody .= $url . PHP_EOL;
	}

	/**
	 * @param non-empty-list<array{text: string, url: string}> $buttons
	 */
	#[\Override]
	public function addBodyButtons(array $buttons, string $label = ''): void {
		if ($this->footerAdded) {
			return;
		}

		$this->ensureBodyIsOpened();
		$this->ensureBodyListClosed();

		$color = $this->themingDefaults->getDefaultColorPrimary();
		$textColor = $this->themingDefaults->getDefaultTextColorPrimary();

		$htmlButtons = [];
		foreach ($buttons as $index => $button) {
			$url = htmlspecialchars($button['url']);
			$text = htmlspecialchars($button['text']);
			$htmlButtons[] = $index === 0
				? vsprintf($this->buttonsPrimary, [$color, $url, $textColor, $text])
				: vsprintf($this->buttonsSecondary, [$url, $text]);
		}

		$htmlLabel = $label !== '' ? vsprintf($this->buttonsLabel, [htmlspecialchars($label)]) : '';
		$this->htmlBody .= vsprintf($this->buttonsBegin, [$htmlLabel]);
		$this->htmlBody .= implode('', $htmlButtons);
		$this->htmlBody .= $this->buttonsEnd;

		if ($label !== '') {
			$this->plainBody .= $label . PHP_EOL;
		}
		foreach ($buttons as $button) {
			$this->plainBody .= $button['text'] . ': ' . $button['url'] . PHP_EOL;
		}
		$this->plainBody .= PHP_EOL;
	}

	#[\Override]
	public function addBodySender(string $displayName, string $subline = ''): void {
		if ($this->footerAdded) {
			return;
		}

		$this->ensureBodyIsClosed();

		$htmlSubline = $subline !== '' ? vsprintf($this->senderSubline, [htmlspecialchars($subline)]) : '';
		$this->htmlBody .= vsprintf($this->sender, [$this->renderInitials($displayName), htmlspecialchars($displayName), $htmlSubline]);

		$this->plainBody .= $displayName;
		if ($subline !== '') {
			$this->plainBody .= ' (' . $subline . ')';
		}
		$this->plainBody .= PHP_EOL . PHP_EOL;
	}

	#[\Override]
	public function addBodyNote(string $text, string $label = '', string $type = IEMailTemplate::NOTE_NEUTRAL): void {
		if ($this->footerAdded) {
			return;
		}
		if (!isset($this->noteColors[$type])) {
			$type = IEMailTemplate::NOTE_NEUTRAL;
		}
		$colors = $this->noteColors[$type];

		$this->ensureBodyIsOpened();
		$this->ensureBodyListClosed();

		$htmlText = str_replace("\n", '<br/>', htmlspecialchars($text));
		if ($label !== '') {
			$htmlLabel = htmlspecialchars($label);
			$htmlText = $type === IEMailTemplate::NOTE_NEUTRAL
				? vsprintf($this->noteLabel, [$htmlLabel]) . $htmlText
				: '<strong>' . $htmlLabel . '</strong> ' . $htmlText;
		}
		$this->htmlBody .= vsprintf($this->note, [$colors['background'], $colors['border'], $htmlText, $type]);

		if ($type === IEMailTemplate::NOTE_NEUTRAL) {
			if ($label !== '') {
				$this->plainBody .= $label . PHP_EOL;
			}
			$this->plainBody .= '> ' . str_replace("\n", PHP_EOL . '> ', $text);
		} else {
			$this->plainBody .= $label !== '' ? $label . ' ' . $text : $text;
		}
		$this->plainBody .= PHP_EOL . PHP_EOL;
	}

	#[\Override]
	public function addBodyDetails(EMailDetails $details): void {
		if ($this->footerAdded) {
			return;
		}

		$this->ensureBodyIsOpened();
		$this->ensureBodyListClosed();

		$leading = '';
		$initialsName = $details->getInitialsName();
		$dateBadge = $details->getDateBadge();
		if ($initialsName !== null) {
			$leading = vsprintf($this->detailsLeading, [$this->renderInitials($initialsName)]);
		} elseif ($dateBadge !== null) {
			$leading = vsprintf($this->detailsLeading, [vsprintf($this->detailsDateBadge, [
				$this->themingDefaults->getDefaultColorPrimary(),
				$this->themingDefaults->getDefaultTextColorPrimary(),
				htmlspecialchars($dateBadge['month']),
				htmlspecialchars($dateBadge['day']),
			])]);
		}

		$subtitle = $details->getSubtitle();
		$htmlSubtitle = $subtitle !== '' ? vsprintf($this->senderSubline, [htmlspecialchars($subtitle)]) : '';
		$rows = $details->getRows();
		$separator = 'border-bottom:1px solid #e5e5e5;';

		$this->htmlBody .= vsprintf($this->detailsBegin, [$rows !== [] ? $separator : '', $leading, htmlspecialchars($details->getTitle()), $htmlSubtitle]);
		$this->plainBody .= $details->getTitle() . PHP_EOL;
		if ($subtitle !== '') {
			$this->plainBody .= $subtitle . PHP_EOL;
		}

		$linkColor = $this->themingDefaults->getDefaultColorPrimary();
		foreach ($rows as $index => $row) {
			$htmlParts = [];
			$plainParts = [];
			foreach ($row->getParts() as $part) {
				$text = htmlspecialchars($part['text']);
				switch ($part['type']) {
					case EMailDetailsRow::PART_LINK:
						$htmlParts[] = '<a class="nc-link" href="' . htmlspecialchars($part['url']) . '" style="color:' . $linkColor . '">' . $text . '</a>';
						$plainParts[] = $part['text'] === $part['url'] ? $part['url'] : $part['text'] . ' (' . $part['url'] . ')';
						break;
					case EMailDetailsRow::PART_MUTED:
						$htmlParts[] = '<span class="nc-muted" style="color:#767676">' . $text . '</span>';
						$plainParts[] = $part['text'];
						break;
					default:
						$htmlParts[] = $text;
						$plainParts[] = $part['text'];
				}
			}

			$this->htmlBody .= vsprintf($this->detailsRow, [
				$index > 0 ? 'border-top:1px solid #e5e5e5;' : '',
				htmlspecialchars($row->getLabel()),
				implode('<br>', $htmlParts),
			]);

			$plainLabel = '  ' . $row->getLabel() . ': ';
			$indent = PHP_EOL . str_repeat(' ', mb_strlen($plainLabel));
			$this->plainBody .= $plainLabel . implode($indent, $plainParts) . PHP_EOL;
		}

		$this->htmlBody .= $this->detailsEnd;
		$this->plainBody .= PHP_EOL;
	}

	/**
	 * Initials circle in the theming colors, same letters as the generated avatars
	 */
	protected function renderInitials(string $name): string {
		$name = trim($name);
		$initials = '?';
		if ($name !== '') {
			$initials = implode('', array_map(
				static fn (string $namePart): string => mb_strtoupper(mb_substr(trim($namePart), 0, 1, 'UTF-8'), 'UTF-8'),
				explode(' ', $name, 2),
			));
		}

		return vsprintf($this->initials, [
			$this->themingDefaults->getDefaultColorPrimary(),
			$this->themingDefaults->getDefaultTextColorPrimary(),
			htmlspecialchars($initials),
		]);
	}

	/**
	 * Close the HTML body when it is open
	 */
	protected function ensureBodyIsClosed(): void {
		if (!$this->bodyOpened) {
			return;
		}

		$this->ensureBodyListClosed();

		$this->htmlBody .= $this->bodyEnd;
		$this->bodyOpened = false;
	}

	/**
	 * Adds a logo and a text to the footer. <br> in the text will be replaced by new lines in the plain text email
	 *
	 * @param string $text If the text is empty the default "Name - Slogan<br>This is an automatically sent email" will be used
	 */
	#[\Override]
	public function addFooter(string $text = '', ?string $lang = null): void {
		if ($text === '') {
			$l10n = $this->l10nFactory->get('lib', $lang);
			$slogan = $this->themingDefaults->getSlogan($lang);
			if ($slogan !== '') {
				$slogan = ' - ' . $slogan;
			}
			$text = $this->themingDefaults->getName() . $slogan . '<br>' . $l10n->t('This is an automatically sent email, please do not reply.');
		}

		if ($this->footerAdded) {
			return;
		}
		$this->footerAdded = true;

		$this->ensureBodyIsClosed();

		$this->htmlBody .= vsprintf($this->footer, [$text]);
		$this->htmlBody .= $this->tail;
		$this->plainBody .= PHP_EOL . '-- ' . PHP_EOL;
		$this->plainBody .= str_replace('<br>', PHP_EOL, $text);
	}

	/**
	 * Returns the rendered email subject as string
	 */
	#[\Override]
	public function renderSubject(): string {
		return $this->subject;
	}

	/**
	 * Returns the rendered HTML email as string
	 */
	#[\Override]
	public function renderHtml(): string {
		if (!$this->footerAdded) {
			$this->footerAdded = true;
			$this->ensureBodyIsClosed();
			$this->htmlBody .= $this->tail;
		}
		return $this->htmlBody;
	}

	/**
	 * Returns the rendered plain text email as string
	 */
	#[\Override]
	public function renderText(): string {
		if (!$this->footerAdded) {
			$this->footerAdded = true;
			$this->ensureBodyIsClosed();
			$this->htmlBody .= $this->tail;
		}
		return $this->plainBody;
	}
}
