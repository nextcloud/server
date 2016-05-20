<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace Test\IntegrityCheck;

use OC\IntegrityCheck\Checker;
use OC\Memcache\NullCache;
use phpseclib\Crypt\RSA;
use phpseclib\File\X509;
use Test\TestCase;
use OC\IntegrityCheck\Helpers\EnvironmentHelper;
use OC\IntegrityCheck\Helpers\FileAccessHelper;
use OC\IntegrityCheck\Helpers\AppLocator;
use OCP\IConfig;
use OCP\ICacheFactory;
use OCP\App\IAppManager;

class CheckerTest extends TestCase {
	/** @var EnvironmentHelper */
	private $environmentHelper;
	/** @var AppLocator */
	private $appLocator;
	/** @var Checker */
	private $checker;
	/** @var FileAccessHelper */
	private $fileAccessHelper;
	/** @var IConfig */
	private $config;
	/** @var ICacheFactory */
	private $cacheFactory;
	/** @var IAppManager */
	private $appManager;

	public function setUp() {
		parent::setUp();
		$this->environmentHelper = $this->getMock('\OC\IntegrityCheck\Helpers\EnvironmentHelper');
		$this->fileAccessHelper = $this->getMock('\OC\IntegrityCheck\Helpers\FileAccessHelper');
		$this->appLocator = $this->getMock('\OC\IntegrityCheck\Helpers\AppLocator');
		$this->config = $this->getMock('\OCP\IConfig');
		$this->cacheFactory = $this->getMock('\OCP\ICacheFactory');
		$this->appManager = $this->getMock('\OCP\App\IAppManager');

		$this->cacheFactory
			->expects($this->any())
			->method('create')
			->with('oc.integritycheck.checker')
			->will($this->returnValue(new NullCache()));

		$this->checker = new Checker(
			$this->environmentHelper,
			$this->fileAccessHelper,
			$this->appLocator,
			$this->config,
			$this->cacheFactory,
			$this->appManager,
			\OC::$server->getTempManager()
		);
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Directory does not exist.
	 */
	public function testWriteAppSignatureOfNotExistingApp() {
		$keyBundle = file_get_contents(__DIR__ .'/../../data/integritycheck/SomeApp.crt');
		$rsaPrivateKey = file_get_contents(__DIR__ .'/../../data/integritycheck/SomeApp.key');
		$rsa = new RSA();
		$rsa->loadKey($rsaPrivateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$this->checker->writeAppSignature('NotExistingApp', $x509, $rsa);
	}

	public function testWriteAppSignature() {
		$expectedSignatureFileData = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "Y5yvXvcGHVPuRRatKVDUONWq1FpLXugZd6Km\/+aEHsQj7coVl9FeMj9OsWamBf7yRIw3dtNLguTLlAA9QAv\/b0uHN3JnbNZN+dwFOve4NMtqXfSDlWftqKN00VS+RJXpG1S2IIx9Poyp2NoghL\/5AuTv4GHiNb7zU\/DT\/kt71pUGPgPR6IIFaE+zHOD96vjYkrH+GfWZzKR0FCdLib9yyNvk+EGrcjKM6qjs2GKfS\/XFjj\/\/neDnh\/0kcPuKE3ZbofnI4TIDTv0CGqvOp7PtqVNc3Vy\/UKa7uF1PT0MAUKMww6EiMUSFZdUVP4WWF0Y72W53Qdtf1hrAZa2kfKyoK5kd7sQmCSKUPSU8978AUVZlBtTRlyT803IKwMV0iHMkw+xYB1sN2FlHup\/DESADqxhdgYuK35bCPvgkb4SBe4B8Voz\/izTvcP7VT5UvkYdAO+05\/jzdaHEmzmsD92CFfvX0q8O\/Y\/29ubftUJsqcHeMDKgcR4eZOE8+\/QVc\/89QO6WnKNuNuV+5bybO6g6PAdC9ZPsCvnihS61O2mwRXHLR3jv2UleFWm+lZEquPKtkhi6SLtDiijA4GV6dmS+dzujSLb7hGeD5o1plZcZ94uhWljl+QIp82+zU\/lYB1Zfr4Mb4e+V7r2gv7Fbv7y6YtjE2GIQwRhC5jq56bD0ZB+I=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEwTCCAqmgAwIBAgIUWv0iujufs5lUr0svCf\/qTQvoyKAwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIyNDk1M1oXDTE2MTEwMzIyNDk1M1owEjEQMA4GA1UEAwwHU29tZUFwcDCCAiIw\r\nDQYJKoZIhvcNAQEBBQADggIPADCCAgoCggIBAK8q0x62agGSRBqeWsaeEwFfepMk\r\nF8cAobMMi50qHCv9IrOn\/ZH9l52xBrbIkErVmRjmly0d4JhD8Ymhidsh9ONKYl\/j\r\n+ishsZDM8eNNdp3Ew+fEYVvY1W7mR1qU24NWj0bzVsClI7hvPVIuw7AjfBDq1C5+\r\nA+ZSLSXYvOK2cEWjdxQfuNZwEZSjmA63DUllBIrm35IaTvfuyhU6BW9yHZxmb8+M\r\nw0xDv30D5UkE\/2N7Pa\/HQJLxCR+3zKibRK3nUyRDLSXxMkU9PnFNaPNX59VPgyj4\r\nGB1CFSToldJVPF4pzh7p36uGXZVxs8m3LFD4Ol8mhi7jkxDZjqFN46gzR0r23Py6\r\ndol9vfawGIoUwp9LvL0S7MvdRY0oazLXwClLP4OQ17zpSMAiCj7fgNT661JamPGj\r\nt5O7Zn2wA7I4ddDS\/HDTWCu98Zwc9fHIpsJPgCZ9awoqxi4Mnf7Pk9g5nnXhszGC\r\ncxxIASQKM+GhdzoRxKknax2RzUCwCzcPRtCj8AQT\/x\/mqN3PfRmlnFBNACUw9bpZ\r\nSOoNq2pCF9igftDWpSIXQ38pVpKLWowjjg3DVRmVKBgivHnUnVLyzYBahHPj0vaz\r\ntFtUFRaqXDnt+4qyUGyrT5h5pjZaTcHIcSB4PiarYwdVvgslgwnQzOUcGAzRWBD4\r\n6jV2brP5vFY3g6iPAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBACTY3CCHC+Z28gCf\r\nFWGKQ3wAKs+k4+0yoti0qm2EKX7rSGQ0PHSas6uW79WstC4Rj+DYkDtIhGMSg8FS\r\nHVGZHGBCc0HwdX+BOAt3zi4p7Sf3oQef70\/4imPoKxbAVCpd\/cveVcFyDC19j1yB\r\nBapwu87oh+muoeaZxOlqQI4UxjBlR\/uRSMhOn2UGauIr3dWJgAF4pGt7TtIzt+1v\r\n0uA6FtN1Y4R5O8AaJPh1bIG0CVvFBE58esGzjEYLhOydgKFnEP94kVPgJD5ds9C3\r\npPhEpo1dRpiXaF7WGIV1X6DI\/ipWvfrF7CEy6I\/kP1InY\/vMDjQjeDnJ\/VrXIWXO\r\nyZvHXVaN\/m+1RlETsH7YO\/QmxRue9ZHN3gvvWtmpCeA95sfpepOk7UcHxHZYyQbF\r\n49\/au8j+5tsr4A83xzsT1JbcKRxkAaQ7WDJpOnE5O1+H0fB+BaLakTg6XX9d4Fo7\r\n7Gin7hVWX7pL+JIyxMzME3LhfI61+CRcqZQIrpyaafUziPQbWIPfEs7h8tCOWyvW\r\nUO8ZLervYCB3j44ivkrxPlcBklDCqqKKBzDP9dYOtS\/P4RB1NkHA9+NTvmBpTonS\r\nSFXdg9fFMD7VfjDE3Vnk+8DWkVH5wBYowTAD7w9Wuzr7DumiAULexnP\/Y7xwxLv7\r\n4B+pXTAcRK0zECDEaX3npS8xWzrB\r\n-----END CERTIFICATE-----"
}';
		$this->fileAccessHelper
			->expects($this->once())
			->method('file_put_contents')
			->with(
					\OC::$SERVERROOT . '/tests/data/integritycheck/app//appinfo/signature.json',
					$expectedSignatureFileData
			);

		$keyBundle = file_get_contents(__DIR__ .'/../../data/integritycheck/SomeApp.crt');
		$rsaPrivateKey = file_get_contents(__DIR__ .'/../../data/integritycheck/SomeApp.key');
		$rsa = new RSA();
		$rsa->loadKey($rsaPrivateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$this->checker->writeAppSignature(\OC::$SERVERROOT . '/tests/data/integritycheck/app/', $x509, $rsa);
	}

	public function testVerifyAppSignatureWithoutSignatureData() {
		$this->environmentHelper
				->expects($this->once())
				->method('getChannel')
				->will($this->returnValue('stable'));
		$this->config
				->expects($this->any())
				->method('getSystemValue')
				->with('integrity.check.disabled', false)
				->will($this->returnValue(false));

		$expected = [
			'EXCEPTION' => [
					'class' => 'OC\IntegrityCheck\Exceptions\InvalidSignatureException',
					'message' => 'Signature data not found.',
			],
		];
		$this->assertSame($expected, $this->checker->verifyAppSignature('SomeApp'));
	}

	public function testVerifyAppSignatureWithValidSignatureData() {
		$this->environmentHelper
				->expects($this->once())
				->method('getChannel')
				->will($this->returnValue('stable'));
		$this->config
				->expects($this->any())
				->method('getSystemValue')
				->with('integrity.check.disabled', false)
				->will($this->returnValue(false));

		$this->appLocator
				->expects($this->once())
				->method('getAppPath')
				->with('SomeApp')
				->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/app/'));
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "dYoohBaWIFR\/To1FXEbMQB5apUhVYlEauBGSPo12nq84wxWkBx2EM3KDRgkB5Sub2tr0CgmAc2EVjPhKIEzAam26cyUb48bJziz1V6wvW7z4GZAfaJpzLkyHdSfV5117VSf5w1rDcAeZDXfGUaaNEJPWytaF4ZIxVge7f3NGshHy4odFVPADy\/u6c43BWvaOtJ4m3aJQbP6sxCO9dxwcm5yJJJR3n36jfh229sdWBxyl8BhwhH1e1DEv78\/aiL6ckKFPVNzx01R6yDFt3TgEMR97YZ\/R6lWiXG+dsJ305jNFlusLu518zBUvl7g5yjzGN778H29b2C8VLZKmi\/h1CH9jGdD72fCqCYdenD2uZKzb6dsUtXtvBmVcVT6BUGz41W1pkkEEB+YJpMrHILIxAiHRGv1+aZa9\/Oz8LWFd+BEUQjC2LJgojPnpzaG\/msw1nBkX16NNVDWWtJ25Bc\/r\/mG46rwjWB\/cmV6Lwt6KODiqlxgrC4lm9ALOCEWw+23OcYhLwNfQTYevXqHqsFfXOkhUnM8z5vDUb\/HBraB1DjFXN8iLK+1YewD4P495e+SRzrR79Oi3F8SEqRIzRLfN2rnW1BTms\/wYsz0p67cup1Slk1XlNmHwbWX25NVd2PPlLOvZRGoqcKFpIjC5few8THiZfyjiNFwt3RM0AFdZcXY=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDMzM1oXDTE2MTEwMzIxMDMzM1owDzENMAsGA1UEAwwEY29yZTCCAiIwDQYJ\r\nKoZIhvcNAQEBBQADggIPADCCAgoCggIBALb6EgHpkAqZbO5vRO8XSh7G7XGWHw5s\r\niOf4RwPXR6SE9bWZEm\/b72SfWk\/\/J6AbrD8WiOzBuT\/ODy6k5T1arEdHO+Pux0W1\r\nMxYJJI4kH74KKgMpC0SB0Rt+8WrMqV1r3hhJ46df6Xr\/xolP3oD+eLbShPcblhdS\r\nVtkZEkoev8Sh6L2wDCeHDyPxzvj1w2dTdGVO9Kztn0xIlyfEBakqvBWtcxyi3Ln0\r\nklnxlMx3tPDUE4kqvpia9qNiB1AN2PV93eNr5\/2riAzIssMFSCarWCx0AKYb54+d\r\nxLpcYFyqPJ0ydBCkF78DD45RCZet6PNYkdzgbqlUWEGGomkuDoJbBg4wzgzO0D77\r\nH87KFhYW8tKFFvF1V3AHl\/sFQ9tDHaxM9Y0pZ2jPp\/ccdiqnmdkBxBDqsiRvHvVB\r\nCn6qpb4vWGFC7vHOBfYspmEL1zLlKXZv3ezMZEZw7O9ZvUP3VO\/wAtd2vUW8UFiq\r\ns2v1QnNLN6jNh51obcwmrBvWhJy9vQIdtIjQbDxqWTHh1zUSrw9wrlklCBZ\/zrM0\r\ni8nfCFwTxWRxp3H9KoECzO\/zS5R5KIS7s3\/wq\/w9T2Ie4rcecgXwDizwnn0C\/aKc\r\nbDIjujpL1s9HO05pcD\/V3wKcPZ1izymBkmMyIbL52iRVN5FTVHeZdXPpFuq+CTQJ\r\nQ238lC+A\/KOVAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBAGoKTnh8RfJV4sQItVC2\r\nAvfJagkrIqZ3iiQTUBQGTKBsTnAqE1H7QgUSV9vSd+8rgvHkyZsRjmtyR1e3A6Ji\r\noNCXUbExC\/0iCPUqdHZIVb+Lc\/vWuv4ByFMybGPydgtLoEUX2ZrKFWmcgZFDUSRd\r\n9Uj26vtUhCC4bU4jgu6hIrR9IuxOBLQUxGTRZyAcXvj7obqRAEZwFAKQgFpfpqTb\r\nH+kjcbZSaAlLVSF7vBc1syyI8RGYbqpwvtREqJtl5IEIwe6huEqJ3zPnlP2th\/55\r\ncf3Fovj6JJgbb9XFxrdnsOsDOu\/tpnaRWlvv5ib4+SzG5wWFT5UUEo4Wg2STQiiX\r\nuVSRQxK1LE1yg84bs3NZk9FSQh4B8vZVuRr5FaJsZZkwlFlhRO\/\/+TJtXRbyNgsf\r\noMRZGi8DLGU2SGEAHcRH\/QZHq\/XDUWVzdxrSBYcy7GSpT7UDVzGv1rEJUrn5veP1\r\n0KmauAqtiIaYRm4f6YBsn0INcZxzIPZ0p8qFtVZBPeHhvQtvOt0iXI\/XUxEWOa2F\r\nK2EqhErgMK\/N07U1JJJay5tYZRtvkGq46oP\/5kQG8hYST0MDK6VihJoPpvCmAm4E\r\npEYKQ96x6A4EH9Y9mZlYozH\/eqmxPbTK8n89\/p7Ydun4rI+B2iiLnY8REWWy6+UQ\r\nV204fGUkJqW5CrKy3P3XvY9X\r\n-----END CERTIFICATE-----"
}';
		$this->fileAccessHelper
				->expects($this->at(0))
				->method('file_get_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/app//appinfo/signature.json'
				)
				->will($this->returnValue($signatureDataFile));
		$this->fileAccessHelper
				->expects($this->at(1))
				->method('file_get_contents')
				->with(
						'/resources/codesigning/root.crt'
				)
				->will($this->returnValue(file_get_contents(__DIR__ .'/../../data/integritycheck/root.crt')));

		$this->assertSame([], $this->checker->verifyAppSignature('SomeApp'));
	}

	public function testVerifyAppSignatureWithTamperedSignatureData() {
		$this->environmentHelper
				->expects($this->once())
				->method('getChannel')
				->will($this->returnValue('stable'));
		$this->config
				->expects($this->any())
				->method('getSystemValue')
				->with('integrity.check.disabled', false)
				->will($this->returnValue(false));

		$this->appLocator
				->expects($this->once())
				->method('getAppPath')
				->with('SomeApp')
				->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/app/'));
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "tampered",
        "subfolder\/file.txt": "tampered"
    },
    "signature": "EL49UaSeyMAqyMtqId+tgOhhwgOevPZsRLX4j2blnybAB6fN07z0936JqZV7+eMPiE30Idx+UCY6rCFN531Kqe9vAOCdgtHUSOjjKyKc+lvULESlMb6YQcrZrvDlEMMjzjH49ewG7Ai8sNN6HrRUd9U8ws+ewSkW2DOOBItj\/21RBnkrSt+2AtGXGigEvuTm57HrCYDj8\/lSkumC2GVkjLUHeLOKYo4PRNOr6yP5mED5v7zo66AWvXl2fKv54InZcdxsAk35lyK9DGZbk\/027ZRd0AOHT3LImRLvQ+8EAg3XLlRUy0hOFGgPC+jYonMzgYvsAXAXi2j8LnLJlsLwpFwu1k1B+kZVPMumKZvP9OvJb70EirecXmz62V+Jiyuaq7ne4y7Kp5gKZT\/T8SeZ0lFtCmPfYyzBB0y8s5ldmTTmdVYHs54t\/OCCW82HzQZxnFNPzDTRa8HglsaMKrqPtW59+R4UvRKSWhB8M\/Ah57qgzycvPV4KMz\/FbD4l\/\/9chRKSlCfc2k3b8ZSHNmi+EzCKgJjWIoKdgN1yax94puU8jfn8UW+G7H9Y1Jsf\/jox6QLyYEgtV1vOHY2xLT7fVs2vhyvkN2MNjJnmQ70gFG5Qz2lBz5wi6ZpB+tOfCcpbLxWAkoWoIrmC\/Ilqh7mfmRZ43g5upjkepHNd93ONuY8=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEwTCCAqmgAwIBAgIUWv0iujufs5lUr0svCf\/qTQvoyKAwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIyNDk1M1oXDTE2MTEwMzIyNDk1M1owEjEQMA4GA1UEAwwHU29tZUFwcDCCAiIw\r\nDQYJKoZIhvcNAQEBBQADggIPADCCAgoCggIBAK8q0x62agGSRBqeWsaeEwFfepMk\r\nF8cAobMMi50qHCv9IrOn\/ZH9l52xBrbIkErVmRjmly0d4JhD8Ymhidsh9ONKYl\/j\r\n+ishsZDM8eNNdp3Ew+fEYVvY1W7mR1qU24NWj0bzVsClI7hvPVIuw7AjfBDq1C5+\r\nA+ZSLSXYvOK2cEWjdxQfuNZwEZSjmA63DUllBIrm35IaTvfuyhU6BW9yHZxmb8+M\r\nw0xDv30D5UkE\/2N7Pa\/HQJLxCR+3zKibRK3nUyRDLSXxMkU9PnFNaPNX59VPgyj4\r\nGB1CFSToldJVPF4pzh7p36uGXZVxs8m3LFD4Ol8mhi7jkxDZjqFN46gzR0r23Py6\r\ndol9vfawGIoUwp9LvL0S7MvdRY0oazLXwClLP4OQ17zpSMAiCj7fgNT661JamPGj\r\nt5O7Zn2wA7I4ddDS\/HDTWCu98Zwc9fHIpsJPgCZ9awoqxi4Mnf7Pk9g5nnXhszGC\r\ncxxIASQKM+GhdzoRxKknax2RzUCwCzcPRtCj8AQT\/x\/mqN3PfRmlnFBNACUw9bpZ\r\nSOoNq2pCF9igftDWpSIXQ38pVpKLWowjjg3DVRmVKBgivHnUnVLyzYBahHPj0vaz\r\ntFtUFRaqXDnt+4qyUGyrT5h5pjZaTcHIcSB4PiarYwdVvgslgwnQzOUcGAzRWBD4\r\n6jV2brP5vFY3g6iPAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBACTY3CCHC+Z28gCf\r\nFWGKQ3wAKs+k4+0yoti0qm2EKX7rSGQ0PHSas6uW79WstC4Rj+DYkDtIhGMSg8FS\r\nHVGZHGBCc0HwdX+BOAt3zi4p7Sf3oQef70\/4imPoKxbAVCpd\/cveVcFyDC19j1yB\r\nBapwu87oh+muoeaZxOlqQI4UxjBlR\/uRSMhOn2UGauIr3dWJgAF4pGt7TtIzt+1v\r\n0uA6FtN1Y4R5O8AaJPh1bIG0CVvFBE58esGzjEYLhOydgKFnEP94kVPgJD5ds9C3\r\npPhEpo1dRpiXaF7WGIV1X6DI\/ipWvfrF7CEy6I\/kP1InY\/vMDjQjeDnJ\/VrXIWXO\r\nyZvHXVaN\/m+1RlETsH7YO\/QmxRue9ZHN3gvvWtmpCeA95sfpepOk7UcHxHZYyQbF\r\n49\/au8j+5tsr4A83xzsT1JbcKRxkAaQ7WDJpOnE5O1+H0fB+BaLakTg6XX9d4Fo7\r\n7Gin7hVWX7pL+JIyxMzME3LhfI61+CRcqZQIrpyaafUziPQbWIPfEs7h8tCOWyvW\r\nUO8ZLervYCB3j44ivkrxPlcBklDCqqKKBzDP9dYOtS\/P4RB1NkHA9+NTvmBpTonS\r\nSFXdg9fFMD7VfjDE3Vnk+8DWkVH5wBYowTAD7w9Wuzr7DumiAULexnP\/Y7xwxLv7\r\n4B+pXTAcRK0zECDEaX3npS8xWzrB\r\n-----END CERTIFICATE-----"
}';
		$this->fileAccessHelper
				->expects($this->at(0))
				->method('file_get_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/app//appinfo/signature.json'
				)
				->will($this->returnValue($signatureDataFile));
		$this->fileAccessHelper
				->expects($this->at(1))
				->method('file_get_contents')
				->with(
						'/resources/codesigning/root.crt'
				)
				->will($this->returnValue(file_get_contents(__DIR__ .'/../../data/integritycheck/root.crt')));

		$expected = [
				'EXCEPTION' => [
						'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException',
						'message' => 'Signature could not get verified.',
				],
		];
		$this->assertEquals($expected, $this->checker->verifyAppSignature('SomeApp'));
	}

	public function testVerifyAppSignatureWithTamperedFiles() {
		$this->environmentHelper
				->expects($this->once())
				->method('getChannel')
				->will($this->returnValue('stable'));
		$this->config
				->expects($this->any())
				->method('getSystemValue')
				->with('integrity.check.disabled', false)
				->will($this->returnValue(false));

		$this->appLocator
				->expects($this->once())
				->method('getAppPath')
				->with('SomeApp')
				->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData/'));
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "dYoohBaWIFR\/To1FXEbMQB5apUhVYlEauBGSPo12nq84wxWkBx2EM3KDRgkB5Sub2tr0CgmAc2EVjPhKIEzAam26cyUb48bJziz1V6wvW7z4GZAfaJpzLkyHdSfV5117VSf5w1rDcAeZDXfGUaaNEJPWytaF4ZIxVge7f3NGshHy4odFVPADy\/u6c43BWvaOtJ4m3aJQbP6sxCO9dxwcm5yJJJR3n36jfh229sdWBxyl8BhwhH1e1DEv78\/aiL6ckKFPVNzx01R6yDFt3TgEMR97YZ\/R6lWiXG+dsJ305jNFlusLu518zBUvl7g5yjzGN778H29b2C8VLZKmi\/h1CH9jGdD72fCqCYdenD2uZKzb6dsUtXtvBmVcVT6BUGz41W1pkkEEB+YJpMrHILIxAiHRGv1+aZa9\/Oz8LWFd+BEUQjC2LJgojPnpzaG\/msw1nBkX16NNVDWWtJ25Bc\/r\/mG46rwjWB\/cmV6Lwt6KODiqlxgrC4lm9ALOCEWw+23OcYhLwNfQTYevXqHqsFfXOkhUnM8z5vDUb\/HBraB1DjFXN8iLK+1YewD4P495e+SRzrR79Oi3F8SEqRIzRLfN2rnW1BTms\/wYsz0p67cup1Slk1XlNmHwbWX25NVd2PPlLOvZRGoqcKFpIjC5few8THiZfyjiNFwt3RM0AFdZcXY=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDMzM1oXDTE2MTEwMzIxMDMzM1owDzENMAsGA1UEAwwEY29yZTCCAiIwDQYJ\r\nKoZIhvcNAQEBBQADggIPADCCAgoCggIBALb6EgHpkAqZbO5vRO8XSh7G7XGWHw5s\r\niOf4RwPXR6SE9bWZEm\/b72SfWk\/\/J6AbrD8WiOzBuT\/ODy6k5T1arEdHO+Pux0W1\r\nMxYJJI4kH74KKgMpC0SB0Rt+8WrMqV1r3hhJ46df6Xr\/xolP3oD+eLbShPcblhdS\r\nVtkZEkoev8Sh6L2wDCeHDyPxzvj1w2dTdGVO9Kztn0xIlyfEBakqvBWtcxyi3Ln0\r\nklnxlMx3tPDUE4kqvpia9qNiB1AN2PV93eNr5\/2riAzIssMFSCarWCx0AKYb54+d\r\nxLpcYFyqPJ0ydBCkF78DD45RCZet6PNYkdzgbqlUWEGGomkuDoJbBg4wzgzO0D77\r\nH87KFhYW8tKFFvF1V3AHl\/sFQ9tDHaxM9Y0pZ2jPp\/ccdiqnmdkBxBDqsiRvHvVB\r\nCn6qpb4vWGFC7vHOBfYspmEL1zLlKXZv3ezMZEZw7O9ZvUP3VO\/wAtd2vUW8UFiq\r\ns2v1QnNLN6jNh51obcwmrBvWhJy9vQIdtIjQbDxqWTHh1zUSrw9wrlklCBZ\/zrM0\r\ni8nfCFwTxWRxp3H9KoECzO\/zS5R5KIS7s3\/wq\/w9T2Ie4rcecgXwDizwnn0C\/aKc\r\nbDIjujpL1s9HO05pcD\/V3wKcPZ1izymBkmMyIbL52iRVN5FTVHeZdXPpFuq+CTQJ\r\nQ238lC+A\/KOVAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBAGoKTnh8RfJV4sQItVC2\r\nAvfJagkrIqZ3iiQTUBQGTKBsTnAqE1H7QgUSV9vSd+8rgvHkyZsRjmtyR1e3A6Ji\r\noNCXUbExC\/0iCPUqdHZIVb+Lc\/vWuv4ByFMybGPydgtLoEUX2ZrKFWmcgZFDUSRd\r\n9Uj26vtUhCC4bU4jgu6hIrR9IuxOBLQUxGTRZyAcXvj7obqRAEZwFAKQgFpfpqTb\r\nH+kjcbZSaAlLVSF7vBc1syyI8RGYbqpwvtREqJtl5IEIwe6huEqJ3zPnlP2th\/55\r\ncf3Fovj6JJgbb9XFxrdnsOsDOu\/tpnaRWlvv5ib4+SzG5wWFT5UUEo4Wg2STQiiX\r\nuVSRQxK1LE1yg84bs3NZk9FSQh4B8vZVuRr5FaJsZZkwlFlhRO\/\/+TJtXRbyNgsf\r\noMRZGi8DLGU2SGEAHcRH\/QZHq\/XDUWVzdxrSBYcy7GSpT7UDVzGv1rEJUrn5veP1\r\n0KmauAqtiIaYRm4f6YBsn0INcZxzIPZ0p8qFtVZBPeHhvQtvOt0iXI\/XUxEWOa2F\r\nK2EqhErgMK\/N07U1JJJay5tYZRtvkGq46oP\/5kQG8hYST0MDK6VihJoPpvCmAm4E\r\npEYKQ96x6A4EH9Y9mZlYozH\/eqmxPbTK8n89\/p7Ydun4rI+B2iiLnY8REWWy6+UQ\r\nV204fGUkJqW5CrKy3P3XvY9X\r\n-----END CERTIFICATE-----"
}';
		$this->fileAccessHelper
				->expects($this->at(0))
				->method('file_get_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//appinfo/signature.json'
				)
				->will($this->returnValue($signatureDataFile));
		$this->fileAccessHelper
				->expects($this->at(1))
				->method('file_get_contents')
				->with(
						'/resources/codesigning/root.crt'
				)
				->will($this->returnValue(file_get_contents(__DIR__ .'/../../data/integritycheck/root.crt')));


		$expected = [
			'INVALID_HASH' => [
				'AnotherFile.txt' => [
						'expected' => '1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112',
						'current' => '7322348ba269c6d5522efe02f424fa3a0da319a7cd9c33142a5afe32a2d9af2da3a411f086fcfc96ff4301ea566f481dba0960c2abeef3594c4d930462f6584c',
				],
			],
			'FILE_MISSING' => [
				'subfolder/file.txt' => [
						'expected' => '410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b',
						'current' => '',
				],
			],
			'EXTRA_FILE' => [
				'UnecessaryFile' => [
						'expected' => '',
						'current' => 'cf83e1357eefb8bdf1542850d66d8007d620e4050b5715dc83f4a921d36ce9ce47d0d13c5d85f2b0ff8318d2877eec2f63b931bd47417a81a538327af927da3e',
				],
			],

		];
		$this->assertSame($expected, $this->checker->verifyAppSignature('SomeApp'));
	}

	public function testVerifyAppSignatureWithTamperedFilesAndAlternatePath() {
		$this->environmentHelper
				->expects($this->once())
				->method('getChannel')
				->will($this->returnValue('stable'));
		$this->config
				->expects($this->any())
				->method('getSystemValue')
				->with('integrity.check.disabled', false)
				->will($this->returnValue(false));

		$this->appLocator
				->expects($this->never())
				->method('getAppPath')
				->with('SomeApp');
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "dYoohBaWIFR\/To1FXEbMQB5apUhVYlEauBGSPo12nq84wxWkBx2EM3KDRgkB5Sub2tr0CgmAc2EVjPhKIEzAam26cyUb48bJziz1V6wvW7z4GZAfaJpzLkyHdSfV5117VSf5w1rDcAeZDXfGUaaNEJPWytaF4ZIxVge7f3NGshHy4odFVPADy\/u6c43BWvaOtJ4m3aJQbP6sxCO9dxwcm5yJJJR3n36jfh229sdWBxyl8BhwhH1e1DEv78\/aiL6ckKFPVNzx01R6yDFt3TgEMR97YZ\/R6lWiXG+dsJ305jNFlusLu518zBUvl7g5yjzGN778H29b2C8VLZKmi\/h1CH9jGdD72fCqCYdenD2uZKzb6dsUtXtvBmVcVT6BUGz41W1pkkEEB+YJpMrHILIxAiHRGv1+aZa9\/Oz8LWFd+BEUQjC2LJgojPnpzaG\/msw1nBkX16NNVDWWtJ25Bc\/r\/mG46rwjWB\/cmV6Lwt6KODiqlxgrC4lm9ALOCEWw+23OcYhLwNfQTYevXqHqsFfXOkhUnM8z5vDUb\/HBraB1DjFXN8iLK+1YewD4P495e+SRzrR79Oi3F8SEqRIzRLfN2rnW1BTms\/wYsz0p67cup1Slk1XlNmHwbWX25NVd2PPlLOvZRGoqcKFpIjC5few8THiZfyjiNFwt3RM0AFdZcXY=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDMzM1oXDTE2MTEwMzIxMDMzM1owDzENMAsGA1UEAwwEY29yZTCCAiIwDQYJ\r\nKoZIhvcNAQEBBQADggIPADCCAgoCggIBALb6EgHpkAqZbO5vRO8XSh7G7XGWHw5s\r\niOf4RwPXR6SE9bWZEm\/b72SfWk\/\/J6AbrD8WiOzBuT\/ODy6k5T1arEdHO+Pux0W1\r\nMxYJJI4kH74KKgMpC0SB0Rt+8WrMqV1r3hhJ46df6Xr\/xolP3oD+eLbShPcblhdS\r\nVtkZEkoev8Sh6L2wDCeHDyPxzvj1w2dTdGVO9Kztn0xIlyfEBakqvBWtcxyi3Ln0\r\nklnxlMx3tPDUE4kqvpia9qNiB1AN2PV93eNr5\/2riAzIssMFSCarWCx0AKYb54+d\r\nxLpcYFyqPJ0ydBCkF78DD45RCZet6PNYkdzgbqlUWEGGomkuDoJbBg4wzgzO0D77\r\nH87KFhYW8tKFFvF1V3AHl\/sFQ9tDHaxM9Y0pZ2jPp\/ccdiqnmdkBxBDqsiRvHvVB\r\nCn6qpb4vWGFC7vHOBfYspmEL1zLlKXZv3ezMZEZw7O9ZvUP3VO\/wAtd2vUW8UFiq\r\ns2v1QnNLN6jNh51obcwmrBvWhJy9vQIdtIjQbDxqWTHh1zUSrw9wrlklCBZ\/zrM0\r\ni8nfCFwTxWRxp3H9KoECzO\/zS5R5KIS7s3\/wq\/w9T2Ie4rcecgXwDizwnn0C\/aKc\r\nbDIjujpL1s9HO05pcD\/V3wKcPZ1izymBkmMyIbL52iRVN5FTVHeZdXPpFuq+CTQJ\r\nQ238lC+A\/KOVAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBAGoKTnh8RfJV4sQItVC2\r\nAvfJagkrIqZ3iiQTUBQGTKBsTnAqE1H7QgUSV9vSd+8rgvHkyZsRjmtyR1e3A6Ji\r\noNCXUbExC\/0iCPUqdHZIVb+Lc\/vWuv4ByFMybGPydgtLoEUX2ZrKFWmcgZFDUSRd\r\n9Uj26vtUhCC4bU4jgu6hIrR9IuxOBLQUxGTRZyAcXvj7obqRAEZwFAKQgFpfpqTb\r\nH+kjcbZSaAlLVSF7vBc1syyI8RGYbqpwvtREqJtl5IEIwe6huEqJ3zPnlP2th\/55\r\ncf3Fovj6JJgbb9XFxrdnsOsDOu\/tpnaRWlvv5ib4+SzG5wWFT5UUEo4Wg2STQiiX\r\nuVSRQxK1LE1yg84bs3NZk9FSQh4B8vZVuRr5FaJsZZkwlFlhRO\/\/+TJtXRbyNgsf\r\noMRZGi8DLGU2SGEAHcRH\/QZHq\/XDUWVzdxrSBYcy7GSpT7UDVzGv1rEJUrn5veP1\r\n0KmauAqtiIaYRm4f6YBsn0INcZxzIPZ0p8qFtVZBPeHhvQtvOt0iXI\/XUxEWOa2F\r\nK2EqhErgMK\/N07U1JJJay5tYZRtvkGq46oP\/5kQG8hYST0MDK6VihJoPpvCmAm4E\r\npEYKQ96x6A4EH9Y9mZlYozH\/eqmxPbTK8n89\/p7Ydun4rI+B2iiLnY8REWWy6+UQ\r\nV204fGUkJqW5CrKy3P3XvY9X\r\n-----END CERTIFICATE-----"
}';
		$this->fileAccessHelper
				->expects($this->at(0))
				->method('file_get_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//appinfo/signature.json'
				)
				->will($this->returnValue($signatureDataFile));
		$this->fileAccessHelper
				->expects($this->at(1))
				->method('file_get_contents')
				->with(
						'/resources/codesigning/root.crt'
				)
				->will($this->returnValue(file_get_contents(__DIR__ .'/../../data/integritycheck/root.crt')));


		$expected = [
				'INVALID_HASH' => [
						'AnotherFile.txt' => [
								'expected' => '1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112',
								'current' => '7322348ba269c6d5522efe02f424fa3a0da319a7cd9c33142a5afe32a2d9af2da3a411f086fcfc96ff4301ea566f481dba0960c2abeef3594c4d930462f6584c',
						],
				],
				'FILE_MISSING' => [
						'subfolder/file.txt' => [
								'expected' => '410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b',
								'current' => '',
						],
				],
				'EXTRA_FILE' => [
						'UnecessaryFile' => [
								'expected' => '',
								'current' => 'cf83e1357eefb8bdf1542850d66d8007d620e4050b5715dc83f4a921d36ce9ce47d0d13c5d85f2b0ff8318d2877eec2f63b931bd47417a81a538327af927da3e',
						],
				],

		];
		$this->assertSame($expected, $this->checker->verifyAppSignature('SomeApp', \OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData/'));
	}

	public function testVerifyAppWithDifferentScope() {
		$this->environmentHelper
				->expects($this->once())
				->method('getChannel')
				->will($this->returnValue('stable'));
		$this->config
				->expects($this->any())
				->method('getSystemValue')
				->with('integrity.check.disabled', false)
				->will($this->returnValue(false));

		$this->appLocator
				->expects($this->once())
				->method('getAppPath')
				->with('SomeApp')
				->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData/'));
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "eXesvDm3pkek12xSwMG10y9suRES79Nye3jYNe5KYq1tTUPqRRNgxmMGAfcUro0zpLeAr2YgHeSMWtglblGOW7pmwGVPZ0O1Y4r1fE6jnep0kW+35PLIaqCorIOnCAtSzDNKBhwd1ow3zW2wC0DFouuEkIO8u5Fw28g8E8dp8zEk1xMblNPy+xtWkmYHrVJ\/dQgun1bYOF2ZFtAzatwndTI\/bGsy1i3Wsl+x6HyWKQdq8y8VObtOqKDH7uERBEpB9DHVyKflj1v1gQuEH6BhaRdATc7ee0MiQdGblraIySwYRdfo2d8i82OVKrenMB3SLwyCvDPyQ9iKpTOnSF52ZBqaqSXKM2N\/RAkweeBFQQCwcHhqxvB0cfbyHcbkOLeCZe\/tsh68IxwTiYgzvLfl7sOZ5arnZbzrPpZmB+hfV2omkoJ1tDwOWz9hEmLLNtfo2OxyUH1m0+XFaC+Gbn4WkVDgf7YZkwUcG+Qoa3oKDNMss8MEyZxewl2iDGZcf402dlidHRprlfmXbAYuVQ08\/a0HxIKYPGh\/nsMGmwnO15CWtFpAbhUA\/D5oRjsIxnvXaMDg0iAFpdu\/5Ffsj7g3EPdBkiQHNYK7YU1RRx609eH0bZyiIYHdUPw7ikLupvrebZmELqi3mqDFO99u4eISlxFJlUbUND3L4BtmWTWrKwI=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIExjCCAq6gAwIBAgIUHSJjhJqMwr+3TkoiQFg4SVVYQ1gwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIzMjc1NVoXDTE2MTEwMzIzMjc1NVowFzEVMBMGA1UEAwwMQW5vdGhlclNjb3Bl\r\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA33npb5RmUkXrDT+TbwMf\r\n0zQ33SlzsjoGxCrbSwJOn6leGGInJ6ZrdzLL0WTi\/dTpg+Y\/JS+72XWm5NSjaTxo\r\n7OHc3cQBwXQj4tN6j\/y5qqY0GDLYufEkx2rpazqt9lBSJ72u1bGl2yoOXzYCz5i0\r\n60KsJXC9K44LKzGsarzbwAgskSVNkjAsPgjnCWZmcl6icpLi5Fz9rs2UMOWbdvdI\r\nAROsn0eC9E\/akmXTy5YMu6bAIGpvjZFHzyA83FQRbvv5o1V5Gsye\/VQLEgh7rqfz\r\nT\/jgWifP+JgoeB6otzuRZ3fFsmbBiyCIRtIOzQQflozhUlWtmiEGwg4GySuMUjEH\r\nA1LF86LO+ZzDQgd2oYNKmrQ8O+EcLqx9BpV4AFhEvqdk7uycJYPHs6yl+yfbzTeJ\r\n2Xd0yVAfd9r\/iDr36clLj2bzEObdl9xzKjcCIXE4Q0G4Pur41\/BJUDK9PI390ccQ\r\nnFjjVYBMsC859OwW64tMP0zkM9Vv72LCaEzaR8jqH0j11catqxunr+StfMcmxLTN\r\nbqBJbSEq4ER3mJxCTI2UrIVmdQ7+wRxgv3QTDNOZyqrz2L8A1Rpb3h0APxtQv+oA\r\n8KIZYID5\/qsS2V2jITkMQ8Nd1W3b0cZhZ600z+znh3jLJ0TYLvwN6\/qBQTUDaM2o\r\ng1+icMqXIXIeKuoPCVVsG7cCAwEAATANBgkqhkiG9w0BAQUFAAOCAgEAHc4F\/kOV\r\nHc8In5MmGg2YtjwZzjdeoC5TIPZczRqz0B+wRbJzN6aYryKZKLmP+wKpgRnJWDzp\r\nrgKGyyEQIAfK63DEv4B9p4N1B+B3aeMKsSpVcw7wbFTD57V5A7pURGoo31d0mw5L\r\nUIXZ2u+TUfGbzucMxLdFhTwjGpz9M6Kkm\/POxmV0tvLija5LdbdKnYR9BFmyu4IX\r\nqyoIAtComATNLl+3URu3SZxhE3NxhzMz+eAeNfh1KuIf2gWIIeDCXalVSJLym+OQ\r\nHFDpqRhJqfTMprrRlmmU7Zntgbj8\/RRZuXnBvH9cQ2KykLOb4UoCPlGUqOqKyP9m\r\nDJSFRiMJfpgMQUaJk1TLhKF+IR6FnmwURLEtkONJumtDQju9KaWPlhueONdyGi0p\r\nqxLVUo1Vb52XnPhk2GEEduxpDc9V5ePJ+pdcEdMifY\/uPNBRuBj2c87yq1DLH+U4\r\n3XzP1MlwjnBWZYuoFo0j6Jq0r\/MG6HjGdmkGIsRoheRi8Z8Scz5AW5QRkNz8pKop\r\nTELFqQy9g6TyQzzC8t6HZcpNe842ZUk4raEAbCZe\/XqxWMw5svPgNceBqM3fh7sZ\r\nBSykOHLaL8kiRO\/IS3y1yZEAuiWBvtxcTNLzBb+hdRpm2y8\/qH\/pKo+CMj1VzjNT\r\nD8YRQg0cjmDytJzHDrtV\/aTc9W1aPHun0vw=\r\n-----END CERTIFICATE-----"
}';
		$this->fileAccessHelper
				->expects($this->at(0))
				->method('file_get_contents')
				->with(\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//appinfo/signature.json')
				->will($this->returnValue($signatureDataFile));
		$this->fileAccessHelper
				->expects($this->at(1))
				->method('file_get_contents')
				->with(
						'/resources/codesigning/root.crt'
				)
				->will($this->returnValue(file_get_contents(__DIR__ .'/../../data/integritycheck/root.crt')));

		$expected = [
				'EXCEPTION' => [
						'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException',
						'message' => 'Certificate is not valid for required scope. (Requested: SomeApp, current: CN=AnotherScope)',
					],
		];
		$this->assertSame($expected, $this->checker->verifyAppSignature('SomeApp'));
	}

	public function testVerifyAppWithDifferentScopeAndAlwaysTrustedCore() {
		$this->environmentHelper
				->expects($this->once())
				->method('getChannel')
				->will($this->returnValue('stable'));
		$this->config
				->expects($this->any())
				->method('getSystemValue')
				->with('integrity.check.disabled', false)
				->will($this->returnValue(false));

		$this->appLocator
				->expects($this->once())
				->method('getAppPath')
				->with('SomeApp')
				->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/app/'));
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "dYoohBaWIFR\/To1FXEbMQB5apUhVYlEauBGSPo12nq84wxWkBx2EM3KDRgkB5Sub2tr0CgmAc2EVjPhKIEzAam26cyUb48bJziz1V6wvW7z4GZAfaJpzLkyHdSfV5117VSf5w1rDcAeZDXfGUaaNEJPWytaF4ZIxVge7f3NGshHy4odFVPADy\/u6c43BWvaOtJ4m3aJQbP6sxCO9dxwcm5yJJJR3n36jfh229sdWBxyl8BhwhH1e1DEv78\/aiL6ckKFPVNzx01R6yDFt3TgEMR97YZ\/R6lWiXG+dsJ305jNFlusLu518zBUvl7g5yjzGN778H29b2C8VLZKmi\/h1CH9jGdD72fCqCYdenD2uZKzb6dsUtXtvBmVcVT6BUGz41W1pkkEEB+YJpMrHILIxAiHRGv1+aZa9\/Oz8LWFd+BEUQjC2LJgojPnpzaG\/msw1nBkX16NNVDWWtJ25Bc\/r\/mG46rwjWB\/cmV6Lwt6KODiqlxgrC4lm9ALOCEWw+23OcYhLwNfQTYevXqHqsFfXOkhUnM8z5vDUb\/HBraB1DjFXN8iLK+1YewD4P495e+SRzrR79Oi3F8SEqRIzRLfN2rnW1BTms\/wYsz0p67cup1Slk1XlNmHwbWX25NVd2PPlLOvZRGoqcKFpIjC5few8THiZfyjiNFwt3RM0AFdZcXY=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDMzM1oXDTE2MTEwMzIxMDMzM1owDzENMAsGA1UEAwwEY29yZTCCAiIwDQYJ\r\nKoZIhvcNAQEBBQADggIPADCCAgoCggIBALb6EgHpkAqZbO5vRO8XSh7G7XGWHw5s\r\niOf4RwPXR6SE9bWZEm\/b72SfWk\/\/J6AbrD8WiOzBuT\/ODy6k5T1arEdHO+Pux0W1\r\nMxYJJI4kH74KKgMpC0SB0Rt+8WrMqV1r3hhJ46df6Xr\/xolP3oD+eLbShPcblhdS\r\nVtkZEkoev8Sh6L2wDCeHDyPxzvj1w2dTdGVO9Kztn0xIlyfEBakqvBWtcxyi3Ln0\r\nklnxlMx3tPDUE4kqvpia9qNiB1AN2PV93eNr5\/2riAzIssMFSCarWCx0AKYb54+d\r\nxLpcYFyqPJ0ydBCkF78DD45RCZet6PNYkdzgbqlUWEGGomkuDoJbBg4wzgzO0D77\r\nH87KFhYW8tKFFvF1V3AHl\/sFQ9tDHaxM9Y0pZ2jPp\/ccdiqnmdkBxBDqsiRvHvVB\r\nCn6qpb4vWGFC7vHOBfYspmEL1zLlKXZv3ezMZEZw7O9ZvUP3VO\/wAtd2vUW8UFiq\r\ns2v1QnNLN6jNh51obcwmrBvWhJy9vQIdtIjQbDxqWTHh1zUSrw9wrlklCBZ\/zrM0\r\ni8nfCFwTxWRxp3H9KoECzO\/zS5R5KIS7s3\/wq\/w9T2Ie4rcecgXwDizwnn0C\/aKc\r\nbDIjujpL1s9HO05pcD\/V3wKcPZ1izymBkmMyIbL52iRVN5FTVHeZdXPpFuq+CTQJ\r\nQ238lC+A\/KOVAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBAGoKTnh8RfJV4sQItVC2\r\nAvfJagkrIqZ3iiQTUBQGTKBsTnAqE1H7QgUSV9vSd+8rgvHkyZsRjmtyR1e3A6Ji\r\noNCXUbExC\/0iCPUqdHZIVb+Lc\/vWuv4ByFMybGPydgtLoEUX2ZrKFWmcgZFDUSRd\r\n9Uj26vtUhCC4bU4jgu6hIrR9IuxOBLQUxGTRZyAcXvj7obqRAEZwFAKQgFpfpqTb\r\nH+kjcbZSaAlLVSF7vBc1syyI8RGYbqpwvtREqJtl5IEIwe6huEqJ3zPnlP2th\/55\r\ncf3Fovj6JJgbb9XFxrdnsOsDOu\/tpnaRWlvv5ib4+SzG5wWFT5UUEo4Wg2STQiiX\r\nuVSRQxK1LE1yg84bs3NZk9FSQh4B8vZVuRr5FaJsZZkwlFlhRO\/\/+TJtXRbyNgsf\r\noMRZGi8DLGU2SGEAHcRH\/QZHq\/XDUWVzdxrSBYcy7GSpT7UDVzGv1rEJUrn5veP1\r\n0KmauAqtiIaYRm4f6YBsn0INcZxzIPZ0p8qFtVZBPeHhvQtvOt0iXI\/XUxEWOa2F\r\nK2EqhErgMK\/N07U1JJJay5tYZRtvkGq46oP\/5kQG8hYST0MDK6VihJoPpvCmAm4E\r\npEYKQ96x6A4EH9Y9mZlYozH\/eqmxPbTK8n89\/p7Ydun4rI+B2iiLnY8REWWy6+UQ\r\nV204fGUkJqW5CrKy3P3XvY9X\r\n-----END CERTIFICATE-----"
}';
		$this->fileAccessHelper
				->expects($this->at(0))
				->method('file_get_contents')
				->with(\OC::$SERVERROOT . '/tests/data/integritycheck/app//appinfo/signature.json')
				->will($this->returnValue($signatureDataFile));
		$this->fileAccessHelper
				->expects($this->at(1))
				->method('file_get_contents')
				->with(
					'/resources/codesigning/root.crt'
				)
				->will($this->returnValue(file_get_contents(__DIR__ .'/../../data/integritycheck/root.crt')));

		$this->assertSame([], $this->checker->verifyAppSignature('SomeApp'));
	}

	public function testWriteCoreSignature() {
		$expectedSignatureFileData = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "dYoohBaWIFR\/To1FXEbMQB5apUhVYlEauBGSPo12nq84wxWkBx2EM3KDRgkB5Sub2tr0CgmAc2EVjPhKIEzAam26cyUb48bJziz1V6wvW7z4GZAfaJpzLkyHdSfV5117VSf5w1rDcAeZDXfGUaaNEJPWytaF4ZIxVge7f3NGshHy4odFVPADy\/u6c43BWvaOtJ4m3aJQbP6sxCO9dxwcm5yJJJR3n36jfh229sdWBxyl8BhwhH1e1DEv78\/aiL6ckKFPVNzx01R6yDFt3TgEMR97YZ\/R6lWiXG+dsJ305jNFlusLu518zBUvl7g5yjzGN778H29b2C8VLZKmi\/h1CH9jGdD72fCqCYdenD2uZKzb6dsUtXtvBmVcVT6BUGz41W1pkkEEB+YJpMrHILIxAiHRGv1+aZa9\/Oz8LWFd+BEUQjC2LJgojPnpzaG\/msw1nBkX16NNVDWWtJ25Bc\/r\/mG46rwjWB\/cmV6Lwt6KODiqlxgrC4lm9ALOCEWw+23OcYhLwNfQTYevXqHqsFfXOkhUnM8z5vDUb\/HBraB1DjFXN8iLK+1YewD4P495e+SRzrR79Oi3F8SEqRIzRLfN2rnW1BTms\/wYsz0p67cup1Slk1XlNmHwbWX25NVd2PPlLOvZRGoqcKFpIjC5few8THiZfyjiNFwt3RM0AFdZcXY=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDMzM1oXDTE2MTEwMzIxMDMzM1owDzENMAsGA1UEAwwEY29yZTCCAiIwDQYJ\r\nKoZIhvcNAQEBBQADggIPADCCAgoCggIBALb6EgHpkAqZbO5vRO8XSh7G7XGWHw5s\r\niOf4RwPXR6SE9bWZEm\/b72SfWk\/\/J6AbrD8WiOzBuT\/ODy6k5T1arEdHO+Pux0W1\r\nMxYJJI4kH74KKgMpC0SB0Rt+8WrMqV1r3hhJ46df6Xr\/xolP3oD+eLbShPcblhdS\r\nVtkZEkoev8Sh6L2wDCeHDyPxzvj1w2dTdGVO9Kztn0xIlyfEBakqvBWtcxyi3Ln0\r\nklnxlMx3tPDUE4kqvpia9qNiB1AN2PV93eNr5\/2riAzIssMFSCarWCx0AKYb54+d\r\nxLpcYFyqPJ0ydBCkF78DD45RCZet6PNYkdzgbqlUWEGGomkuDoJbBg4wzgzO0D77\r\nH87KFhYW8tKFFvF1V3AHl\/sFQ9tDHaxM9Y0pZ2jPp\/ccdiqnmdkBxBDqsiRvHvVB\r\nCn6qpb4vWGFC7vHOBfYspmEL1zLlKXZv3ezMZEZw7O9ZvUP3VO\/wAtd2vUW8UFiq\r\ns2v1QnNLN6jNh51obcwmrBvWhJy9vQIdtIjQbDxqWTHh1zUSrw9wrlklCBZ\/zrM0\r\ni8nfCFwTxWRxp3H9KoECzO\/zS5R5KIS7s3\/wq\/w9T2Ie4rcecgXwDizwnn0C\/aKc\r\nbDIjujpL1s9HO05pcD\/V3wKcPZ1izymBkmMyIbL52iRVN5FTVHeZdXPpFuq+CTQJ\r\nQ238lC+A\/KOVAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBAGoKTnh8RfJV4sQItVC2\r\nAvfJagkrIqZ3iiQTUBQGTKBsTnAqE1H7QgUSV9vSd+8rgvHkyZsRjmtyR1e3A6Ji\r\noNCXUbExC\/0iCPUqdHZIVb+Lc\/vWuv4ByFMybGPydgtLoEUX2ZrKFWmcgZFDUSRd\r\n9Uj26vtUhCC4bU4jgu6hIrR9IuxOBLQUxGTRZyAcXvj7obqRAEZwFAKQgFpfpqTb\r\nH+kjcbZSaAlLVSF7vBc1syyI8RGYbqpwvtREqJtl5IEIwe6huEqJ3zPnlP2th\/55\r\ncf3Fovj6JJgbb9XFxrdnsOsDOu\/tpnaRWlvv5ib4+SzG5wWFT5UUEo4Wg2STQiiX\r\nuVSRQxK1LE1yg84bs3NZk9FSQh4B8vZVuRr5FaJsZZkwlFlhRO\/\/+TJtXRbyNgsf\r\noMRZGi8DLGU2SGEAHcRH\/QZHq\/XDUWVzdxrSBYcy7GSpT7UDVzGv1rEJUrn5veP1\r\n0KmauAqtiIaYRm4f6YBsn0INcZxzIPZ0p8qFtVZBPeHhvQtvOt0iXI\/XUxEWOa2F\r\nK2EqhErgMK\/N07U1JJJay5tYZRtvkGq46oP\/5kQG8hYST0MDK6VihJoPpvCmAm4E\r\npEYKQ96x6A4EH9Y9mZlYozH\/eqmxPbTK8n89\/p7Ydun4rI+B2iiLnY8REWWy6+UQ\r\nV204fGUkJqW5CrKy3P3XvY9X\r\n-----END CERTIFICATE-----"
}';
		$this->environmentHelper
				->expects($this->any())
				->method('getServerRoot')
				->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/app/'));
		$this->fileAccessHelper
				->expects($this->once())
				->method('file_put_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/app//core/signature.json',
						$expectedSignatureFileData
				);

		$keyBundle = file_get_contents(__DIR__ .'/../../data/integritycheck/core.crt');
		$rsaPrivateKey = file_get_contents(__DIR__ .'/../../data/integritycheck/core.key');
		$rsa = new RSA();
		$rsa->loadKey($rsaPrivateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$this->checker->writeCoreSignature($x509, $rsa, \OC::$SERVERROOT . '/tests/data/integritycheck/app/');
	}

	public function testWriteCoreSignatureWithUnmodifiedHtaccess() {
		$expectedSignatureFileData = '{
    "hashes": {
        ".htaccess": "dc479770a6232061e04a768ee1f9133fdb3aea7b3a99f7105b0e0b6197474733e8d14b5b2bbad054e6b62a410fe5d0b3d790242dee1e0f11274af2100f5289e2",
        "subfolder\/.htaccess": "2c57b1e25050e11dc3ae975832f378c452159f7b69f818e47eeeafadd6ba568517461dcb4d843b90b906cd7c89d161bc1b89dff8e3ae0eb6f5088508c47befd1"
    },
    "signature": "nRtR377DB\/I\/4hmh9q3elMQYfSHnQFlNtjchNgrdfmUQqVmgkU\/4qgGyxDqYkV8mSMbH2gYysfP42nx\/3zSo7n0dBYDfU87Q6f96Cv597vEV27do8CaBkEk8Xjn2SxhHw8hVxracvE2OBAPxk0H3sRp\/cQBgjoXpju4kQin0N5E+DEJMh7Sp+u8aKoFpb+2FaAZJFn\/hnqxLTlVi2nyDxGL3U0eobWY+jWH9XPt52v3Hyh8TDhcAnQ1cN30B8Jn2+jkrm8ib+buchaCXHk0cPX72xuPECdwOEKLCBNrJa3FGSvO1zWiecnCgxCXgt+R8hUgsVPTsbrdFY2YRJGIhHndYZL98XzgG7cw85SnnMMe2SulzeL7xANGF8qiEVyiC7x83bbj5xOkeM\/CUTajrLBO3vyZ23KKOxvskjgI0t+Zw1zFsl+sYW0\/O\/V5WzPOwMwV8+iApQ8k9gEMiYQg98QLEMYnSohncmp0Z9qx2qFcQuHLcKJVa1J6wGtE\/EHR\/4d0aYPd6IRjg+qshCJmdzud\/12xjpGTl+BT0Hi0VsU5o7ZMi7WhmukZmmv8u0uZsvKREQNATm4cO4WCkYySt5O9gZEJOF+jjgeynDoAh09lyrNXIgMpM9ufm\/XEG\/I\/f2zIwbAUc6J6qks5OuYlJzW5vscTiOKhwcGZU9WBLgh0=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDMzM1oXDTE2MTEwMzIxMDMzM1owDzENMAsGA1UEAwwEY29yZTCCAiIwDQYJ\r\nKoZIhvcNAQEBBQADggIPADCCAgoCggIBALb6EgHpkAqZbO5vRO8XSh7G7XGWHw5s\r\niOf4RwPXR6SE9bWZEm\/b72SfWk\/\/J6AbrD8WiOzBuT\/ODy6k5T1arEdHO+Pux0W1\r\nMxYJJI4kH74KKgMpC0SB0Rt+8WrMqV1r3hhJ46df6Xr\/xolP3oD+eLbShPcblhdS\r\nVtkZEkoev8Sh6L2wDCeHDyPxzvj1w2dTdGVO9Kztn0xIlyfEBakqvBWtcxyi3Ln0\r\nklnxlMx3tPDUE4kqvpia9qNiB1AN2PV93eNr5\/2riAzIssMFSCarWCx0AKYb54+d\r\nxLpcYFyqPJ0ydBCkF78DD45RCZet6PNYkdzgbqlUWEGGomkuDoJbBg4wzgzO0D77\r\nH87KFhYW8tKFFvF1V3AHl\/sFQ9tDHaxM9Y0pZ2jPp\/ccdiqnmdkBxBDqsiRvHvVB\r\nCn6qpb4vWGFC7vHOBfYspmEL1zLlKXZv3ezMZEZw7O9ZvUP3VO\/wAtd2vUW8UFiq\r\ns2v1QnNLN6jNh51obcwmrBvWhJy9vQIdtIjQbDxqWTHh1zUSrw9wrlklCBZ\/zrM0\r\ni8nfCFwTxWRxp3H9KoECzO\/zS5R5KIS7s3\/wq\/w9T2Ie4rcecgXwDizwnn0C\/aKc\r\nbDIjujpL1s9HO05pcD\/V3wKcPZ1izymBkmMyIbL52iRVN5FTVHeZdXPpFuq+CTQJ\r\nQ238lC+A\/KOVAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBAGoKTnh8RfJV4sQItVC2\r\nAvfJagkrIqZ3iiQTUBQGTKBsTnAqE1H7QgUSV9vSd+8rgvHkyZsRjmtyR1e3A6Ji\r\noNCXUbExC\/0iCPUqdHZIVb+Lc\/vWuv4ByFMybGPydgtLoEUX2ZrKFWmcgZFDUSRd\r\n9Uj26vtUhCC4bU4jgu6hIrR9IuxOBLQUxGTRZyAcXvj7obqRAEZwFAKQgFpfpqTb\r\nH+kjcbZSaAlLVSF7vBc1syyI8RGYbqpwvtREqJtl5IEIwe6huEqJ3zPnlP2th\/55\r\ncf3Fovj6JJgbb9XFxrdnsOsDOu\/tpnaRWlvv5ib4+SzG5wWFT5UUEo4Wg2STQiiX\r\nuVSRQxK1LE1yg84bs3NZk9FSQh4B8vZVuRr5FaJsZZkwlFlhRO\/\/+TJtXRbyNgsf\r\noMRZGi8DLGU2SGEAHcRH\/QZHq\/XDUWVzdxrSBYcy7GSpT7UDVzGv1rEJUrn5veP1\r\n0KmauAqtiIaYRm4f6YBsn0INcZxzIPZ0p8qFtVZBPeHhvQtvOt0iXI\/XUxEWOa2F\r\nK2EqhErgMK\/N07U1JJJay5tYZRtvkGq46oP\/5kQG8hYST0MDK6VihJoPpvCmAm4E\r\npEYKQ96x6A4EH9Y9mZlYozH\/eqmxPbTK8n89\/p7Ydun4rI+B2iiLnY8REWWy6+UQ\r\nV204fGUkJqW5CrKy3P3XvY9X\r\n-----END CERTIFICATE-----"
}';
		$this->environmentHelper
				->expects($this->any())
				->method('getServerRoot')
				->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessUnmodified/'));
		$this->fileAccessHelper
				->expects($this->once())
				->method('file_put_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessUnmodified//core/signature.json',
						$expectedSignatureFileData
				);

		$keyBundle = file_get_contents(__DIR__ .'/../../data/integritycheck/core.crt');
		$rsaPrivateKey = file_get_contents(__DIR__ .'/../../data/integritycheck/core.key');
		$rsa = new RSA();
		$rsa->loadKey($rsaPrivateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$this->checker->writeCoreSignature($x509, $rsa, \OC::$SERVERROOT . '/tests/data/integritycheck/htaccessUnmodified/');
	}

	public function testWriteCoreSignatureWithInvalidModifiedHtaccess() {
		$expectedSignatureFileData = '{
    "hashes": {
        ".htaccess": "4a54273dc8d697b2ca615acf2ae2c1ee3c1c643492cb04f42b10984fa9aacff1420dc829fd82f93ad3476fbd0cdab0251142c887dc8f872d03e39a3a3eb6d381"
    },
    "signature": "qpDddYGgAKNR3TszOgjPXRphUl2P9Ym5OQaetltocgZASGDkOun5D64+1D0QJRKb4SG2+48muxGOHyL2Ngos4NUrrSR+SIkywZacay82YQBCEdr7\/4MjW1WHRPjvboLwEJwViw0EdAjsWRpD68aPnzUGrGsy2BsCo06P5iwjk9cXcHxdjC9R39npvoC3QNvQ2jmNIbh1Lc4U97dbb+CsXEQCLU1OSa9p3q6cEFV98Easwt7uF\/DzHK+CbeZlxVZ0DwLh2\/ylT1PyGou8QC1b3vKAnPjLWMO+UsCPpCKhk3C5pV+5etQ8puGd+0x2t5tEU+qXxLzek91zWNC+rqgC\/WlqLKbwPb\/BCHs4zLGV55Q2fEQmT21x0KCUELdPs4dBnYP4Ox5tEDugtJujWFzOHzoY6gGa\/BY\/78pSZXmq9o8dWkBEtioWWvaNZ1rM0ddE83GBlBTgjigi9Ay1D++bUW\/FCBB7CMk6qyNlV81H+cBuIEODw2aymmkM9LLDD2Qbmvo8gHEPRjiQxPC5OpDlcdSNiL+zcxVxeuX4FpT+9xzz\/\/DRONhufxRpsbuCOMxd96RW7y9U2N2Uxb3Bzn\/BIqEayUUsdgZjfaGcXXYKR+chu\/LOwNYN6RlnLsgqL\/dhGKwlRVKXw1RA2\/af\/CpqyR7uVP6al1YJo\/YJ+5XJ6zE=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDMzM1oXDTE2MTEwMzIxMDMzM1owDzENMAsGA1UEAwwEY29yZTCCAiIwDQYJ\r\nKoZIhvcNAQEBBQADggIPADCCAgoCggIBALb6EgHpkAqZbO5vRO8XSh7G7XGWHw5s\r\niOf4RwPXR6SE9bWZEm\/b72SfWk\/\/J6AbrD8WiOzBuT\/ODy6k5T1arEdHO+Pux0W1\r\nMxYJJI4kH74KKgMpC0SB0Rt+8WrMqV1r3hhJ46df6Xr\/xolP3oD+eLbShPcblhdS\r\nVtkZEkoev8Sh6L2wDCeHDyPxzvj1w2dTdGVO9Kztn0xIlyfEBakqvBWtcxyi3Ln0\r\nklnxlMx3tPDUE4kqvpia9qNiB1AN2PV93eNr5\/2riAzIssMFSCarWCx0AKYb54+d\r\nxLpcYFyqPJ0ydBCkF78DD45RCZet6PNYkdzgbqlUWEGGomkuDoJbBg4wzgzO0D77\r\nH87KFhYW8tKFFvF1V3AHl\/sFQ9tDHaxM9Y0pZ2jPp\/ccdiqnmdkBxBDqsiRvHvVB\r\nCn6qpb4vWGFC7vHOBfYspmEL1zLlKXZv3ezMZEZw7O9ZvUP3VO\/wAtd2vUW8UFiq\r\ns2v1QnNLN6jNh51obcwmrBvWhJy9vQIdtIjQbDxqWTHh1zUSrw9wrlklCBZ\/zrM0\r\ni8nfCFwTxWRxp3H9KoECzO\/zS5R5KIS7s3\/wq\/w9T2Ie4rcecgXwDizwnn0C\/aKc\r\nbDIjujpL1s9HO05pcD\/V3wKcPZ1izymBkmMyIbL52iRVN5FTVHeZdXPpFuq+CTQJ\r\nQ238lC+A\/KOVAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBAGoKTnh8RfJV4sQItVC2\r\nAvfJagkrIqZ3iiQTUBQGTKBsTnAqE1H7QgUSV9vSd+8rgvHkyZsRjmtyR1e3A6Ji\r\noNCXUbExC\/0iCPUqdHZIVb+Lc\/vWuv4ByFMybGPydgtLoEUX2ZrKFWmcgZFDUSRd\r\n9Uj26vtUhCC4bU4jgu6hIrR9IuxOBLQUxGTRZyAcXvj7obqRAEZwFAKQgFpfpqTb\r\nH+kjcbZSaAlLVSF7vBc1syyI8RGYbqpwvtREqJtl5IEIwe6huEqJ3zPnlP2th\/55\r\ncf3Fovj6JJgbb9XFxrdnsOsDOu\/tpnaRWlvv5ib4+SzG5wWFT5UUEo4Wg2STQiiX\r\nuVSRQxK1LE1yg84bs3NZk9FSQh4B8vZVuRr5FaJsZZkwlFlhRO\/\/+TJtXRbyNgsf\r\noMRZGi8DLGU2SGEAHcRH\/QZHq\/XDUWVzdxrSBYcy7GSpT7UDVzGv1rEJUrn5veP1\r\n0KmauAqtiIaYRm4f6YBsn0INcZxzIPZ0p8qFtVZBPeHhvQtvOt0iXI\/XUxEWOa2F\r\nK2EqhErgMK\/N07U1JJJay5tYZRtvkGq46oP\/5kQG8hYST0MDK6VihJoPpvCmAm4E\r\npEYKQ96x6A4EH9Y9mZlYozH\/eqmxPbTK8n89\/p7Ydun4rI+B2iiLnY8REWWy6+UQ\r\nV204fGUkJqW5CrKy3P3XvY9X\r\n-----END CERTIFICATE-----"
}';
		$this->fileAccessHelper
				->expects($this->once())
				->method('file_put_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithInvalidModifiedContent//core/signature.json',
						$expectedSignatureFileData
				);

		$keyBundle = file_get_contents(__DIR__ .'/../../data/integritycheck/core.crt');
		$rsaPrivateKey = file_get_contents(__DIR__ .'/../../data/integritycheck/core.key');
		$rsa = new RSA();
		$rsa->loadKey($rsaPrivateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$this->checker->writeCoreSignature($x509, $rsa, \OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithInvalidModifiedContent/');
	}

	public function testWriteCoreSignatureWithValidModifiedHtaccessAndUserIni() {
		$expectedSignatureFileData = '{
    "hashes": {
        ".htaccess": "ef34c5f35fffb6e8e3008c2118617b53243cfc5ac2513edba9ebd383169862bc16e4f889316ad65788d6b172fe14713af90908c19838c4ba13b4146e12c7ac62",
        ".user.ini": "0a557e3cdca4c2e3675deed761d79d109011dcdebbd9c7f6429f1d3476938ec95729543d7384651d1d0c48e26c5024cc5f517445920915a704ea748bdb903c5f",
        "subfolder\/.htaccess": "2c57b1e25050e11dc3ae975832f378c452159f7b69f818e47eeeafadd6ba568517461dcb4d843b90b906cd7c89d161bc1b89dff8e3ae0eb6f5088508c47befd1"
    },
    "signature": "d6pqYc0pj5hihZK4Pi\/rM9XguY1xK9LEch+jUcxZWwhzOPL4qVHx5LN4FAhEOnr5ZjuhK\/umVEUjieamF4z8tP\/4nnnu2LmRuPMmj6+1tBEwbsKoeg7NiYfYL5h+VSdBePpIZDmjk0tjEpsMtCPhUPAY5vOSSDJ3Xef4KQIpeL6RKIctDcVdO26QWPLFpCo9NK3j91KHuXTcjbAsATDo+oXQzi0CaomBqL6Ft1SU\/Bdes6usgeVWd6mGygZ6zUCLqB4hSi6335xIkkUO1c3NekWksiqTWqdmVIpsTEsIpapx+nE0UFBGc7ZF2rnamg5813g67M5V\/UwhBRcHobMFWfbp73QDUsHcuLCOhamgYh7hbVIlDP7LS2V3kIRLgMLBVwLnvb8LAbaGUsdYGtbfmrhcMK\/jkpGCv0pqUCc4I+1QuVexNEQrdqafwYRnQUsmdFSFaCASYVvgxPrY5jA+y1HwNX5HEc5mMzVORNPhZXUcxWBRUQxUESY5j473DInMQUhq7SLVNAaglxDR1a9M5tQO8engvIJ5eTImLITm0qdefmEvFrxQ0BrrGmPNFYUysrHeNGDhMkGX+JIONj+T4Ht3Z7dr7cfufYDHRaummsTGgRx6206zRSqavsBWL\/Cbzrfu1HhiRagncVcoL40EommJt8lobaKHs3GZ8k861Wo=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDMzM1oXDTE2MTEwMzIxMDMzM1owDzENMAsGA1UEAwwEY29yZTCCAiIwDQYJ\r\nKoZIhvcNAQEBBQADggIPADCCAgoCggIBALb6EgHpkAqZbO5vRO8XSh7G7XGWHw5s\r\niOf4RwPXR6SE9bWZEm\/b72SfWk\/\/J6AbrD8WiOzBuT\/ODy6k5T1arEdHO+Pux0W1\r\nMxYJJI4kH74KKgMpC0SB0Rt+8WrMqV1r3hhJ46df6Xr\/xolP3oD+eLbShPcblhdS\r\nVtkZEkoev8Sh6L2wDCeHDyPxzvj1w2dTdGVO9Kztn0xIlyfEBakqvBWtcxyi3Ln0\r\nklnxlMx3tPDUE4kqvpia9qNiB1AN2PV93eNr5\/2riAzIssMFSCarWCx0AKYb54+d\r\nxLpcYFyqPJ0ydBCkF78DD45RCZet6PNYkdzgbqlUWEGGomkuDoJbBg4wzgzO0D77\r\nH87KFhYW8tKFFvF1V3AHl\/sFQ9tDHaxM9Y0pZ2jPp\/ccdiqnmdkBxBDqsiRvHvVB\r\nCn6qpb4vWGFC7vHOBfYspmEL1zLlKXZv3ezMZEZw7O9ZvUP3VO\/wAtd2vUW8UFiq\r\ns2v1QnNLN6jNh51obcwmrBvWhJy9vQIdtIjQbDxqWTHh1zUSrw9wrlklCBZ\/zrM0\r\ni8nfCFwTxWRxp3H9KoECzO\/zS5R5KIS7s3\/wq\/w9T2Ie4rcecgXwDizwnn0C\/aKc\r\nbDIjujpL1s9HO05pcD\/V3wKcPZ1izymBkmMyIbL52iRVN5FTVHeZdXPpFuq+CTQJ\r\nQ238lC+A\/KOVAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBAGoKTnh8RfJV4sQItVC2\r\nAvfJagkrIqZ3iiQTUBQGTKBsTnAqE1H7QgUSV9vSd+8rgvHkyZsRjmtyR1e3A6Ji\r\noNCXUbExC\/0iCPUqdHZIVb+Lc\/vWuv4ByFMybGPydgtLoEUX2ZrKFWmcgZFDUSRd\r\n9Uj26vtUhCC4bU4jgu6hIrR9IuxOBLQUxGTRZyAcXvj7obqRAEZwFAKQgFpfpqTb\r\nH+kjcbZSaAlLVSF7vBc1syyI8RGYbqpwvtREqJtl5IEIwe6huEqJ3zPnlP2th\/55\r\ncf3Fovj6JJgbb9XFxrdnsOsDOu\/tpnaRWlvv5ib4+SzG5wWFT5UUEo4Wg2STQiiX\r\nuVSRQxK1LE1yg84bs3NZk9FSQh4B8vZVuRr5FaJsZZkwlFlhRO\/\/+TJtXRbyNgsf\r\noMRZGi8DLGU2SGEAHcRH\/QZHq\/XDUWVzdxrSBYcy7GSpT7UDVzGv1rEJUrn5veP1\r\n0KmauAqtiIaYRm4f6YBsn0INcZxzIPZ0p8qFtVZBPeHhvQtvOt0iXI\/XUxEWOa2F\r\nK2EqhErgMK\/N07U1JJJay5tYZRtvkGq46oP\/5kQG8hYST0MDK6VihJoPpvCmAm4E\r\npEYKQ96x6A4EH9Y9mZlYozH\/eqmxPbTK8n89\/p7Ydun4rI+B2iiLnY8REWWy6+UQ\r\nV204fGUkJqW5CrKy3P3XvY9X\r\n-----END CERTIFICATE-----"
}';
		$this->environmentHelper
				->expects($this->any())
				->method('getServerRoot')
				->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithValidModifiedContent'));
		$this->fileAccessHelper
				->expects($this->once())
				->method('file_put_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithValidModifiedContent/core/signature.json',
						$expectedSignatureFileData
				);

		$keyBundle = file_get_contents(__DIR__ .'/../../data/integritycheck/core.crt');
		$rsaPrivateKey = file_get_contents(__DIR__ .'/../../data/integritycheck/core.key');
		$rsa = new RSA();
		$rsa->loadKey($rsaPrivateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$this->checker->writeCoreSignature($x509, $rsa, \OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithValidModifiedContent');
	}

	public function testVerifyCoreSignatureWithoutSignatureData() {
		$this->environmentHelper
				->expects($this->once())
				->method('getChannel')
				->will($this->returnValue('stable'));
		$this->config
				->expects($this->any())
				->method('getSystemValue')
				->with('integrity.check.disabled', false)
				->will($this->returnValue(false));

		$expected = [
			'EXCEPTION' => [
				'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException',
				'message' => 'Signature data not found.',
			],
		];
		$this->assertSame($expected, $this->checker->verifyCoreSignature());
	}

	public function testVerifyCoreSignatureWithValidSignatureData() {
		$this->environmentHelper
				->expects($this->once())
				->method('getChannel')
				->will($this->returnValue('stable'));
		$this->config
				->expects($this->any())
				->method('getSystemValue')
				->with('integrity.check.disabled', false)
				->will($this->returnValue(false));

		$this->environmentHelper
				->expects($this->any())
				->method('getServerRoot')
				->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/app/'));
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "dYoohBaWIFR\/To1FXEbMQB5apUhVYlEauBGSPo12nq84wxWkBx2EM3KDRgkB5Sub2tr0CgmAc2EVjPhKIEzAam26cyUb48bJziz1V6wvW7z4GZAfaJpzLkyHdSfV5117VSf5w1rDcAeZDXfGUaaNEJPWytaF4ZIxVge7f3NGshHy4odFVPADy\/u6c43BWvaOtJ4m3aJQbP6sxCO9dxwcm5yJJJR3n36jfh229sdWBxyl8BhwhH1e1DEv78\/aiL6ckKFPVNzx01R6yDFt3TgEMR97YZ\/R6lWiXG+dsJ305jNFlusLu518zBUvl7g5yjzGN778H29b2C8VLZKmi\/h1CH9jGdD72fCqCYdenD2uZKzb6dsUtXtvBmVcVT6BUGz41W1pkkEEB+YJpMrHILIxAiHRGv1+aZa9\/Oz8LWFd+BEUQjC2LJgojPnpzaG\/msw1nBkX16NNVDWWtJ25Bc\/r\/mG46rwjWB\/cmV6Lwt6KODiqlxgrC4lm9ALOCEWw+23OcYhLwNfQTYevXqHqsFfXOkhUnM8z5vDUb\/HBraB1DjFXN8iLK+1YewD4P495e+SRzrR79Oi3F8SEqRIzRLfN2rnW1BTms\/wYsz0p67cup1Slk1XlNmHwbWX25NVd2PPlLOvZRGoqcKFpIjC5few8THiZfyjiNFwt3RM0AFdZcXY=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDMzM1oXDTE2MTEwMzIxMDMzM1owDzENMAsGA1UEAwwEY29yZTCCAiIwDQYJ\r\nKoZIhvcNAQEBBQADggIPADCCAgoCggIBALb6EgHpkAqZbO5vRO8XSh7G7XGWHw5s\r\niOf4RwPXR6SE9bWZEm\/b72SfWk\/\/J6AbrD8WiOzBuT\/ODy6k5T1arEdHO+Pux0W1\r\nMxYJJI4kH74KKgMpC0SB0Rt+8WrMqV1r3hhJ46df6Xr\/xolP3oD+eLbShPcblhdS\r\nVtkZEkoev8Sh6L2wDCeHDyPxzvj1w2dTdGVO9Kztn0xIlyfEBakqvBWtcxyi3Ln0\r\nklnxlMx3tPDUE4kqvpia9qNiB1AN2PV93eNr5\/2riAzIssMFSCarWCx0AKYb54+d\r\nxLpcYFyqPJ0ydBCkF78DD45RCZet6PNYkdzgbqlUWEGGomkuDoJbBg4wzgzO0D77\r\nH87KFhYW8tKFFvF1V3AHl\/sFQ9tDHaxM9Y0pZ2jPp\/ccdiqnmdkBxBDqsiRvHvVB\r\nCn6qpb4vWGFC7vHOBfYspmEL1zLlKXZv3ezMZEZw7O9ZvUP3VO\/wAtd2vUW8UFiq\r\ns2v1QnNLN6jNh51obcwmrBvWhJy9vQIdtIjQbDxqWTHh1zUSrw9wrlklCBZ\/zrM0\r\ni8nfCFwTxWRxp3H9KoECzO\/zS5R5KIS7s3\/wq\/w9T2Ie4rcecgXwDizwnn0C\/aKc\r\nbDIjujpL1s9HO05pcD\/V3wKcPZ1izymBkmMyIbL52iRVN5FTVHeZdXPpFuq+CTQJ\r\nQ238lC+A\/KOVAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBAGoKTnh8RfJV4sQItVC2\r\nAvfJagkrIqZ3iiQTUBQGTKBsTnAqE1H7QgUSV9vSd+8rgvHkyZsRjmtyR1e3A6Ji\r\noNCXUbExC\/0iCPUqdHZIVb+Lc\/vWuv4ByFMybGPydgtLoEUX2ZrKFWmcgZFDUSRd\r\n9Uj26vtUhCC4bU4jgu6hIrR9IuxOBLQUxGTRZyAcXvj7obqRAEZwFAKQgFpfpqTb\r\nH+kjcbZSaAlLVSF7vBc1syyI8RGYbqpwvtREqJtl5IEIwe6huEqJ3zPnlP2th\/55\r\ncf3Fovj6JJgbb9XFxrdnsOsDOu\/tpnaRWlvv5ib4+SzG5wWFT5UUEo4Wg2STQiiX\r\nuVSRQxK1LE1yg84bs3NZk9FSQh4B8vZVuRr5FaJsZZkwlFlhRO\/\/+TJtXRbyNgsf\r\noMRZGi8DLGU2SGEAHcRH\/QZHq\/XDUWVzdxrSBYcy7GSpT7UDVzGv1rEJUrn5veP1\r\n0KmauAqtiIaYRm4f6YBsn0INcZxzIPZ0p8qFtVZBPeHhvQtvOt0iXI\/XUxEWOa2F\r\nK2EqhErgMK\/N07U1JJJay5tYZRtvkGq46oP\/5kQG8hYST0MDK6VihJoPpvCmAm4E\r\npEYKQ96x6A4EH9Y9mZlYozH\/eqmxPbTK8n89\/p7Ydun4rI+B2iiLnY8REWWy6+UQ\r\nV204fGUkJqW5CrKy3P3XvY9X\r\n-----END CERTIFICATE-----"
}';
		$this->fileAccessHelper
				->expects($this->at(0))
				->method('file_get_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/app//core/signature.json'
				)
				->will($this->returnValue($signatureDataFile));
		$this->fileAccessHelper
				->expects($this->at(1))
				->method('file_get_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/app//resources/codesigning/root.crt'
				)
				->will($this->returnValue(file_get_contents(__DIR__ .'/../../data/integritycheck/root.crt')));

		$this->assertSame([], $this->checker->verifyCoreSignature());
	}

	public function testVerifyCoreSignatureWithValidModifiedHtaccessAndUserIniSignatureData() {
		$this->environmentHelper
			->expects($this->once())
			->method('getChannel')
			->will($this->returnValue('stable'));
		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->with('integrity.check.disabled', false)
			->will($this->returnValue(false));

		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithValidModifiedContent'));
		$signatureDataFile = '{
    "hashes": {
        ".htaccess": "ef34c5f35fffb6e8e3008c2118617b53243cfc5ac2513edba9ebd383169862bc16e4f889316ad65788d6b172fe14713af90908c19838c4ba13b4146e12c7ac62",
        ".user.ini": "0a557e3cdca4c2e3675deed761d79d109011dcdebbd9c7f6429f1d3476938ec95729543d7384651d1d0c48e26c5024cc5f517445920915a704ea748bdb903c5f",
        "subfolder\/.htaccess": "2c57b1e25050e11dc3ae975832f378c452159f7b69f818e47eeeafadd6ba568517461dcb4d843b90b906cd7c89d161bc1b89dff8e3ae0eb6f5088508c47befd1"
    },
    "signature": "d6pqYc0pj5hihZK4Pi\/rM9XguY1xK9LEch+jUcxZWwhzOPL4qVHx5LN4FAhEOnr5ZjuhK\/umVEUjieamF4z8tP\/4nnnu2LmRuPMmj6+1tBEwbsKoeg7NiYfYL5h+VSdBePpIZDmjk0tjEpsMtCPhUPAY5vOSSDJ3Xef4KQIpeL6RKIctDcVdO26QWPLFpCo9NK3j91KHuXTcjbAsATDo+oXQzi0CaomBqL6Ft1SU\/Bdes6usgeVWd6mGygZ6zUCLqB4hSi6335xIkkUO1c3NekWksiqTWqdmVIpsTEsIpapx+nE0UFBGc7ZF2rnamg5813g67M5V\/UwhBRcHobMFWfbp73QDUsHcuLCOhamgYh7hbVIlDP7LS2V3kIRLgMLBVwLnvb8LAbaGUsdYGtbfmrhcMK\/jkpGCv0pqUCc4I+1QuVexNEQrdqafwYRnQUsmdFSFaCASYVvgxPrY5jA+y1HwNX5HEc5mMzVORNPhZXUcxWBRUQxUESY5j473DInMQUhq7SLVNAaglxDR1a9M5tQO8engvIJ5eTImLITm0qdefmEvFrxQ0BrrGmPNFYUysrHeNGDhMkGX+JIONj+T4Ht3Z7dr7cfufYDHRaummsTGgRx6206zRSqavsBWL\/Cbzrfu1HhiRagncVcoL40EommJt8lobaKHs3GZ8k861Wo=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDMzM1oXDTE2MTEwMzIxMDMzM1owDzENMAsGA1UEAwwEY29yZTCCAiIwDQYJ\r\nKoZIhvcNAQEBBQADggIPADCCAgoCggIBALb6EgHpkAqZbO5vRO8XSh7G7XGWHw5s\r\niOf4RwPXR6SE9bWZEm\/b72SfWk\/\/J6AbrD8WiOzBuT\/ODy6k5T1arEdHO+Pux0W1\r\nMxYJJI4kH74KKgMpC0SB0Rt+8WrMqV1r3hhJ46df6Xr\/xolP3oD+eLbShPcblhdS\r\nVtkZEkoev8Sh6L2wDCeHDyPxzvj1w2dTdGVO9Kztn0xIlyfEBakqvBWtcxyi3Ln0\r\nklnxlMx3tPDUE4kqvpia9qNiB1AN2PV93eNr5\/2riAzIssMFSCarWCx0AKYb54+d\r\nxLpcYFyqPJ0ydBCkF78DD45RCZet6PNYkdzgbqlUWEGGomkuDoJbBg4wzgzO0D77\r\nH87KFhYW8tKFFvF1V3AHl\/sFQ9tDHaxM9Y0pZ2jPp\/ccdiqnmdkBxBDqsiRvHvVB\r\nCn6qpb4vWGFC7vHOBfYspmEL1zLlKXZv3ezMZEZw7O9ZvUP3VO\/wAtd2vUW8UFiq\r\ns2v1QnNLN6jNh51obcwmrBvWhJy9vQIdtIjQbDxqWTHh1zUSrw9wrlklCBZ\/zrM0\r\ni8nfCFwTxWRxp3H9KoECzO\/zS5R5KIS7s3\/wq\/w9T2Ie4rcecgXwDizwnn0C\/aKc\r\nbDIjujpL1s9HO05pcD\/V3wKcPZ1izymBkmMyIbL52iRVN5FTVHeZdXPpFuq+CTQJ\r\nQ238lC+A\/KOVAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBAGoKTnh8RfJV4sQItVC2\r\nAvfJagkrIqZ3iiQTUBQGTKBsTnAqE1H7QgUSV9vSd+8rgvHkyZsRjmtyR1e3A6Ji\r\noNCXUbExC\/0iCPUqdHZIVb+Lc\/vWuv4ByFMybGPydgtLoEUX2ZrKFWmcgZFDUSRd\r\n9Uj26vtUhCC4bU4jgu6hIrR9IuxOBLQUxGTRZyAcXvj7obqRAEZwFAKQgFpfpqTb\r\nH+kjcbZSaAlLVSF7vBc1syyI8RGYbqpwvtREqJtl5IEIwe6huEqJ3zPnlP2th\/55\r\ncf3Fovj6JJgbb9XFxrdnsOsDOu\/tpnaRWlvv5ib4+SzG5wWFT5UUEo4Wg2STQiiX\r\nuVSRQxK1LE1yg84bs3NZk9FSQh4B8vZVuRr5FaJsZZkwlFlhRO\/\/+TJtXRbyNgsf\r\noMRZGi8DLGU2SGEAHcRH\/QZHq\/XDUWVzdxrSBYcy7GSpT7UDVzGv1rEJUrn5veP1\r\n0KmauAqtiIaYRm4f6YBsn0INcZxzIPZ0p8qFtVZBPeHhvQtvOt0iXI\/XUxEWOa2F\r\nK2EqhErgMK\/N07U1JJJay5tYZRtvkGq46oP\/5kQG8hYST0MDK6VihJoPpvCmAm4E\r\npEYKQ96x6A4EH9Y9mZlYozH\/eqmxPbTK8n89\/p7Ydun4rI+B2iiLnY8REWWy6+UQ\r\nV204fGUkJqW5CrKy3P3XvY9X\r\n-----END CERTIFICATE-----"
}';
		$this->fileAccessHelper
			->expects($this->at(0))
			->method('file_get_contents')
			->with(
				\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithValidModifiedContent/core/signature.json'
			)
			->will($this->returnValue($signatureDataFile));
		$this->fileAccessHelper
			->expects($this->at(1))
			->method('file_get_contents')
			->with(
				\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithValidModifiedContent/resources/codesigning/root.crt'
			)
			->will($this->returnValue(file_get_contents(__DIR__ .'/../../data/integritycheck/root.crt')));

		$this->assertSame([], $this->checker->verifyCoreSignature());
	}

	public function testVerifyCoreSignatureWithValidSignatureDataAndNotAlphabeticOrder() {
		$this->environmentHelper
				->expects($this->once())
				->method('getChannel')
				->will($this->returnValue('stable'));
		$this->config
				->expects($this->any())
				->method('getSystemValue')
				->with('integrity.check.disabled', false)
				->will($this->returnValue(false));

		$this->environmentHelper
				->expects($this->any())
				->method('getServerRoot')
				->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/app/'));
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "dYoohBaWIFR\/To1FXEbMQB5apUhVYlEauBGSPo12nq84wxWkBx2EM3KDRgkB5Sub2tr0CgmAc2EVjPhKIEzAam26cyUb48bJziz1V6wvW7z4GZAfaJpzLkyHdSfV5117VSf5w1rDcAeZDXfGUaaNEJPWytaF4ZIxVge7f3NGshHy4odFVPADy\/u6c43BWvaOtJ4m3aJQbP6sxCO9dxwcm5yJJJR3n36jfh229sdWBxyl8BhwhH1e1DEv78\/aiL6ckKFPVNzx01R6yDFt3TgEMR97YZ\/R6lWiXG+dsJ305jNFlusLu518zBUvl7g5yjzGN778H29b2C8VLZKmi\/h1CH9jGdD72fCqCYdenD2uZKzb6dsUtXtvBmVcVT6BUGz41W1pkkEEB+YJpMrHILIxAiHRGv1+aZa9\/Oz8LWFd+BEUQjC2LJgojPnpzaG\/msw1nBkX16NNVDWWtJ25Bc\/r\/mG46rwjWB\/cmV6Lwt6KODiqlxgrC4lm9ALOCEWw+23OcYhLwNfQTYevXqHqsFfXOkhUnM8z5vDUb\/HBraB1DjFXN8iLK+1YewD4P495e+SRzrR79Oi3F8SEqRIzRLfN2rnW1BTms\/wYsz0p67cup1Slk1XlNmHwbWX25NVd2PPlLOvZRGoqcKFpIjC5few8THiZfyjiNFwt3RM0AFdZcXY=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDMzM1oXDTE2MTEwMzIxMDMzM1owDzENMAsGA1UEAwwEY29yZTCCAiIwDQYJ\r\nKoZIhvcNAQEBBQADggIPADCCAgoCggIBALb6EgHpkAqZbO5vRO8XSh7G7XGWHw5s\r\niOf4RwPXR6SE9bWZEm\/b72SfWk\/\/J6AbrD8WiOzBuT\/ODy6k5T1arEdHO+Pux0W1\r\nMxYJJI4kH74KKgMpC0SB0Rt+8WrMqV1r3hhJ46df6Xr\/xolP3oD+eLbShPcblhdS\r\nVtkZEkoev8Sh6L2wDCeHDyPxzvj1w2dTdGVO9Kztn0xIlyfEBakqvBWtcxyi3Ln0\r\nklnxlMx3tPDUE4kqvpia9qNiB1AN2PV93eNr5\/2riAzIssMFSCarWCx0AKYb54+d\r\nxLpcYFyqPJ0ydBCkF78DD45RCZet6PNYkdzgbqlUWEGGomkuDoJbBg4wzgzO0D77\r\nH87KFhYW8tKFFvF1V3AHl\/sFQ9tDHaxM9Y0pZ2jPp\/ccdiqnmdkBxBDqsiRvHvVB\r\nCn6qpb4vWGFC7vHOBfYspmEL1zLlKXZv3ezMZEZw7O9ZvUP3VO\/wAtd2vUW8UFiq\r\ns2v1QnNLN6jNh51obcwmrBvWhJy9vQIdtIjQbDxqWTHh1zUSrw9wrlklCBZ\/zrM0\r\ni8nfCFwTxWRxp3H9KoECzO\/zS5R5KIS7s3\/wq\/w9T2Ie4rcecgXwDizwnn0C\/aKc\r\nbDIjujpL1s9HO05pcD\/V3wKcPZ1izymBkmMyIbL52iRVN5FTVHeZdXPpFuq+CTQJ\r\nQ238lC+A\/KOVAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBAGoKTnh8RfJV4sQItVC2\r\nAvfJagkrIqZ3iiQTUBQGTKBsTnAqE1H7QgUSV9vSd+8rgvHkyZsRjmtyR1e3A6Ji\r\noNCXUbExC\/0iCPUqdHZIVb+Lc\/vWuv4ByFMybGPydgtLoEUX2ZrKFWmcgZFDUSRd\r\n9Uj26vtUhCC4bU4jgu6hIrR9IuxOBLQUxGTRZyAcXvj7obqRAEZwFAKQgFpfpqTb\r\nH+kjcbZSaAlLVSF7vBc1syyI8RGYbqpwvtREqJtl5IEIwe6huEqJ3zPnlP2th\/55\r\ncf3Fovj6JJgbb9XFxrdnsOsDOu\/tpnaRWlvv5ib4+SzG5wWFT5UUEo4Wg2STQiiX\r\nuVSRQxK1LE1yg84bs3NZk9FSQh4B8vZVuRr5FaJsZZkwlFlhRO\/\/+TJtXRbyNgsf\r\noMRZGi8DLGU2SGEAHcRH\/QZHq\/XDUWVzdxrSBYcy7GSpT7UDVzGv1rEJUrn5veP1\r\n0KmauAqtiIaYRm4f6YBsn0INcZxzIPZ0p8qFtVZBPeHhvQtvOt0iXI\/XUxEWOa2F\r\nK2EqhErgMK\/N07U1JJJay5tYZRtvkGq46oP\/5kQG8hYST0MDK6VihJoPpvCmAm4E\r\npEYKQ96x6A4EH9Y9mZlYozH\/eqmxPbTK8n89\/p7Ydun4rI+B2iiLnY8REWWy6+UQ\r\nV204fGUkJqW5CrKy3P3XvY9X\r\n-----END CERTIFICATE-----"
}';
		$this->fileAccessHelper
				->expects($this->at(0))
				->method('file_get_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/app//core/signature.json'
				)
				->will($this->returnValue($signatureDataFile));
		$this->fileAccessHelper
				->expects($this->at(1))
				->method('file_get_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/app//resources/codesigning/root.crt'
				)
				->will($this->returnValue(file_get_contents(__DIR__ .'/../../data/integritycheck/root.crt')));

		$this->assertSame([], $this->checker->verifyCoreSignature());
	}

	public function testVerifyCoreSignatureWithTamperedSignatureData() {
		$this->environmentHelper
				->expects($this->once())
				->method('getChannel')
				->will($this->returnValue('stable'));
		$this->config
				->expects($this->any())
				->method('getSystemValue')
				->with('integrity.check.disabled', false)
				->will($this->returnValue(false));

		$this->environmentHelper
				->expects($this->any())
				->method('getServerRoot')
				->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData/'));
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "tampered",
        "subfolder\/file.txt": "tampered"
    },
    "signature": "eXesvDm3pkek12xSwMG10y9suRES79Nye3jYNe5KYq1tTUPqRRNgxmMGAfcUro0zpLeAr2YgHeSMWtglblGOW7pmwGVPZ0O1Y4r1fE6jnep0kW+35PLIaqCorIOnCAtSzDNKBhwd1ow3zW2wC0DFouuEkIO8u5Fw28g8E8dp8zEk1xMblNPy+xtWkmYHrVJ\/dQgun1bYOF2ZFtAzatwndTI\/bGsy1i3Wsl+x6HyWKQdq8y8VObtOqKDH7uERBEpB9DHVyKflj1v1gQuEH6BhaRdATc7ee0MiQdGblraIySwYRdfo2d8i82OVKrenMB3SLwyCvDPyQ9iKpTOnSF52ZBqaqSXKM2N\/RAkweeBFQQCwcHhqxvB0cfbyHcbkOLeCZe\/tsh68IxwTiYgzvLfl7sOZ5arnZbzrPpZmB+hfV2omkoJ1tDwOWz9hEmLLNtfo2OxyUH1m0+XFaC+Gbn4WkVDgf7YZkwUcG+Qoa3oKDNMss8MEyZxewl2iDGZcf402dlidHRprlfmXbAYuVQ08\/a0HxIKYPGh\/nsMGmwnO15CWtFpAbhUA\/D5oRjsIxnvXaMDg0iAFpdu\/5Ffsj7g3EPdBkiQHNYK7YU1RRx609eH0bZyiIYHdUPw7ikLupvrebZmELqi3mqDFO99u4eISlxFJlUbUND3L4BtmWTWrKwI=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDMzM1oXDTE2MTEwMzIxMDMzM1owDzENMAsGA1UEAwwEY29yZTCCAiIwDQYJ\r\nKoZIhvcNAQEBBQADggIPADCCAgoCggIBALb6EgHpkAqZbO5vRO8XSh7G7XGWHw5s\r\niOf4RwPXR6SE9bWZEm\/b72SfWk\/\/J6AbrD8WiOzBuT\/ODy6k5T1arEdHO+Pux0W1\r\nMxYJJI4kH74KKgMpC0SB0Rt+8WrMqV1r3hhJ46df6Xr\/xolP3oD+eLbShPcblhdS\r\nVtkZEkoev8Sh6L2wDCeHDyPxzvj1w2dTdGVO9Kztn0xIlyfEBakqvBWtcxyi3Ln0\r\nklnxlMx3tPDUE4kqvpia9qNiB1AN2PV93eNr5\/2riAzIssMFSCarWCx0AKYb54+d\r\nxLpcYFyqPJ0ydBCkF78DD45RCZet6PNYkdzgbqlUWEGGomkuDoJbBg4wzgzO0D77\r\nH87KFhYW8tKFFvF1V3AHl\/sFQ9tDHaxM9Y0pZ2jPp\/ccdiqnmdkBxBDqsiRvHvVB\r\nCn6qpb4vWGFC7vHOBfYspmEL1zLlKXZv3ezMZEZw7O9ZvUP3VO\/wAtd2vUW8UFiq\r\ns2v1QnNLN6jNh51obcwmrBvWhJy9vQIdtIjQbDxqWTHh1zUSrw9wrlklCBZ\/zrM0\r\ni8nfCFwTxWRxp3H9KoECzO\/zS5R5KIS7s3\/wq\/w9T2Ie4rcecgXwDizwnn0C\/aKc\r\nbDIjujpL1s9HO05pcD\/V3wKcPZ1izymBkmMyIbL52iRVN5FTVHeZdXPpFuq+CTQJ\r\nQ238lC+A\/KOVAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBAGoKTnh8RfJV4sQItVC2\r\nAvfJagkrIqZ3iiQTUBQGTKBsTnAqE1H7QgUSV9vSd+8rgvHkyZsRjmtyR1e3A6Ji\r\noNCXUbExC\/0iCPUqdHZIVb+Lc\/vWuv4ByFMybGPydgtLoEUX2ZrKFWmcgZFDUSRd\r\n9Uj26vtUhCC4bU4jgu6hIrR9IuxOBLQUxGTRZyAcXvj7obqRAEZwFAKQgFpfpqTb\r\nH+kjcbZSaAlLVSF7vBc1syyI8RGYbqpwvtREqJtl5IEIwe6huEqJ3zPnlP2th\/55\r\ncf3Fovj6JJgbb9XFxrdnsOsDOu\/tpnaRWlvv5ib4+SzG5wWFT5UUEo4Wg2STQiiX\r\nuVSRQxK1LE1yg84bs3NZk9FSQh4B8vZVuRr5FaJsZZkwlFlhRO\/\/+TJtXRbyNgsf\r\noMRZGi8DLGU2SGEAHcRH\/QZHq\/XDUWVzdxrSBYcy7GSpT7UDVzGv1rEJUrn5veP1\r\n0KmauAqtiIaYRm4f6YBsn0INcZxzIPZ0p8qFtVZBPeHhvQtvOt0iXI\/XUxEWOa2F\r\nK2EqhErgMK\/N07U1JJJay5tYZRtvkGq46oP\/5kQG8hYST0MDK6VihJoPpvCmAm4E\r\npEYKQ96x6A4EH9Y9mZlYozH\/eqmxPbTK8n89\/p7Ydun4rI+B2iiLnY8REWWy6+UQ\r\nV204fGUkJqW5CrKy3P3XvY9X\r\n-----END CERTIFICATE-----"
}';
		$this->fileAccessHelper
				->expects($this->at(0))
				->method('file_get_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//core/signature.json'
				)
				->will($this->returnValue($signatureDataFile));
		$this->fileAccessHelper
				->expects($this->at(1))
				->method('file_get_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//resources/codesigning/root.crt'
				)
				->will($this->returnValue(file_get_contents(__DIR__ .'/../../data/integritycheck/root.crt')));

		$expected = [
				'EXCEPTION' => [
						'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException',
						'message' => 'Signature could not get verified.',
				]
		];
		$this->assertSame($expected, $this->checker->verifyCoreSignature());
	}

	public function testVerifyCoreSignatureWithTamperedFiles() {
		$this->environmentHelper
				->expects($this->once())
				->method('getChannel')
				->will($this->returnValue('stable'));
		$this->config
				->expects($this->any())
				->method('getSystemValue')
				->with('integrity.check.disabled', false)
				->will($this->returnValue(false));

		$this->environmentHelper
				->expects($this->any())
				->method('getServerRoot')
				->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData/'));
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "dYoohBaWIFR\/To1FXEbMQB5apUhVYlEauBGSPo12nq84wxWkBx2EM3KDRgkB5Sub2tr0CgmAc2EVjPhKIEzAam26cyUb48bJziz1V6wvW7z4GZAfaJpzLkyHdSfV5117VSf5w1rDcAeZDXfGUaaNEJPWytaF4ZIxVge7f3NGshHy4odFVPADy\/u6c43BWvaOtJ4m3aJQbP6sxCO9dxwcm5yJJJR3n36jfh229sdWBxyl8BhwhH1e1DEv78\/aiL6ckKFPVNzx01R6yDFt3TgEMR97YZ\/R6lWiXG+dsJ305jNFlusLu518zBUvl7g5yjzGN778H29b2C8VLZKmi\/h1CH9jGdD72fCqCYdenD2uZKzb6dsUtXtvBmVcVT6BUGz41W1pkkEEB+YJpMrHILIxAiHRGv1+aZa9\/Oz8LWFd+BEUQjC2LJgojPnpzaG\/msw1nBkX16NNVDWWtJ25Bc\/r\/mG46rwjWB\/cmV6Lwt6KODiqlxgrC4lm9ALOCEWw+23OcYhLwNfQTYevXqHqsFfXOkhUnM8z5vDUb\/HBraB1DjFXN8iLK+1YewD4P495e+SRzrR79Oi3F8SEqRIzRLfN2rnW1BTms\/wYsz0p67cup1Slk1XlNmHwbWX25NVd2PPlLOvZRGoqcKFpIjC5few8THiZfyjiNFwt3RM0AFdZcXY=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDMzM1oXDTE2MTEwMzIxMDMzM1owDzENMAsGA1UEAwwEY29yZTCCAiIwDQYJ\r\nKoZIhvcNAQEBBQADggIPADCCAgoCggIBALb6EgHpkAqZbO5vRO8XSh7G7XGWHw5s\r\niOf4RwPXR6SE9bWZEm\/b72SfWk\/\/J6AbrD8WiOzBuT\/ODy6k5T1arEdHO+Pux0W1\r\nMxYJJI4kH74KKgMpC0SB0Rt+8WrMqV1r3hhJ46df6Xr\/xolP3oD+eLbShPcblhdS\r\nVtkZEkoev8Sh6L2wDCeHDyPxzvj1w2dTdGVO9Kztn0xIlyfEBakqvBWtcxyi3Ln0\r\nklnxlMx3tPDUE4kqvpia9qNiB1AN2PV93eNr5\/2riAzIssMFSCarWCx0AKYb54+d\r\nxLpcYFyqPJ0ydBCkF78DD45RCZet6PNYkdzgbqlUWEGGomkuDoJbBg4wzgzO0D77\r\nH87KFhYW8tKFFvF1V3AHl\/sFQ9tDHaxM9Y0pZ2jPp\/ccdiqnmdkBxBDqsiRvHvVB\r\nCn6qpb4vWGFC7vHOBfYspmEL1zLlKXZv3ezMZEZw7O9ZvUP3VO\/wAtd2vUW8UFiq\r\ns2v1QnNLN6jNh51obcwmrBvWhJy9vQIdtIjQbDxqWTHh1zUSrw9wrlklCBZ\/zrM0\r\ni8nfCFwTxWRxp3H9KoECzO\/zS5R5KIS7s3\/wq\/w9T2Ie4rcecgXwDizwnn0C\/aKc\r\nbDIjujpL1s9HO05pcD\/V3wKcPZ1izymBkmMyIbL52iRVN5FTVHeZdXPpFuq+CTQJ\r\nQ238lC+A\/KOVAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBAGoKTnh8RfJV4sQItVC2\r\nAvfJagkrIqZ3iiQTUBQGTKBsTnAqE1H7QgUSV9vSd+8rgvHkyZsRjmtyR1e3A6Ji\r\noNCXUbExC\/0iCPUqdHZIVb+Lc\/vWuv4ByFMybGPydgtLoEUX2ZrKFWmcgZFDUSRd\r\n9Uj26vtUhCC4bU4jgu6hIrR9IuxOBLQUxGTRZyAcXvj7obqRAEZwFAKQgFpfpqTb\r\nH+kjcbZSaAlLVSF7vBc1syyI8RGYbqpwvtREqJtl5IEIwe6huEqJ3zPnlP2th\/55\r\ncf3Fovj6JJgbb9XFxrdnsOsDOu\/tpnaRWlvv5ib4+SzG5wWFT5UUEo4Wg2STQiiX\r\nuVSRQxK1LE1yg84bs3NZk9FSQh4B8vZVuRr5FaJsZZkwlFlhRO\/\/+TJtXRbyNgsf\r\noMRZGi8DLGU2SGEAHcRH\/QZHq\/XDUWVzdxrSBYcy7GSpT7UDVzGv1rEJUrn5veP1\r\n0KmauAqtiIaYRm4f6YBsn0INcZxzIPZ0p8qFtVZBPeHhvQtvOt0iXI\/XUxEWOa2F\r\nK2EqhErgMK\/N07U1JJJay5tYZRtvkGq46oP\/5kQG8hYST0MDK6VihJoPpvCmAm4E\r\npEYKQ96x6A4EH9Y9mZlYozH\/eqmxPbTK8n89\/p7Ydun4rI+B2iiLnY8REWWy6+UQ\r\nV204fGUkJqW5CrKy3P3XvY9X\r\n-----END CERTIFICATE-----"
}';
		$this->fileAccessHelper
				->expects($this->at(0))
				->method('file_get_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//core/signature.json'
				)
				->will($this->returnValue($signatureDataFile));
		$this->fileAccessHelper
				->expects($this->at(1))
				->method('file_get_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//resources/codesigning/root.crt'
				)
				->will($this->returnValue(file_get_contents(__DIR__ .'/../../data/integritycheck/root.crt')));

		$expected = [
				'INVALID_HASH' => [
						'AnotherFile.txt' => [
								'expected' => '1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112',
								'current' => '7322348ba269c6d5522efe02f424fa3a0da319a7cd9c33142a5afe32a2d9af2da3a411f086fcfc96ff4301ea566f481dba0960c2abeef3594c4d930462f6584c',
						],
				],
				'FILE_MISSING' => [
						'subfolder/file.txt' => [
								'expected' => '410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b',
								'current' => '',
						],
				],
				'EXTRA_FILE' => [
						'UnecessaryFile' => [
								'expected' => '',
								'current' => 'cf83e1357eefb8bdf1542850d66d8007d620e4050b5715dc83f4a921d36ce9ce47d0d13c5d85f2b0ff8318d2877eec2f63b931bd47417a81a538327af927da3e',
						],
				],

		];
		$this->assertSame($expected, $this->checker->verifyCoreSignature());
	}

	public function testVerifyCoreWithInvalidCertificate() {
		$this->environmentHelper
				->expects($this->once())
				->method('getChannel')
				->will($this->returnValue('stable'));
		$this->config
				->expects($this->any())
				->method('getSystemValue')
				->with('integrity.check.disabled', false)
				->will($this->returnValue(false));

		$this->environmentHelper
				->expects($this->any())
				->method('getServerRoot')
				->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/app/'));
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "eXesvDm3pkek12xSwMG10y9suRES79Nye3jYNe5KYq1tTUPqRRNgxmMGAfcUro0zpLeAr2YgHeSMWtglblGOW7pmwGVPZ0O1Y4r1fE6jnep0kW+35PLIaqCorIOnCAtSzDNKBhwd1ow3zW2wC0DFouuEkIO8u5Fw28g8E8dp8zEk1xMblNPy+xtWkmYHrVJ\/dQgun1bYOF2ZFtAzatwndTI\/bGsy1i3Wsl+x6HyWKQdq8y8VObtOqKDH7uERBEpB9DHVyKflj1v1gQuEH6BhaRdATc7ee0MiQdGblraIySwYRdfo2d8i82OVKrenMB3SLwyCvDPyQ9iKpTOnSF52ZBqaqSXKM2N\/RAkweeBFQQCwcHhqxvB0cfbyHcbkOLeCZe\/tsh68IxwTiYgzvLfl7sOZ5arnZbzrPpZmB+hfV2omkoJ1tDwOWz9hEmLLNtfo2OxyUH1m0+XFaC+Gbn4WkVDgf7YZkwUcG+Qoa3oKDNMss8MEyZxewl2iDGZcf402dlidHRprlfmXbAYuVQ08\/a0HxIKYPGh\/nsMGmwnO15CWtFpAbhUA\/D5oRjsIxnvXaMDg0iAFpdu\/5Ffsj7g3EPdBkiQHNYK7YU1RRx609eH0bZyiIYHdUPw7ikLupvrebZmELqi3mqDFO99u4eISlxFJlUbUND3L4BtmWTWrKwI=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUPYoweUxCPqbDW4ntuh7QvgyqSrgwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIyNDIwNloXDTE2MTEwMzIyNDIwNlowDzENMAsGA1UEAwwEY29yZTCCAiIwDQYJ\r\nKoZIhvcNAQEBBQADggIPADCCAgoCggIBAJui3nDbjOIjxNnthdBZplphujsN6u8K\r\nQ\/62zAuSwzXVp0+3IMgM\/2sepklVE8YfCyVJ5+SUJqnqHoUWVRVfs8jL0wW6nrHM\r\n\/lsscAguWCee4iAdNOqI9kq4+DUau8J45e62XA9mrAo\/8\/NKzFE2y2WduDoQZcm+\r\n8+dwcUUHXw2jl8dfrmvEMYSqTNDdb4rGmQpeV+dr9BLqr+x03U1Q08qCG9j7mSOz\r\ncvJENjOvC5uzAh5LCuCgxqG4o+mPzB0FtNnwoRRu6IsF3Y3KacRqPc30fB\/iXDn5\r\nBPr14uNxTTYWoZJ1F0tZrLzRbXdjJJOC+dnQurTtXWZ8WjPB1BWQYK7fW6t82mkN\r\n2Qe2xen99gs9nX5yY\/sHM3TKSJdM7AVCEv\/emW3gNjkvWTtRlN\/Nc7X2ckNwXcvo\r\n0yi3fSPjzXpDgLbhp1FzrMlHDn1VzmRT3r8wLByWa\/hsxrJDsBzwunMJYhXhmeKb\r\n3wX0tN\/EUJTWBntpwVOIGnRPD51oBoQUOMaEAq\/kz8PgN181bWZkJbRuf+FWkijQ\r\no+HR2lVF1jWXXst5Uc+s9HN81Uly7X4O9MMg0QxT4+wymtGDs6AOkwMi9rgBTrRB\r\n3tLU3XL2UIwRXgmd8cPtTu\/I6Bm7LdyaYtZ3yJTxRewq3nZdWypqBhD8uhpIYVkf\r\no4bxmGkVAQVTAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBAKKAX5EHgU1grODnJ0of\r\nspFpgB1K67YvclNUyuU6NQ6zBJx1\/w1RnM7uxLcxiiWj1BbUhwZQ0ojmEHeUyi6O\r\nGrDVajwhTccDMmja3u5adhEncx65\/H+lD85IPRRkS2qBDssMDdJHhZ0uI+40nI7M\r\nMq1kFjl+6wiuqZXqps66DuLbk45g\/ZlrFIrIo3Ix5vj0OVqwT+gO4LYirJK6KgVS\r\nUttbcEsc\/yKU9ThnM8\/n4m2jstZXfzKPgOsJrQcZrFOtpj+CWmBzVElBSPlDT3Nh\r\nHSgOeTFJ8bQBxj2iG5dLA+JZJQKxyJ1gy2ZtxIJ2GyvLtSe8NUSqvfPWOaAKEUV2\r\ngniytnEFLr+PcD+9EGux6jZNuj6HmtWVThTfD5VGFmtlVU2z71ZRYY0kn6J3mmFc\r\nS2ecEcCUwqG5YNLncEUCyZhC2klWql2SHyGctCEyWWY7ikIDjVzYt2EbcFvLNBnP\r\ntybN1TYHRRZxlug00CCoOE9EZfk46FkZpDvU6KmqJRofkNZ5sj+SffyGcwYwNrDH\r\nKqe8m+9lHf3CRTIDeMu8r2xl1I6M6ZZfjabbmVP9Jd6WN4s6f1FlXDWzhlT1N0Qw\r\nGzJj6xB+SPtS3UV05tBlvbfA4e06D5G9uD7Q8ONcINtMS0xsSJ2oo82AqlpvlF\/q\r\noj7YKHsaTVGA+FxBktZHfoxD\r\n-----END CERTIFICATE-----"
}';
		$this->fileAccessHelper
				->expects($this->at(0))
				->method('file_get_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/app//core/signature.json'
				)
				->will($this->returnValue($signatureDataFile));
		$this->fileAccessHelper
				->expects($this->at(1))
				->method('file_get_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/app//resources/codesigning/root.crt'
				)
				->will($this->returnValue(file_get_contents(__DIR__ .'/../../data/integritycheck/root.crt')));

		$expected = [
				'EXCEPTION' => [
						'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException',
						'message' => 'Certificate is not valid.',
				]
		];
		$this->assertSame($expected, $this->checker->verifyCoreSignature());
	}

	public function testVerifyCoreWithDifferentScope() {
		$this->environmentHelper
				->expects($this->once())
				->method('getChannel')
				->will($this->returnValue('stable'));
		$this->config
				->expects($this->any())
				->method('getSystemValue')
				->with('integrity.check.disabled', false)
				->will($this->returnValue(false));

		$this->environmentHelper
				->expects($this->any())
				->method('getServerRoot')
				->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/app/'));
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "EL49UaSeyMAqyMtqId+tgOhhwgOevPZsRLX4j2blnybAB6fN07z0936JqZV7+eMPiE30Idx+UCY6rCFN531Kqe9vAOCdgtHUSOjjKyKc+lvULESlMb6YQcrZrvDlEMMjzjH49ewG7Ai8sNN6HrRUd9U8ws+ewSkW2DOOBItj\/21RBnkrSt+2AtGXGigEvuTm57HrCYDj8\/lSkumC2GVkjLUHeLOKYo4PRNOr6yP5mED5v7zo66AWvXl2fKv54InZcdxsAk35lyK9DGZbk\/027ZRd0AOHT3LImRLvQ+8EAg3XLlRUy0hOFGgPC+jYonMzgYvsAXAXi2j8LnLJlsLwpFwu1k1B+kZVPMumKZvP9OvJb70EirecXmz62V+Jiyuaq7ne4y7Kp5gKZT\/T8SeZ0lFtCmPfYyzBB0y8s5ldmTTmdVYHs54t\/OCCW82HzQZxnFNPzDTRa8HglsaMKrqPtW59+R4UvRKSWhB8M\/Ah57qgzycvPV4KMz\/FbD4l\/\/9chRKSlCfc2k3b8ZSHNmi+EzCKgJjWIoKdgN1yax94puU8jfn8UW+G7H9Y1Jsf\/jox6QLyYEgtV1vOHY2xLT7fVs2vhyvkN2MNjJnmQ70gFG5Qz2lBz5wi6ZpB+tOfCcpbLxWAkoWoIrmC\/Ilqh7mfmRZ43g5upjkepHNd93ONuY8=",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEwTCCAqmgAwIBAgIUWv0iujufs5lUr0svCf\/qTQvoyKAwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIyNDk1M1oXDTE2MTEwMzIyNDk1M1owEjEQMA4GA1UEAwwHU29tZUFwcDCCAiIw\r\nDQYJKoZIhvcNAQEBBQADggIPADCCAgoCggIBAK8q0x62agGSRBqeWsaeEwFfepMk\r\nF8cAobMMi50qHCv9IrOn\/ZH9l52xBrbIkErVmRjmly0d4JhD8Ymhidsh9ONKYl\/j\r\n+ishsZDM8eNNdp3Ew+fEYVvY1W7mR1qU24NWj0bzVsClI7hvPVIuw7AjfBDq1C5+\r\nA+ZSLSXYvOK2cEWjdxQfuNZwEZSjmA63DUllBIrm35IaTvfuyhU6BW9yHZxmb8+M\r\nw0xDv30D5UkE\/2N7Pa\/HQJLxCR+3zKibRK3nUyRDLSXxMkU9PnFNaPNX59VPgyj4\r\nGB1CFSToldJVPF4pzh7p36uGXZVxs8m3LFD4Ol8mhi7jkxDZjqFN46gzR0r23Py6\r\ndol9vfawGIoUwp9LvL0S7MvdRY0oazLXwClLP4OQ17zpSMAiCj7fgNT661JamPGj\r\nt5O7Zn2wA7I4ddDS\/HDTWCu98Zwc9fHIpsJPgCZ9awoqxi4Mnf7Pk9g5nnXhszGC\r\ncxxIASQKM+GhdzoRxKknax2RzUCwCzcPRtCj8AQT\/x\/mqN3PfRmlnFBNACUw9bpZ\r\nSOoNq2pCF9igftDWpSIXQ38pVpKLWowjjg3DVRmVKBgivHnUnVLyzYBahHPj0vaz\r\ntFtUFRaqXDnt+4qyUGyrT5h5pjZaTcHIcSB4PiarYwdVvgslgwnQzOUcGAzRWBD4\r\n6jV2brP5vFY3g6iPAgMBAAEwDQYJKoZIhvcNAQEFBQADggIBACTY3CCHC+Z28gCf\r\nFWGKQ3wAKs+k4+0yoti0qm2EKX7rSGQ0PHSas6uW79WstC4Rj+DYkDtIhGMSg8FS\r\nHVGZHGBCc0HwdX+BOAt3zi4p7Sf3oQef70\/4imPoKxbAVCpd\/cveVcFyDC19j1yB\r\nBapwu87oh+muoeaZxOlqQI4UxjBlR\/uRSMhOn2UGauIr3dWJgAF4pGt7TtIzt+1v\r\n0uA6FtN1Y4R5O8AaJPh1bIG0CVvFBE58esGzjEYLhOydgKFnEP94kVPgJD5ds9C3\r\npPhEpo1dRpiXaF7WGIV1X6DI\/ipWvfrF7CEy6I\/kP1InY\/vMDjQjeDnJ\/VrXIWXO\r\nyZvHXVaN\/m+1RlETsH7YO\/QmxRue9ZHN3gvvWtmpCeA95sfpepOk7UcHxHZYyQbF\r\n49\/au8j+5tsr4A83xzsT1JbcKRxkAaQ7WDJpOnE5O1+H0fB+BaLakTg6XX9d4Fo7\r\n7Gin7hVWX7pL+JIyxMzME3LhfI61+CRcqZQIrpyaafUziPQbWIPfEs7h8tCOWyvW\r\nUO8ZLervYCB3j44ivkrxPlcBklDCqqKKBzDP9dYOtS\/P4RB1NkHA9+NTvmBpTonS\r\nSFXdg9fFMD7VfjDE3Vnk+8DWkVH5wBYowTAD7w9Wuzr7DumiAULexnP\/Y7xwxLv7\r\n4B+pXTAcRK0zECDEaX3npS8xWzrB\r\n-----END CERTIFICATE-----"
}';
		$this->fileAccessHelper
				->expects($this->at(0))
				->method('file_get_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/app//core/signature.json'
				)
				->will($this->returnValue($signatureDataFile));
		$this->fileAccessHelper
				->expects($this->at(1))
				->method('file_get_contents')
				->with(
						\OC::$SERVERROOT . '/tests/data/integritycheck/app//resources/codesigning/root.crt'
				)
				->will($this->returnValue(file_get_contents(__DIR__ .'/../../data/integritycheck/root.crt')));

		$expected = [
				'EXCEPTION' => [
						'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException',
						'message' => 'Certificate is not valid for required scope. (Requested: core, current: CN=SomeApp)',
				]
		];
		$this->assertSame($expected, $this->checker->verifyCoreSignature());
	}

	public function testRunInstanceVerification() {
		$this->checker = $this->getMockBuilder('\OC\IntegrityCheck\Checker')
			->setConstructorArgs([
				$this->environmentHelper,
				$this->fileAccessHelper,
				$this->appLocator,
				$this->config,
				$this->cacheFactory,
				$this->appManager,
				\OC::$server->getTempManager()
			])
			->setMethods([
				'verifyCoreSignature',
				'verifyAppSignature',
			])
			->getMock();

		$this->checker
			->expects($this->at(0))
			->method('verifyCoreSignature');
		$this->appLocator
			->expects($this->at(0))
			->Method('getAllApps')
			->will($this->returnValue([
				'files',
				'calendar',
				'contacts',
				'dav',
			]));
		$this->appManager
			->expects($this->at(0))
			->method('isShipped')
			->with('files')
			->will($this->returnValue(true));
		$this->checker
			->expects($this->at(1))
			->method('verifyAppSignature')
			->with('files');
		$this->appManager
			->expects($this->at(1))
			->method('isShipped')
			->with('calendar')
			->will($this->returnValue(false));
		$this->appLocator
			->expects($this->at(1))
			->method('getAppPath')
			->with('calendar')
			->will($this->returnValue('/apps/calendar'));
		$this->fileAccessHelper
			->expects($this->at(0))
			->method('file_exists')
			->with('/apps/calendar/appinfo/signature.json')
			->will($this->returnValue(true));
		$this->checker
			->expects($this->at(2))
			->method('verifyAppSignature')
			->with('calendar');
		$this->appManager
				->expects($this->at(2))
				->method('isShipped')
				->with('contacts')
				->will($this->returnValue(false));
		$this->appLocator
				->expects($this->at(2))
				->method('getAppPath')
				->with('contacts')
				->will($this->returnValue('/apps/contacts'));
		$this->fileAccessHelper
				->expects($this->at(1))
				->method('file_exists')
				->with('/apps/contacts/appinfo/signature.json')
				->will($this->returnValue(false));
		$this->appManager
			->expects($this->at(3))
			->method('isShipped')
			->with('dav')
			->will($this->returnValue(true));
		$this->checker
			->expects($this->at(3))
			->method('verifyAppSignature')
			->with('dav');
		$this->config
			->expects($this->once())
			->method('deleteAppValue')
			->with('core', 'oc.integritycheck.checker');

		$this->checker->runInstanceVerification();
	}

	public function testVerifyAppSignatureWithoutSignatureDataAndCodeCheckerDisabled() {
		$this->environmentHelper
				->expects($this->once())
				->method('getChannel')
				->will($this->returnValue('stable'));
		$this->config
				->expects($this->any())
				->method('getSystemValue')
				->with('integrity.check.disabled', false)
				->will($this->returnValue(true));

		$expected = [];
		$this->assertSame($expected, $this->checker->verifyAppSignature('SomeApp'));
	}

	/**
	 * @return array
	 */
	public function channelDataProvider() {
		return [
			['stable', true],
			['git', false],
		];
	}

	/**
	 * @param string $channel
	 * @param bool $isCodeSigningEnforced
	 * @dataProvider channelDataProvider
	 */
	public function testIsCodeCheckEnforced($channel, $isCodeSigningEnforced) {
		$this->environmentHelper
			->expects($this->once())
			->method('getChannel')
			->will($this->returnValue($channel));
		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->with('integrity.check.disabled', false)
			->will($this->returnValue(false));

		$this->assertSame($isCodeSigningEnforced, $this->checker->isCodeCheckEnforced());
	}

	/**
	 * @param string $channel
	 * @dataProvider channelDataProvider
	 */
	public function testIsCodeCheckEnforcedWithDisabledConfigSwitch($channel) {
		$this->environmentHelper
				->expects($this->once())
				->method('getChannel')
				->will($this->returnValue($channel));
		$this->config
				->expects($this->any())
				->method('getSystemValue')
				->with('integrity.check.disabled', false)
				->will($this->returnValue(true));

		$result = $this->invokePrivate($this->checker, 'isCodeCheckEnforced');
		$this->assertSame(false, $result);
	}
}
