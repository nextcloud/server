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
	/** language of the recipient, set by setLanguage */
	protected ?string $language = null;
	/** name of the person the email is sent on behalf of, set by addBodySender */
	protected ?string $senderName = null;
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
			.nc-page-pad{padding:0!important}
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
			.nc-note-info{background:#003553!important;border-left-color:#00AEFF!important}
			.nc-note-warning{background:#3D3010!important;border-left-color:#FFEEC5!important}
			.nc-note-error{background:#552121!important;border-left-color:#FFCCCC!important}
		}
	</style>
</head>
<body class="nc-page" style="-moz-box-sizing:border-box;-ms-text-size-adjust:100%;-webkit-box-sizing:border-box;-webkit-text-size-adjust:100%;Margin:0;background:#f4f4f5;box-sizing:border-box;margin:0;min-width:100%;padding:0;width:100%!important">
<table role="presentation" class="nc-page" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;border-collapse:collapse;border-spacing:0;width:100%">
	<tr>
		<td class="nc-page-pad" align="center" style="padding:32px 0">
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
								<td class="nc-note-%4\$s nc-text" style="background:%1\$s;border-left:4px solid %2\$s;border-radius:4px;color:#222222;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;line-height:1.5;padding:16px 20px">%3\$s</td>
							</tr>
						</table>
EOF;

	protected string $noteLabel = <<<EOF
<span class="nc-muted" style="color:#767676;font-size:14px">%s</span><br>
EOF;

	/** @var array<string, array{background: string, border: string}> */
	protected array $noteColors = [
		IEMailTemplate::NOTE_NEUTRAL => ['background' => '#f4f4f5', 'border' => 'transparent'],
		IEMailTemplate::NOTE_INFO => ['background' => '#D5F1FA', 'border' => '#0066AC'],
		IEMailTemplate::NOTE_WARNING => ['background' => '#FFEEC5', 'border' => '#664700'],
		IEMailTemplate::NOTE_ERROR => ['background' => '#FFE7E7', 'border' => '#8A0000'],
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

	/** @var list<EMailTemplateBlock> parts of the email, rendered by renderHtml and renderText */
	protected array $blocks = [];

	public function __construct(
		protected Defaults $themingDefaults,
		protected IURLGenerator $urlGenerator,
		protected IFactory $l10nFactory,
		protected ?int $logoWidth,
		protected ?int $logoHeight,
		protected string $emailId,
		protected array $data,
	) {
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
		$this->addBlock(EMailTemplateBlock::HEADER, [
			'color' => $this->themingDefaults->getDefaultColorPrimary(),
			'logo' => $logoSrc,
			'name' => $this->themingDefaults->getName(),
			'dimensions' => $logoSizeDimensions,
		]);
	}

	#[\Override]
	public function setLanguage(string $language): void {
		if ($language !== '') {
			$this->language = $language;
		}
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

		$this->addBlock(
			EMailTemplateBlock::HEADING,
			['title' => htmlspecialchars($title)],
			$plainTitle !== false ? $plainTitle . PHP_EOL . PHP_EOL : '',
		);
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

		$this->addBlock(
			EMailTemplateBlock::TEXT,
			['text' => $text],
			$plainText !== false ? $plainText . PHP_EOL . PHP_EOL : '',
		);
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

		$plain = '';
		if ($plainText !== false) {
			if ($plainIndent === 0) {
				/*
				 * If plainIndent is not set by caller, this is the old NC17 layout code.
				 */
				$plain = '  * ' . $plainText;
				if ($plainMetaInfo !== false) {
					$plain .= ' (' . $plainMetaInfo . ')';
				}
				$plain .= PHP_EOL;
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
				$plain = sprintf("%{$plainIndent}s %s\n",
					$label,
					str_replace("\n", "\n" . str_repeat(' ', $plainIndent + 1), $plainText));
			}
		}

		$this->addBlock(EMailTemplateBlock::LIST_ITEM, ['icon' => $icon, 'text' => $htmlText], $plain);
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

		$this->addBlock(
			EMailTemplateBlock::BUTTON_GROUP,
			[
				'color' => $this->themingDefaults->getDefaultColorPrimary(),
				'textColor' => $this->themingDefaults->getDefaultTextColorPrimary(),
				'urlLeft' => htmlspecialchars($urlLeft),
				'textLeft' => $textLeft,
				'urlRight' => htmlspecialchars($urlRight),
				'textRight' => $textRight,
			],
			PHP_EOL . $plainTextLeft . ': ' . $urlLeft . PHP_EOL
				. $plainTextRight . ': ' . $urlRight . PHP_EOL . PHP_EOL,
		);
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

		if ($plainText === '') {
			$plainText = $text;
			$text = htmlspecialchars($text);
		}

		$this->addBlock(
			EMailTemplateBlock::BUTTON,
			[
				'color' => $this->themingDefaults->getDefaultColorPrimary(),
				'textColor' => $this->themingDefaults->getDefaultTextColorPrimary(),
				'url' => htmlspecialchars($url),
				'text' => $text,
			],
			($plainText !== false ? $plainText . ': ' : '') . $url . PHP_EOL,
		);
	}

	/**
	 * @param non-empty-list<array{text: string, url: string}> $buttons
	 */
	#[\Override]
	public function addBodyButtons(array $buttons, string $label = ''): void {
		if ($this->footerAdded) {
			return;
		}

		$plain = $label !== '' ? $label . PHP_EOL : '';
		foreach ($buttons as $button) {
			$plain .= $button['text'] . ': ' . $button['url'] . PHP_EOL;
		}

		$this->addBlock(
			EMailTemplateBlock::BUTTONS,
			[
				'color' => $this->themingDefaults->getDefaultColorPrimary(),
				'textColor' => $this->themingDefaults->getDefaultTextColorPrimary(),
				'buttons' => array_map(static fn (array $button): array => [
					'text' => htmlspecialchars($button['text']),
					'url' => htmlspecialchars($button['url']),
				], $buttons),
				'label' => htmlspecialchars($label),
			],
			$plain . PHP_EOL,
		);
	}

	#[\Override]
	public function addBodySender(string $displayName, string $subline = ''): void {
		if ($this->footerAdded) {
			return;
		}
		$this->senderName = $displayName;

		$this->addBlock(
			EMailTemplateBlock::SENDER,
			[
				'initials' => $this->getInitialsValues($displayName),
				'name' => htmlspecialchars($displayName),
				'subline' => htmlspecialchars($subline),
			],
			$displayName . ($subline !== '' ? ' (' . $subline . ')' : '') . PHP_EOL . PHP_EOL,
		);
	}

	#[\Override]
	public function addBodyNote(string $text, string $label = '', string $type = IEMailTemplate::NOTE_NEUTRAL): void {
		if ($this->footerAdded) {
			return;
		}
		if (!isset($this->noteColors[$type])) {
			$type = IEMailTemplate::NOTE_NEUTRAL;
		}

		if ($type === IEMailTemplate::NOTE_NEUTRAL) {
			$plain = ($label !== '' ? $label . PHP_EOL : '') . '> ' . str_replace("\n", PHP_EOL . '> ', $text);
		} else {
			$plain = $label !== '' ? $label . ' ' . $text : $text;
		}

		$this->addBlock(
			EMailTemplateBlock::NOTE,
			[
				'type' => $type,
				'text' => str_replace("\n", '<br/>', htmlspecialchars($text)),
				'label' => htmlspecialchars($label),
			],
			$plain . PHP_EOL . PHP_EOL,
		);
	}

	#[\Override]
	public function addBodyDetails(EMailDetails $details): void {
		if ($this->footerAdded) {
			return;
		}

		$initialsName = $details->getInitialsName();
		$dateBadge = $details->getDateBadge();
		$leading = null;
		if ($initialsName !== null) {
			$leading = ['initials' => $this->getInitialsValues($initialsName)];
		} elseif ($dateBadge !== null) {
			$leading = ['badge' => [
				'color' => $this->themingDefaults->getDefaultColorPrimary(),
				'textColor' => $this->themingDefaults->getDefaultTextColorPrimary(),
				'month' => htmlspecialchars($dateBadge['month']),
				'day' => htmlspecialchars($dateBadge['day']),
			]];
		}

		$subtitle = $details->getSubtitle();
		$plain = $details->getTitle() . PHP_EOL . ($subtitle !== '' ? $subtitle . PHP_EOL : '');

		$linkColor = $this->themingDefaults->getDefaultColorPrimary();
		$rows = [];
		foreach ($details->getRows() as $row) {
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
			$rows[] = ['label' => htmlspecialchars($row->getLabel()), 'value' => implode('<br>', $htmlParts)];

			$plainLabel = '  ' . $row->getLabel() . ': ';
			$indent = PHP_EOL . str_repeat(' ', mb_strlen($plainLabel));
			$plain .= $plainLabel . implode($indent, $plainParts) . PHP_EOL;
		}

		$this->addBlock(
			EMailTemplateBlock::DETAILS,
			[
				'leading' => $leading,
				'title' => htmlspecialchars($details->getTitle()),
				'subtitle' => htmlspecialchars($subtitle),
				'rows' => $rows,
			],
			$plain . PHP_EOL,
		);
	}

	/**
	 * Values of the initials circle in the theming colors, same letters as the generated avatars
	 *
	 * @return array{color: string, textColor: string, initials: string}
	 */
	protected function getInitialsValues(string $name): array {
		$name = trim($name);
		$initials = '?';
		if ($name !== '') {
			$initials = implode('', array_map(
				static fn (string $namePart): string => mb_strtoupper(mb_substr(trim($namePart), 0, 1, 'UTF-8'), 'UTF-8'),
				explode(' ', $name, 2),
			));
		}

		return [
			'color' => $this->themingDefaults->getDefaultColorPrimary(),
			'textColor' => $this->themingDefaults->getDefaultTextColorPrimary(),
			'initials' => htmlspecialchars($initials),
		];
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
		$lang ??= $this->language;
		$plainText = null;
		if ($text === '') {
			$l10n = $this->l10nFactory->get('lib', $lang);
			$slogan = $this->themingDefaults->getSlogan($lang);
			if ($slogan !== '') {
				$slogan = ' - ' . $slogan;
			}
			$text = $this->themingDefaults->getName() . $slogan . '<br>';
			if ($this->senderName !== null) {
				$url = $this->urlGenerator->getAbsoluteURL('/');
				$host = parse_url($url, PHP_URL_HOST) ?: $url;
				$link = '<a class="nc-link" href="' . htmlspecialchars($url) . '" style="color:inherit">' . htmlspecialchars($host) . '</a>';
				$text .= $l10n->t('This email was sent from %1$s on behalf of %2$s.', [$link, htmlspecialchars($this->senderName)]);
				$plainText = $this->themingDefaults->getName() . $slogan . PHP_EOL
					. $l10n->t('This email was sent from %1$s on behalf of %2$s.', [$host, $this->senderName]);
			} else {
				$text .= $l10n->t('This is an automatically sent email, please do not reply.');
			}
		}

		if ($this->footerAdded) {
			return;
		}
		$this->footerAdded = true;

		$this->addBlock(
			EMailTemplateBlock::FOOTER,
			['text' => $text],
			PHP_EOL . '-- ' . PHP_EOL . ($plainText ?? str_replace('<br>', PHP_EOL, $text)),
		);
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
		$this->closeEmail();

		$this->htmlBody = $this->head;
		$this->bodyOpened = false;
		$this->bodyListOpened = false;
		foreach ($this->blocks as $block) {
			$this->renderBlockHtml($block);
		}
		$html = $this->htmlBody;
		$this->htmlBody = '';

		if ($this->language === null) {
			return $html;
		}

		$direction = $this->l10nFactory->getLanguageDirection($this->language);
		$lang = htmlspecialchars(str_replace('_', '-', $this->language));
		$html = str_replace('lang="en" xml:lang="en"', 'lang="' . $lang . '" xml:lang="' . $lang . '" dir="' . $direction . '"', $html);
		return $direction === 'rtl' ? $this->mirrorStyles($html) : $html;
	}

	/**
	 * Swap left and right in the template's own styles: the style attributes
	 * and the style element. Text content is never touched.
	 */
	protected function mirrorStyles(string $html): string {
		$mirror = static function (array $match): string {
			// 4-value margin and padding shorthands are top, right, bottom, left
			$css = preg_replace('/\\b(margin|padding):([^\\s;"!]+) ([^\\s;"!]+) ([^\\s;"!]+) ([^\\s;"!]+)/', '$1:$2 $5 $4 $3', $match[2]) ?? $match[2];
			return $match[1] . strtr($css, ['left' => 'right', 'right' => 'left']) . $match[3];
		};
		$html = preg_replace_callback('/(style=")([^"]*)(")/', $mirror, $html) ?? $html;
		return preg_replace_callback('/(<style[^>]*>)(.*?)(<\\/style>)/s', $mirror, $html) ?? $html;
	}

	/**
	 * Returns the rendered plain text email as string
	 */
	#[\Override]
	public function renderText(): string {
		$this->closeEmail();
		return implode('', array_map(static fn (EMailTemplateBlock $block): string => $block->text, $this->blocks));
	}

	/**
	 * Record a block, after any HTML or text a subclass wrote directly
	 *
	 * @param EMailTemplateBlock::* $type
	 * @param array<string, mixed> $values
	 */
	protected function addBlock(string $type, array $values = [], string $text = ''): void {
		$this->addRawBlock();
		$this->blocks[] = new EMailTemplateBlock($type, $values, $text);
	}

	/**
	 * Keep what a subclass appended to htmlBody or plainBody, in order
	 */
	private function addRawBlock(): void {
		if ($this->htmlBody === '' && $this->plainBody === '') {
			return;
		}
		$this->blocks[] = new EMailTemplateBlock(EMailTemplateBlock::RAW, ['html' => $this->htmlBody], $this->plainBody);
		$this->htmlBody = '';
		$this->plainBody = '';
	}

	/**
	 * Ends the email when it is rendered without a footer, nothing can be added afterwards
	 */
	private function closeEmail(): void {
		$this->addRawBlock();
		if (!$this->footerAdded) {
			$this->footerAdded = true;
			$this->blocks[] = new EMailTemplateBlock(EMailTemplateBlock::END);
		}
	}

	private function renderBlockHtml(EMailTemplateBlock $block): void {
		$values = $block->values;
		switch ($block->type) {
			case EMailTemplateBlock::HEADER:
				$this->htmlBody .= vsprintf($this->header, [$values['color'], $values['logo'], $values['name'], $values['dimensions']]);
				break;
			case EMailTemplateBlock::HEADING:
				$this->ensureBodyIsClosed();
				$this->htmlBody .= vsprintf($this->heading, [$values['title']]);
				break;
			case EMailTemplateBlock::TEXT:
				$this->ensureBodyListClosed();
				$this->ensureBodyIsOpened();
				$this->htmlBody .= vsprintf($this->bodyText, [$values['text']]);
				break;
			case EMailTemplateBlock::LIST_ITEM:
				$this->ensureBodyListOpened();
				$this->htmlBody .= vsprintf($this->listItem, [$values['icon'], $values['text']]);
				break;
			case EMailTemplateBlock::BUTTON_GROUP:
				$this->ensureBodyIsOpened();
				$this->ensureBodyListClosed();
				$this->htmlBody .= vsprintf($this->buttonGroup, [
					$values['color'], $values['color'], $values['urlLeft'], $values['color'], $values['textColor'],
					$values['textColor'], $values['textLeft'], $values['urlRight'], $values['textRight'],
				]);
				break;
			case EMailTemplateBlock::BUTTON:
				$this->ensureBodyIsOpened();
				$this->ensureBodyListClosed();
				$this->htmlBody .= vsprintf($this->button, [
					$values['color'], $values['color'], $values['url'], $values['color'], $values['textColor'],
					$values['textColor'], $values['text'],
				]);
				break;
			case EMailTemplateBlock::BUTTONS:
				$this->ensureBodyIsOpened();
				$this->ensureBodyListClosed();
				$this->htmlBody .= $this->renderButtons($values);
				break;
			case EMailTemplateBlock::SENDER:
				$this->ensureBodyIsClosed();
				$subline = $values['subline'] !== '' ? vsprintf($this->senderSubline, [$values['subline']]) : '';
				$this->htmlBody .= vsprintf($this->sender, [$this->renderInitials($values['initials']), $values['name'], $subline]);
				break;
			case EMailTemplateBlock::NOTE:
				$this->ensureBodyIsOpened();
				$this->ensureBodyListClosed();
				$this->htmlBody .= $this->renderNote($values);
				break;
			case EMailTemplateBlock::DETAILS:
				$this->ensureBodyIsOpened();
				$this->ensureBodyListClosed();
				$this->htmlBody .= $this->renderDetails($values);
				break;
			case EMailTemplateBlock::FOOTER:
				$this->ensureBodyIsClosed();
				$this->htmlBody .= vsprintf($this->footer, [$values['text']]);
				$this->htmlBody .= $this->tail;
				break;
			case EMailTemplateBlock::END:
				$this->ensureBodyIsClosed();
				$this->htmlBody .= $this->tail;
				break;
			case EMailTemplateBlock::RAW:
				$this->htmlBody .= $values['html'];
				break;
		}
	}

	/**
	 * @param array{color: string, textColor: string, initials: string} $values
	 */
	protected function renderInitials(array $values): string {
		return vsprintf($this->initials, [$values['color'], $values['textColor'], $values['initials']]);
	}

	/**
	 * @param array<string, mixed> $values
	 */
	private function renderButtons(array $values): string {
		$htmlButtons = [];
		foreach ($values['buttons'] as $index => $button) {
			$htmlButtons[] = $index === 0
				? vsprintf($this->buttonsPrimary, [$values['color'], $button['url'], $values['textColor'], $button['text']])
				: vsprintf($this->buttonsSecondary, [$button['url'], $button['text']]);
		}
		$label = $values['label'] !== '' ? vsprintf($this->buttonsLabel, [$values['label']]) : '';
		return vsprintf($this->buttonsBegin, [$label]) . implode('', $htmlButtons) . $this->buttonsEnd;
	}

	/**
	 * @param array<string, mixed> $values
	 */
	private function renderNote(array $values): string {
		$text = $values['text'];
		if ($values['label'] !== '') {
			$text = $values['type'] === IEMailTemplate::NOTE_NEUTRAL
				? vsprintf($this->noteLabel, [$values['label']]) . $text
				: '<strong>' . $values['label'] . '</strong> ' . $text;
		}
		$colors = $this->noteColors[$values['type']];
		return vsprintf($this->note, [$colors['background'], $colors['border'], $text, $values['type']]);
	}

	/**
	 * @param array<string, mixed> $values
	 */
	private function renderDetails(array $values): string {
		$leading = '';
		if (isset($values['leading']['initials'])) {
			$leading = vsprintf($this->detailsLeading, [$this->renderInitials($values['leading']['initials'])]);
		} elseif (isset($values['leading']['badge'])) {
			$badge = $values['leading']['badge'];
			$leading = vsprintf($this->detailsLeading, [vsprintf($this->detailsDateBadge, [$badge['color'], $badge['textColor'], $badge['month'], $badge['day']])]);
		}

		$subtitle = $values['subtitle'] !== '' ? vsprintf($this->senderSubline, [$values['subtitle']]) : '';
		$html = vsprintf($this->detailsBegin, [$values['rows'] !== [] ? 'border-bottom:1px solid #e5e5e5;' : '', $leading, $values['title'], $subtitle]);
		foreach ($values['rows'] as $index => $row) {
			$html .= vsprintf($this->detailsRow, [$index > 0 ? 'border-top:1px solid #e5e5e5;' : '', $row['label'], $row['value']]);
		}
		return $html . $this->detailsEnd;
	}
}
