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
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" style="-webkit-font-smoothing:antialiased;background:#fff!important">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width">
	<title></title>
	<style type="text/css">@media only screen{html{min-height:100%;background:#fff}}@media only screen and (max-width:610px){table.body img{width:auto;height:auto}table.body center{min-width:0!important}table.body .container{width:95%!important}table.body .columns{height:auto!important;-moz-box-sizing:border-box;-webkit-box-sizing:border-box;box-sizing:border-box;padding-left:30px!important;padding-right:30px!important}th.small-12{display:inline-block!important;width:100%!important}table.menu{width:100%!important}table.menu td,table.menu th{width:auto!important;display:inline-block!important}table.menu.vertical td,table.menu.vertical th{display:block!important}table.menu[align=center]{width:auto!important}}</style>
</head>
<body style="-moz-box-sizing:border-box;-ms-text-size-adjust:100%;-webkit-box-sizing:border-box;-webkit-font-smoothing:antialiased;-webkit-text-size-adjust:100%;margin:0;background:#fff!important;box-sizing:border-box;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;min-width:100%;padding:0;text-align:left;width:100%!important">
	<span class="preheader" style="color:#F5F5F5;display:none!important;font-size:1px;line-height:1px;max-height:0;max-width:0;mso-hide:all!important;opacity:0;overflow:hidden;visibility:hidden">
	</span>
	<table class="body" style="-webkit-font-smoothing:antialiased;margin:0;background:#fff;border-collapse:collapse;border-spacing:0;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;padding:0;text-align:left;vertical-align:top;width:100%">
		<tr style="padding:0;text-align:left;vertical-align:top">
			<td class="center" align="center" valign="top" style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:1.3;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
				<center data-parsed="" style="min-width:580px;width:100%">
EOF;

	protected string $tail = <<<EOF
					</center>
				</td>
			</tr>
		</table>
		<!-- prevent Gmail on iOS font size manipulation -->
		<div style="display:none;white-space:nowrap;font:15px courier;line-height:0">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</div>
	</body>
</html>

EOF;

	protected string $header = <<<EOF
<table align="center" class="wrapper header float-center" style="Margin:0 auto;background:#fff;border-collapse:collapse;border-spacing:0;float:none;margin:0 auto;padding:0;text-align:center;vertical-align:top;width:100%%">
	<tr style="padding:0;text-align:left;vertical-align:top">
		<td class="wrapper-inner" style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:1.3;margin:0;padding:20px;text-align:left;vertical-align:top;word-wrap:break-word">
			<table align="center" class="container" style="Margin:0 auto;background:0 0;border-collapse:collapse;border-spacing:0;margin:0 auto;padding:0;text-align:inherit;vertical-align:top;width:150px">
				<tbody>
				<tr style="padding:0;text-align:left;vertical-align:top">
					<td style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:1.3;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
						<table class="row collapse" style="border-collapse:collapse;border-spacing:0;display:table;padding:0;position:relative;text-align:left;vertical-align:top;width:100%%">
							<tbody>
							<tr style="padding:0;text-align:left;vertical-align:top">
								<center data-parsed="" style="background-color:%s;min-width:175px;max-height:175px; padding:35px 0px;border-radius:200px">
									<img class="logo float-center" src="%s" alt="%s" align="center" style="-ms-interpolation-mode:bicubic;clear:both;display:block;float:none;margin:0 auto;outline:0;text-align:center;text-decoration:none;max-height:105px;max-width:105px;width:auto;height:auto"%s>
								</center>
							</tr>
							</tbody>
						</table>
					</td>
				</tr>
				</tbody>
			</table>
		</td>
	</tr>
</table>
<table class="spacer float-center" style="Margin:0 auto;border-collapse:collapse;border-spacing:0;float:none;margin:0 auto;padding:0;text-align:center;vertical-align:top;width:100%%">
	<tbody>
	<tr style="padding:0;text-align:left;vertical-align:top">
		<td height="40px" style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-size:80px;font-weight:400;hyphens:auto;line-height:80px;margin:0;mso-line-height-rule:exactly;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">&#xA0;</td>
	</tr>
	</tbody>
</table>
EOF;

	protected string $heading = <<<EOF
<table align="center" class="container main-heading float-center" style="Margin:0 auto;background:0 0!important;border-collapse:collapse;border-spacing:0;float:none;margin:0 auto;padding:0;text-align:center;vertical-align:top;width:580px">
	<tbody>
	<tr style="padding:0;text-align:left;vertical-align:top">
		<td style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:1.3;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
			<h1 class="text-center" style="Margin:0;Margin-bottom:10px;color:inherit;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:24px;font-weight:400;line-height:1.3;margin:0;padding:0;text-align:center;word-wrap:normal">%s</h1>
		</td>
	</tr>
	</tbody>
</table>
<table class="spacer float-center" style="Margin:0 auto;border-collapse:collapse;border-spacing:0;float:none;margin:0 auto;padding:0;text-align:center;vertical-align:top;width:100%%">
	<tbody>
	<tr style="padding:0;text-align:left;vertical-align:top">
		<td height="36px" style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-size:40px;font-weight:400;hyphens:auto;line-height:36px;margin:0;mso-line-height-rule:exactly;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">&#xA0;</td>
	</tr>
	</tbody>
</table>
EOF;

	protected string $bodyBegin = <<<EOF
<table align="center" class="wrapper content float-center" style="Margin:0 auto;border-collapse:collapse;border-spacing:0;float:none;margin:0 auto;padding:0;text-align:center;vertical-align:top;width:100%">
	<tr style="padding:0;text-align:left;vertical-align:top">
		<td class="wrapper-inner" style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:1.3;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
			<table align="center" class="container" style="Margin:0 auto;background:#fff;border-collapse:collapse;border-spacing:0;margin:0 auto;padding:0;text-align:inherit;vertical-align:top;width:580px">
				<tbody>
				<tr style="padding:0;text-align:left;vertical-align:top">
					<td style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:1.3;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
EOF;

	protected string $bodyText = <<<EOF
<table class="row description" style="border-collapse:collapse;border-spacing:0;display:table;padding:0;position:relative;text-align:left;vertical-align:top;width:100%%">
	<tbody>
	<tr style="padding:0;text-align:left;vertical-align:top">
		<th class="small-12 large-12 columns first last" style="Margin:0 auto;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0 auto;padding:0;padding-bottom:30px;padding-left:30px;padding-right:30px;text-align:left;width:550px">
			<table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%%">
				<tr style="padding:0;text-align:left;vertical-align:top">
					<th style="Margin:0;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;padding:0;text-align:left">
						<p style="Margin:0;Margin-bottom:10px;color:#777;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;margin-bottom:10px;padding:0;text-align:center">%s</p>
					</th>
					<th class="expander" style="Margin:0;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;padding:0!important;text-align:left;visibility:hidden;width:0"></th>
				</tr>
			</table>
		</th>
	</tr>
	</tbody>
</table>
EOF;

	// note: listBegin (like bodyBegin) is not processed through sprintf, so "%" is not escaped as "%%". (bug #12151)
	protected string $listBegin = <<<EOF
<table class="row description" style="border-collapse:collapse;border-spacing:0;display:table;padding:0;position:relative;text-align:left;vertical-align:top;width:100%">
	<tbody>
	<tr style="padding:0;text-align:left;vertical-align:top">
		<th class="small-12 large-12 columns first last" style="Margin:0 auto;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0 auto;padding:0;padding-bottom:30px;padding-left:30px;padding-right:30px;text-align:left;width:550px">
			<table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%">
EOF;

	protected string $listItem = <<<EOF
				<tr style="padding:0;text-align:left;vertical-align:top">
					<td style="Margin:0;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;padding:0;text-align:left;width:15px;">
						<p class="text-left" style="Margin:0;Margin-bottom:10px;color:#777;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;margin-bottom:10px;padding:0;padding-left:10px;text-align:left">%s</p>
					</td>
					<td style="Margin:0;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;padding:0;text-align:left">
						<p class="text-left" style="Margin:0;Margin-bottom:10px;color:#555;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;margin-bottom:10px;padding:0;padding-left:10px;text-align:left">%s</p>
					</td>
					<td class="expander" style="Margin:0;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;padding:0!important;text-align:left;visibility:hidden;width:0"></td>
				</tr>
EOF;

	protected string $listEnd = <<<EOF
			</table>
		</th>
	</tr>
	</tbody>
</table>
EOF;

	protected string $buttonGroup = <<<EOF
<table class="spacer" style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%%">
	<tbody>
	<tr style="padding:0;text-align:left;vertical-align:top">
		<td height="50px" style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:50px;font-weight:400;hyphens:auto;line-height:50px;margin:0;mso-line-height-rule:exactly;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">&#xA0;</td>
	</tr>
	</tbody>
</table>
<table align="center" class="row btn-group" style="border-collapse:collapse;border-spacing:0;display:table;padding:0;position:relative;text-align:left;vertical-align:top;width:100%%">
	<tbody>
	<tr style="padding:0;text-align:left;vertical-align:top">
		<th class="small-12 large-12 columns first last" style="Margin:0 auto;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0 auto;padding:0;padding-bottom:30px;padding-left:30px;padding-right:30px;text-align:left;width:550px">
			<table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%%">
				<tr style="padding:0;text-align:left;vertical-align:top">
					<th style="Margin:0;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;padding:0;text-align:left">
						<center data-parsed="" style="min-width:490px;width:100%%">
							<!--[if (gte mso 9)|(IE)]>
							<table>
								<tr>
									<td>
									<![endif]-->
										<table class="button btn default primary float-center" style="Margin:0 0 30px 0;border-collapse:collapse;border-spacing:0;display:inline-block;float:none;margin:0 0 30px 0;margin-right:15px;border-radius:8px;max-width:300px;padding:0;text-align:center;vertical-align:top;width:auto;background:%1\$s;background-color:%1\$s;color:#fefefe;">
											<tr style="padding:0;text-align:left;vertical-align:top">
												<td style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:normal;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
													<table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%%">
														<tr style="padding:0;text-align:left;vertical-align:top">
															<td style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border:0 solid %2\$s;border-collapse:collapse!important;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:normal;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
																<a href="%3\$s" style="Margin:0;border:0 solid %4\$s;color:%5\$s;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:regular;line-height:normal;margin:0;padding:8px;text-align:left;outline:1px solid %6\$s;text-decoration:none">%7\$s</a>
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
									<!--[if (gte mso 9)|(IE)]>
									</td>
									<td>
									<![endif]-->
										<table class="button btn default secondary float-center" style="Margin:0 0 30px 0;border-collapse:collapse;border-spacing:0;display:inline-block;float:none;background-color: #ccc;margin:0 0 30px 0;max-height:40px;max-width:300px;padding:1px;border-radius:8px;text-align:center;vertical-align:top;width:auto">
											<tr style="padding:0;text-align:left;vertical-align:top">
												<td style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:normal;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
													<table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%%">
														<tr style="padding:0;text-align:left;vertical-align:top">
															<td style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border:0 solid #777;border-collapse:collapse!important;color:#fefefe;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:normal;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
																<a href="%8\$s" style="Margin:0;background-color:#fff;border:0 solid #777;color:#6C6C6C!important;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:regular;line-height:normal;margin:0;border-radius: 7px;padding:8px;text-align:left;text-decoration:none">%9\$s</a>
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
									<!--[if (gte mso 9)|(IE)]>
									</td>
								</tr>
							</table>
							<![endif]-->
						</center>
					</th>
					<th class="expander" style="Margin:0;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;padding:0!important;text-align:left;visibility:hidden;width:0"></th>
				</tr>
			</table>
		</th>
	</tr>
	</tbody>
</table>
EOF;

	protected string $button = <<<EOF
<table class="spacer" style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%%">
	<tbody>
	<tr style="padding:0;text-align:left;vertical-align:top">
		<td height="50px" style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:50px;font-weight:400;hyphens:auto;line-height:50px;margin:0;mso-line-height-rule:exactly;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">&#xA0;</td>
	</tr>
	</tbody>
</table>
<table align="center" class="row btn-group" style="border-collapse:collapse;border-spacing:0;display:table;padding:0;position:relative;text-align:left;vertical-align:top;width:100%%">
	<tbody>
	<tr style="padding:0;text-align:left;vertical-align:top">
		<th class="small-12 large-12 columns first last" style="Margin:0 auto;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0 auto;padding:0;padding-bottom:30px;padding-left:30px;padding-right:30px;text-align:left;width:550px">
			<table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%%">
				<tr style="padding:0;text-align:left;vertical-align:top">
					<th style="Margin:0;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;padding:0;text-align:left">
						<center data-parsed="" style="min-width:490px;width:100%%">
							<table class="button btn default primary float-center" style="Margin:0;border-collapse:collapse;border-spacing:0;display:inline-block;float:none;margin:0;border-radius:8px;padding:0;text-align:center;vertical-align:top;width:auto;background:%1\$s;color:#fefefe;background-color:%1\$s;">
								<tr style="padding:0;text-align:left;vertical-align:top">
									<td style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:normal;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
										<table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%%">
											<tr style="padding:0;text-align:left;vertical-align:top">
												<td style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border:0 solid %2\$s;border-collapse:collapse!important;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:normal;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
													<a href="%3\$s" style="Margin:0;border:0 solid %4\$s;color:%5\$s;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:regular;line-height:normal;margin:0;padding:8px;text-align:left;outline:1px solid %5\$s;text-decoration:none">%7\$s</a>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</center>
					</th>
					<th class="expander" style="Margin:0;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;padding:0!important;text-align:left;visibility:hidden;width:0"></th>
				</tr>
			</table>
		</th>
	</tr>
	</tbody>
</table>
EOF;

	protected string $bodyEnd = <<<EOF

					</td>
				</tr>
				</tbody>
			</table>
		</td>
	</tr>
</table>
EOF;

	protected string $footer = <<<EOF
<table class="spacer float-center" style="Margin:0 auto;border-collapse:collapse;border-spacing:0;float:none;margin:0 auto;padding:0;text-align:center;vertical-align:top;width:100%%">
	<tbody>
	<tr style="padding:0;text-align:left;vertical-align:top">
		<td height="60px" style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:60px;font-weight:400;hyphens:auto;line-height:60px;margin:0;mso-line-height-rule:exactly;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">&#xA0;</td>
	</tr>
	</tbody>
</table>
<table align="center" class="wrapper footer float-center" style="Margin:0 auto;border-collapse:collapse;border-spacing:0;float:none;margin:0 auto;padding:0;text-align:center;vertical-align:top;width:100%%">
	<tr style="padding:0;text-align:left;vertical-align:top">
		<td class="wrapper-inner" style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:1.3;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
			<center data-parsed="" style="min-width:580px;width:100%%">
				<table class="spacer float-center" style="Margin:0 auto;border-collapse:collapse;border-spacing:0;float:none;margin:0 auto;padding:0;text-align:center;vertical-align:top;width:100%%">
					<tbody>
					<tr style="padding:0;text-align:left;vertical-align:top">
						<td height="15px" style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:15px;font-weight:400;hyphens:auto;line-height:15px;margin:0;mso-line-height-rule:exactly;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">&#xA0;</td>
					</tr>
					</tbody>
				</table>
				<p class="text-center float-center" align="center" style="Margin:0;Margin-bottom:10px;color:#C8C8C8;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:12px;font-weight:400;line-height:16px;margin:0;margin-bottom:10px;padding:0;text-align:center">%s</p>
			</center>
		</td>
	</tr>
</table>
EOF;

	protected string $sender = <<<EOF
<table align="center" class="container sender float-center" style="Margin:0 auto;background:0 0!important;border-collapse:collapse;border-spacing:0;float:none;margin:0 auto;padding:0;text-align:center;vertical-align:top;width:580px">
	<tbody>
	<tr style="padding:0;text-align:left;vertical-align:top">
		<td style="Margin:0;border-collapse:collapse!important;padding:0;padding-bottom:20px;text-align:center;vertical-align:top">
			<table style="Margin:0 auto;border-collapse:collapse;border-spacing:0;margin:0 auto;padding:0">
				<tr style="padding:0;vertical-align:middle">
					<td style="Margin:0;padding:0;vertical-align:middle;width:40px">%1\$s</td>
					<td style="Margin:0;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;padding:0;padding-left:12px;text-align:left;vertical-align:middle"><strong>%2\$s</strong>%3\$s</td>
				</tr>
			</table>
		</td>
	</tr>
	</tbody>
</table>
EOF;

	protected string $senderSubline = <<<EOF
<br><span style="color:#777;font-size:14px">%s</span>
EOF;

	protected string $initials = <<<EOF
<div style="background:%1\$s;border-radius:50%%;color:%2\$s;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:600;height:40px;line-height:40px;text-align:center;width:40px">%3\$s</div>
EOF;

	protected string $note = <<<EOF
<table class="row note" style="border-collapse:collapse;border-spacing:0;display:table;padding:0;position:relative;text-align:left;vertical-align:top;width:100%%">
	<tbody>
	<tr style="padding:0;text-align:left;vertical-align:top">
		<th class="small-12 large-12 columns first last" style="Margin:0 auto;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0 auto;padding:0;padding-bottom:30px;padding-left:30px;padding-right:30px;text-align:left;width:550px">
			<table style="background:%1\$s;border-collapse:collapse;border-left:4px solid %2\$s;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%%">
				<tr style="padding:0;text-align:left;vertical-align:top">
					<td style="Margin:0;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.4;margin:0;padding:12px 16px;text-align:left">%3\$s</td>
				</tr>
			</table>
		</th>
	</tr>
	</tbody>
</table>
EOF;

	protected string $noteLabel = <<<EOF
<span style="color:#777;font-size:14px">%s</span><br>
EOF;

	/** @var array<string, array{background: string, border: string}> */
	protected array $noteColors = [
		IEMailTemplate::NOTE_NEUTRAL => ['background' => '#f5f5f5', 'border' => '#ccc'],
		IEMailTemplate::NOTE_INFO => ['background' => '#e5f0f5', 'border' => '#0071ad'],
		IEMailTemplate::NOTE_WARNING => ['background' => '#fdf6e3', 'border' => '#a37200'],
		IEMailTemplate::NOTE_ERROR => ['background' => '#fbe5e5', 'border' => '#db0606'],
	];

	protected string $detailsBegin = <<<EOF
<table class="row details" style="border-collapse:collapse;border-spacing:0;display:table;padding:0;position:relative;text-align:left;vertical-align:top;width:100%%">
	<tbody>
	<tr style="padding:0;text-align:left;vertical-align:top">
		<th class="small-12 large-12 columns first last" style="Margin:0 auto;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0 auto;padding:0;padding-bottom:30px;padding-left:30px;padding-right:30px;text-align:left;width:550px">
			<table style="border:1px solid #ddd;border-collapse:separate;border-radius:8px;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%%">
				<tr style="padding:0;text-align:left;vertical-align:top">
					<td colspan="2" style="Margin:0;%1\$smargin:0;padding:16px;text-align:left;vertical-align:top">
						<table style="border-collapse:collapse;border-spacing:0;padding:0">
							<tr style="padding:0;vertical-align:middle">
								%2\$s<td style="Margin:0;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;padding:0;text-align:left;vertical-align:middle"><strong style="font-size:18px">%3\$s</strong>%4\$s</td>
							</tr>
						</table>
					</td>
				</tr>
EOF;

	protected string $detailsLeading = <<<EOF
<td style="Margin:0;margin:0;padding:0;padding-right:12px;vertical-align:middle">%s</td>
EOF;

	protected string $detailsDateBadge = <<<EOF
<table style="border:1px solid #ddd;border-collapse:separate;border-radius:6px;border-spacing:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;padding:0;text-align:center;width:48px">
	<tr><td style="background:%1\$s;border-radius:5px 5px 0 0;color:%2\$s;font-size:11px;font-weight:600;padding:2px 0;text-transform:uppercase">%3\$s</td></tr>
	<tr><td style="color:#0a0a0a;font-size:20px;font-weight:600;padding:4px 0">%4\$s</td></tr>
</table>
EOF;

	protected string $detailsRow = <<<EOF
				<tr style="padding:0;text-align:left;vertical-align:top">
					<td style="Margin:0;%1\$scolor:#777;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.4;margin:0;padding:10px 16px;text-align:left;vertical-align:top;width:35%%">%2\$s</td>
					<td style="Margin:0;%1\$scolor:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.4;margin:0;padding:10px 16px 10px 0;text-align:left;vertical-align:top">%3\$s</td>
				</tr>
EOF;

	protected string $detailsEnd = <<<EOF
			</table>
		</th>
	</tr>
	</tbody>
</table>
EOF;

	protected string $buttonsBegin = <<<EOF
<table class="spacer" style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%%">
	<tbody>
	<tr style="padding:0;text-align:left;vertical-align:top">
		<td height="20px" style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;color:#0a0a0a;font-size:20px;font-weight:400;hyphens:auto;line-height:20px;margin:0;mso-line-height-rule:exactly;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">&#xA0;</td>
	</tr>
	</tbody>
</table>
<table align="center" class="row btn-group" style="border-collapse:collapse;border-spacing:0;display:table;padding:0;position:relative;text-align:left;vertical-align:top;width:100%%">
	<tbody>
	<tr style="padding:0;text-align:left;vertical-align:top">
		<th class="small-12 large-12 columns first last" style="Margin:0 auto;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0 auto;padding:0;padding-bottom:30px;padding-left:30px;padding-right:30px;text-align:left;width:550px">
			<center data-parsed="" style="min-width:490px;width:100%%">
				%s
				<!--[if (gte mso 9)|(IE)]>
				<table>
					<tr>
						<td>
						<![endif]-->
EOF;

	protected string $buttonsLabel = <<<EOF
<p style="Margin:0;Margin-bottom:10px;color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:600;line-height:1.3;margin:0;margin-bottom:10px;padding:0;text-align:center">%s</p>
EOF;

	protected string $buttonsPrimary = <<<EOF
							<table class="button btn default primary float-center" style="Margin:0 0 10px 0;background:%1\$s;background-color:%1\$s;border-collapse:collapse;border-radius:8px;border-spacing:0;display:inline-block;float:none;margin:0 0 10px 0;margin-right:15px;padding:0;text-align:center;vertical-align:top;width:auto">
								<tr style="padding:0;text-align:left;vertical-align:top">
									<td style="Margin:0;border-collapse:collapse!important;margin:0;padding:0;text-align:left;vertical-align:top">
										<a href="%2\$s" style="Margin:0;color:%3\$s;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:regular;line-height:normal;margin:0;padding:8px;text-align:left;text-decoration:none">%4\$s</a>
									</td>
								</tr>
							</table>
EOF;

	protected string $buttonsSecondary = <<<EOF
							<table class="button btn default secondary float-center" style="Margin:0 0 10px 0;background-color:#ccc;border-collapse:collapse;border-radius:8px;border-spacing:0;display:inline-block;float:none;margin:0 0 10px 0;margin-right:15px;padding:1px;text-align:center;vertical-align:top;width:auto">
								<tr style="padding:0;text-align:left;vertical-align:top">
									<td style="Margin:0;border-collapse:collapse!important;margin:0;padding:0;text-align:left;vertical-align:top">
										<a href="%1\$s" style="Margin:0;background-color:#fff;border-radius:7px;color:#6C6C6C!important;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:regular;line-height:normal;margin:0;padding:8px;text-align:left;text-decoration:none">%2\$s</a>
									</td>
								</tr>
							</table>
EOF;

	protected string $buttonsSeparator = <<<EOF
						<!--[if (gte mso 9)|(IE)]>
						</td>
						<td>
						<![endif]-->
EOF;

	protected string $buttonsEnd = <<<EOF
						<!--[if (gte mso 9)|(IE)]>
						</td>
					</tr>
				</table>
				<![endif]-->
			</center>
		</th>
	</tr>
	</tbody>
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
			$htmlText = '<em style="color:#777;">' . $metaInfo . '</em><br>' . $htmlText;
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

		$this->htmlBody .= vsprintf($this->buttonGroup, [$color, $color, $urlLeft, $color, $textColor, $textColor, $textLeft, $urlRight, $textRight]);
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
		$this->htmlBody .= vsprintf($this->button, [$color, $color, $url, $color, $textColor, $textColor, $text]);

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
		$this->htmlBody .= implode($this->buttonsSeparator, $htmlButtons);
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

		$this->ensureBodyListClosed();

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
		$colors = $this->noteColors[$type] ?? $this->noteColors[IEMailTemplate::NOTE_NEUTRAL];

		$this->ensureBodyIsOpened();
		$this->ensureBodyListClosed();

		$htmlText = str_replace("\n", '<br/>', htmlspecialchars($text));
		if ($label !== '') {
			$htmlLabel = htmlspecialchars($label);
			$htmlText = $type === IEMailTemplate::NOTE_NEUTRAL
				? vsprintf($this->noteLabel, [$htmlLabel]) . $htmlText
				: '<strong>' . $htmlLabel . '</strong> ' . $htmlText;
		}
		$this->htmlBody .= vsprintf($this->note, [$colors['background'], $colors['border'], $htmlText]);

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
		$separator = 'border-bottom:1px solid #ddd;';

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
						$htmlParts[] = '<span style="color:#777">' . $text . '</span>';
						$plainParts[] = $part['text'];
						break;
					default:
						$htmlParts[] = $text;
						$plainParts[] = $part['text'];
				}
			}

			$this->htmlBody .= vsprintf($this->detailsRow, [
				$index > 0 ? 'border-top:1px solid #eee;' : '',
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
