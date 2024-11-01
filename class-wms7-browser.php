<?php
/**
 * Description: Parses user-agent to get the names: browser, platform, operating system.
 * Modified the code for standard WordPress.
 *
 * PHP version 8.0.1
 * @category Wms7_Browser
 * @package  WatchMan-Site7
 * @author   Oleg Klenitsky <klenitskiy.oleg@mail.ru>
 * @version  4.2.0
 * @license  GPLv2 or later
 * @filesource
 */

if ( ! defined( "ABSPATH" ) ) {
	exit();
}

/**
 * Description: Parses user-agent to get the names: browser, platform, operating system.
 * @category Class
 * @package  WatchMan-Site7
 * @author   Chris Schuld (http://chrisschuld.com/)
 * @version  1.9
 * @license  GPLv2 or later
 */
class Wms7_Browser {
	/**
	 * Internal variable $_agent.
	 *
	 * @var string $_agent
	 */
	private $_agent = '';
	/**
	 * Internal variable $_browser_name.
	 *
	 * @var string $_browser_name
	 */
	private $_browser_name = '';
	/**
	 * Internal variable $_version.
	 *
	 * @var string $_version
	 */
	private $_version = '';
	/**
	 * Internal variable $_platform.
	 *
	 * @var string $_platform
	 */
	private $_platform = '';
	/**
	 * Internal variable $_os.
	 *
	 * @var string $_os
	 */
	private $_os = '';
	/**
	 * Internal variable $_is_mobile.
	 *
	 * @var boolean $_is_mobile
	 */
	private $_is_mobile = false;
	/**
	 * Internal variable $_is_robot.
	 *
	 * @var boolean $_is_robot
	 */
	private $_is_robot = false;

	const BROWSER_UNKNOWN = 'unknown';
	const VERSION_UNKNOWN = 'unknown';
	// http://www.opera.com/.
	const BROWSER_OPERA = 'Opera';
	// http://www.opera.com/mini/.
	const BROWSER_OPERA_MINI = 'Opera Mini';
	// http://www.webtv.net/pc/.
	const BROWSER_WEBTV = 'WebTV';
	// http://www.microsoft.com/ie/.
	const BROWSER_IE = 'Internet Explorer';
	// http://en.wikipedia.org/wiki/Internet_Explorer_Mobile.
	const BROWSER_POCKET_IE = 'Pocket Internet Explorer';
	// http://www.konqueror.org/.
	const BROWSER_KONQUEROR = 'Konqueror';
	// http://www.icab.de/.
	const BROWSER_ICAB = 'iCab';
	// http://www.omnigroup.com/applications/omniweb/.
	const BROWSER_OMNIWEB = 'OmniWeb';
	// http://www.ibphoenix.com/.
	const BROWSER_FIREBIRD = 'Firebird';
	// http://www.mozilla.com/en-US/firefox/firefox.html.
	const BROWSER_FIREFOX = 'Firefox';
	// http://www.geticeweasel.org/.
	const BROWSER_ICEWEASEL = 'Iceweasel';
	// http://wiki.mozilla.org/Projects/shiretoko.
	const BROWSER_SHIRETOKO = 'Shiretoko';
	// http://www.mozilla.com/en-US/.
	const BROWSER_MOZILLA = 'Mozilla';
	// http://www.w3.org/Amaya/.
	const BROWSER_AMAYA = 'Amaya';
	// http://en.wikipedia.org/wiki/Lynx.
	const BROWSER_LYNX = 'Lynx';
	// http://apple.com.
	const BROWSER_SAFARI = 'Safari';
	// http://apple.com.
	const BROWSER_IPHONE = 'iPhone';
	// http://apple.com.
	const BROWSER_IPOD = 'iPod';
	// http://apple.com.
	const BROWSER_IPAD = 'iPad';
	// http://www.google.com/chrome.
	const BROWSER_CHROME = 'Chrome';
	// http://www.android.com/.
	const BROWSER_ANDROID = 'Android';
	// https://en.wikipedia.org/wiki/Internet_Explorer_Mobile.
	const BROWSER_IEMOBILE = 'IEMobile';
	// http://webosose.org/.
	const BROWSER_WEB_OS = 'WebOS';
	// http://www.blackberry.com/.
	const BROWSER_BLACKBERRY = 'BlackBerry';
	// http://en.wikipedia.org/wiki/GNU_IceCat.
	const BROWSER_ICECAT = 'IceCat';
	// http://en.wikipedia.org/wiki/Web_Browser_for_S60.
	const BROWSER_NOKIA_S60 = 'Nokia S60 OSS Browser';
	// * all other WAP-based browsers on the Nokia Platform.
	const BROWSER_NOKIA = 'Nokia Browser';
	// http://explorer.msn.com/.
	const BROWSER_MSN = 'MSN Browser';
	// http://browser.netscape.com/ (DEPRECATED).
	const BROWSER_NETSCAPE_NAVIGATOR = 'Netscape Navigator';
	// http://galeon.sourceforge.net/ (DEPRECATED).
	const BROWSER_GALEON = 'Galeon';
	// http://en.wikipedia.org/wiki/NetPositive (DEPRECATED).
	const BROWSER_NETPOSITIVE = 'NetPositive';
	// http://en.wikipedia.org/wiki/History_of_Mozilla_Firefox (DEPRECATED).
	const BROWSER_PHOENIX = 'Phoenix';

	// https://en.wikipedia.org/wiki/Yandex.
	const BROWSER_YANDEXBOT = 'YandexBot';
	// https://en.wikipedia.org/wiki/Rambler_(portal).
	const BROWSER_RAMBLERBOT = 'RamblerBot';
	// https://en.wikipedia.org/wiki/Mail.Ru.
	const BROWSER_MAILRUBOT = 'MailRuBot';

	// http://search.msn.com/msnbot.htm.
	const BROWSER_MSNBOT = 'MSN Bot';
	// http://en.wikipedia.org/wiki/Googlebot.
	const BROWSER_GOOGLEBOT = 'GoogleBot';
	// http://en.wikipedia.org/wiki/Yahoo!_Slurp.
	const BROWSER_SLURP = 'Yahoo! Slurp';
	// https://en.wikipedia.org/wiki/Bingbot.
	const BROWSER_BINGBOT = 'BingBot';
	// http://bot.virusdie.com/.
	const BROWSER_VIRUSDIEBOT = 'VirusDieBot';
	// https://commoncrawl.org/faq/ ... https://velen.io/.
	const BROWSER_CRAWLERBOT = 'CrawlerBot';
	// https://www.qwant.com/.
	const BROWSER_QWANTIFYBOT = 'QwantifyBot';
	// https://en.wikipedia.org/wiki/Twitter_bot.
	const BROWSER_TWITTERBOT = 'TwitterBot';

	const PLATFORM_UNKNOWN     = 'unknown';
	const PLATFORM_WINDOWS     = 'Windows';
	const PLATFORM_WINDOWS_10  = 'Windows 10';
	const PLATFORM_WINDOWS_8_1 = 'Windows 8.1';
	const PLATFORM_WINDOWS_8   = 'Windows 8';
	const PLATFORM_WINDOWS_7   = 'Windows 7';
	const PLATFORM_WINDOWS_2K  = 'Windows 2K';
	const PLATFORM_WINDOWS_XP  = 'Windows XP';
	const PLATFORM_WINDOWS_98  = 'Windows 98';
	const PLATFORM_WINDOWS_95  = 'Windows 95';
	const PLATFORM_WINDOWS_CE  = 'Windows CE';
	const PLATFORM_APPLE       = 'Apple';
	const PLATFORM_LINUX       = 'Linux';
	const PLATFORM_OS2         = 'OS/2';
	const PLATFORM_BEOS        = 'BeOS';
	const PLATFORM_IPHONE      = 'iPhone';
	const PLATFORM_IPOD        = 'iPod';
	const PLATFORM_IPAD        = 'iPad';
	const PLATFORM_BLACKBERRY  = 'BlackBerry';
	const PLATFORM_NOKIA       = 'Nokia';
	const PLATFORM_FREEBSD     = 'FreeBSD';
	const PLATFORM_OPENBSD     = 'OpenBSD';
	const PLATFORM_NETBSD      = 'NetBSD';
	const PLATFORM_SUNOS       = 'SunOS';
	const PLATFORM_OPENSOLARIS = 'OpenSolaris';
	const PLATFORM_ANDROID     = 'Android';

	const OPERATING_SYSTEM_UNKNOWN = 'unknown';

	/**
	 * Description: Set user agent.
	 * @param string $user_agent Data of user agent.
	 */
	public function wms7_browser_data( $user_agent = '' ) {
		$this->wms7_reset();
		if ( '' !== $user_agent ) {
			$this->wms7_set_user_agent( $user_agent );
		} else {
			$this->wms7_determine();
		}
	}

	/**
	 * Description: Reset all properties.
	 */
	public function wms7_reset() {
		$_http_user_agent = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_DEFAULT );

		$this->_agent        = isset( $_http_user_agent ) ? $_http_user_agent : '';
		$this->_browser_name = self::BROWSER_UNKNOWN;
		$this->_version      = self::VERSION_UNKNOWN;
		$this->_platform     = self::PLATFORM_UNKNOWN;
		$this->_os           = self::OPERATING_SYSTEM_UNKNOWN;
		$this->_is_mobile    = false;
		$this->_is_robot     = false;
	}

	/**
	 * Description: Check to see if the specific browser is valid.
	 * @param  string $browser_name The name of the Browser.
	 * @return True if the browser is the specified browser
	 */
	public function wms7_is_browser( $browser_name ) {
		return( 0 === strcasecmp( $this->_browser_name, trim( $browser_name ) ) );
	}

	/**
	 * Description: The name of the browser. All return types are from the class contants.
	 * @return string Name of the browser
	 */
	public function wms7_get_browser() {
		return $this->_browser_name;
	}
	/**
	 * Description: Set the name of the browser.
	 * @param string $browser The name of the Browser.
	 */
	public function wms7_set_browser( $browser ) {
		$this->_browser_name = $browser;
		return $this->_browser_name;
	}
	/**
	 * Description: The name of the platform. All return types are from the class contants.
	 * @return string Name of the browser
	 */
	public function wms7_get_platform() {
		return $this->_platform;
	}
	/**
	 * Description: Set the name of the platform.
	 * @param string $platform The name of the Platform.
	 */
	public function wms7_set_platform( $platform ) {
		$this->_platform = $platform;
		return $this->_platform;
	}
	/**
	 * Description: The version of the browser.
	 * @return string Version of the browser (will only contain alpha-numeric characters and a period)
	 */
	public function wms7_get_version() {
		return $this->_version;
	}
	/**
	 * Description: Set the version of the browser.
	 * @param string $version The version of the Browser.
	 */
	public function wms7_set_version( $version ) {
		$this->_version = preg_replace( '/[^0-9,.,a-z,A-Z-]/', '', $version );
	}
	/**
	 * Description: Is the browser from a mobile device?
	 * @return boolean True if the browser is from a mobile device otherwise false
	 */
	public function wms7_is_mobile() {
		return $this->_is_mobile;
	}
	/**
	 * Description: Is the browser from a robot (ex Slurp,GoogleBot)?
	 * @return boolean True if the browser is from a robot otherwise false
	 */
	public function wms7_is_robot() {
		return $this->_is_robot;
	}
	/**
	 * Description: Set the Browser to be mobile
	 * @param boolean $value is the browser a mobile browser or not.
	 */
	protected function wms7_set_mobile( $value = true ) {
		$this->_is_mobile = $value;
	}
	/**
	 * Description: Set the Browser to be a robot.
	 * @param boolean $value is the browser a robot or not.
	 */
	protected function wms7_set_robot( $value = true ) {
		$this->_is_robot = $value;
	}
	/**
	 * Description: Get the user agent value in use to determine the browser.
	 * @return string The user agent from the HTTP header
	 */
	public function wms7_get_user_agent() {
		return $this->_agent;
	}
	/**
	 * Description: Set the user agent value (the construction will use the HTTP header value - this will overwrite it).
	 * @param string $agent_string The value for the User Agent.
	 */
	public function wms7_set_user_agent( $agent_string ) {
		$this->wms7_reset();
		$this->_agent = $agent_string;
		$this->wms7_determine();
	}
	/**
	 * Description: Used to determine if the browser is actually "chromeframe"
	 * @return boolean True if the browser is using chromeframe
	 */
	public function wms7_is_chrome_frame() {
		return( strpos( $this->_agent, 'chromeframe' ) !== false );
	}
	/**
	 * Description: Returns a formatted string with a summary of the details of the browser.
	 * @return string formatted string with a summary of the browser
	 */
	public function __toString() {
		return "<strong>Browser Name:</strong>{$this->wms7_get_browser()}<br/>\n" .
			"<strong>Platform:</strong>{$this->wms7_get_platform()}<br/>\n" .
			"<strong>Mobile:</strong>{$this->wms7_is_mobile()}<br/>\n" .
			"<strong>Browser User Agent String:</strong>{$this->wms7_get_user_agent()}<br/>";
	}
	/**
	 * Description: Protected routine to calculate and determine what the browser is in use (including platform).
	 */
	protected function wms7_determine() {
		$this->wms7_check_platform();
		$this->wms7_check_browser();
	}
	/**
	 * Protected procedure for determining the type of browser (about 40 checks for belonging to the type of browser):
	 *	Special Notes:<br>
	 *	(1) Opera must be checked before FireFox due to the odd.
	 *	User agents used in some older versions of Opera.<br>
	 *	(2) WebTV is strapped onto Internet Explorer so we must.
	 *	Check for WebTV before IE.<br>
	 *	(3) (deprecated) Galeon is based on Firefox and needs to be.
	 *	Tested before Firefox is tested.<br>
	 *	(4) OmniWeb is based on Safari so OmniWeb check must occur.
	 *	bBefore Safari.<br>
	 *	(5) Netscape 9+ is based on Firefox so Netscape checks.
	 *	Before FireFox are necessary.
	 * @return boolean True if the browser was detected otherwise false
	 */
	protected function wms7_check_browser() {
		return (
			$this->wms7_check_browser_webtv() ||
			$this->wms7_check_browser_internetexplorer() ||
			$this->wms7_check_browser_opera() ||
			$this->wms7_check_browser_galeon() ||
			$this->wms7_check_browser_netscapenavigator9plus() ||
			$this->wms7_check_browser_firefox() ||
			$this->wms7_check_browser_chrome() ||
			$this->wms7_check_browser_omniweb() ||

			// common mobile.
			$this->wms7_check_browser_web_os() ||
			$this->wms7_check_browser_iemobile() ||
			$this->wms7_check_browser_android() ||
			$this->wms7_check_browser_ipad() ||
			$this->wms7_check_browser_ipod() ||
			$this->wms7_check_browser_iphone() ||
			$this->wms7_check_browser_blackberry() ||
			$this->wms7_check_browser_nokia() ||

			// common bots.
			$this->wms7_check_browser_mailrubot() ||
			$this->wms7_check_browser_ramblerbot() ||
			$this->wms7_check_browser_yandexbot() ||
			$this->wms7_check_browser_googlebot() ||
			$this->wms7_check_browser_msnbot() ||
			$this->wms7_check_browser_slurp() ||
			$this->wms7_check_browser_bingbot() ||
			$this->wms7_check_browser_virusdiebot() ||
			$this->wms7_check_browser_crawlerbot() ||
			$this->wms7_check_browser_qwantifybot() ||
			$this->wms7_check_browser_twitterbot() ||

			// WebKit base check (post mobile and others).
			$this->wms7_check_browser_safari() ||

			// everyone else.
			$this->wms7_check_browser_positive() ||
			$this->wms7_check_browser_firebird() ||
			$this->wms7_check_browser_konqueror() ||
			$this->wms7_check_browser_icab() ||
			$this->wms7_check_browser_phoenix() ||
			$this->wms7_check_browser_amaya() ||
			$this->wms7_check_browser_lynx() ||
			$this->wms7_check_browser_shiretoko() ||
			$this->wms7_check_browser_icecat() ||
			// Mozilla is such an open standard that you must check it last.
			$this->wms7_check_browser_mozilla()
		);
	}

	/**
	 * Description: Determine if the user is using a BlackBerry (last updated 1.7)
	 * @return boolean True if the browser is the BlackBerry browser otherwise false
	 */
	protected function wms7_check_browser_blackberry() {
		if ( stripos( $this->_agent, 'blackberry' ) !== false ) {
			$aresult  = explode( '/', stristr( $this->_agent, 'BlackBerry' ) );
			$aversion = explode( ' ', $aresult[1] );
			$this->wms7_set_version( $aversion[0] );
			$this->_browser_name = self::BROWSER_BLACKBERRY;
			$this->wms7_set_mobile( true );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is the MailRuBot or not
	 * @return boolean True if the browser is the MailRuBot otherwise false
	 */
	protected function wms7_check_browser_mailrubot() {
		if ( stripos( $this->_agent, 'Mail.RU' ) !== false ) {
			$this->_browser_name = self::BROWSER_MAILRUBOT;
			$this->wms7_set_robot( true );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is the RamblerBot or not
	 * @return boolean True if the browser is the RamblerBot otherwise false
	 */
	protected function wms7_check_browser_ramblerbot() {
		if ( stripos( $this->_agent, 'Rambler' ) !== false ) {
			$this->_browser_name = self::BROWSER_RAMBLERBOT;
			$this->wms7_set_robot( true );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is the YandexBot or not
	 * @return boolean True if the browser is the YandexBot otherwise false
	 */
	protected function wms7_check_browser_yandexbot() {
		if ( stripos( $this->_agent, 'yandex' ) !== false ) {
			$this->_browser_name = self::BROWSER_YANDEXBOT;
			$this->wms7_set_robot( true );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is the Twitter or not
	 * @return boolean True if the browser is the Twitter otherwise false
	 */
	protected function wms7_check_browser_twitterbot() {
		if ( preg_match( '/TwitterBot/i', $this->_agent ) ) {
			$this->_browser_name = self::BROWSER_TWITTERBOT;
			$this->wms7_set_robot( true );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is the Qwantify or not
	 * @return boolean True if the browser is the Qwantify otherwise false
	 */
	protected function wms7_check_browser_qwantifybot() {
		if ( preg_match( '/Qwantify/i', $this->_agent ) ) {
			$this->_browser_name = self::BROWSER_QWANTIFYBOT;
			$this->wms7_set_robot( true );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is the GoogleBot or not (last updated 1.7)
	 * @return boolean True if the browser is the GoogletBot otherwise false
	 */
	protected function wms7_check_browser_googlebot() {
		if ( preg_match( '/googlebot|Google-Site-Verification/i', $this->_agent ) ) {
			$this->_browser_name = self::BROWSER_GOOGLEBOT;
			$this->wms7_set_robot( true );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is the MSNBot or not (last updated 1.9)
	 * @return boolean True if the browser is the MSNBot otherwise false
	 */
	protected function wms7_check_browser_msnbot() {
		if ( stripos( $this->_agent, 'msnbot' ) !== false ) {
			$this->_browser_name = self::BROWSER_MSNBOT;
			$this->wms7_set_robot( true );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is the BingBot or not (last updated 1.9)
	 * @return boolean True if the browser is the BingBot otherwise false
	 */
	protected function wms7_check_browser_bingbot() {
		if ( stripos( $this->_agent, 'bingbot' ) !== false ) {
			$this->_browser_name = self::BROWSER_BINGBOT;
			$this->wms7_set_robot( true );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is the Yahoo! Slurp Robot or not (last updated 1.7)
	 * @return boolean True if the browser is the Yahoo! Slurp Robot otherwise false
	 */
	protected function wms7_check_browser_slurp() {
		if ( stripos( $this->_agent, 'slurp' ) !== false ) {
			$this->_browser_name = self::BROWSER_SLURP;
			$this->wms7_set_robot( true );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is the VirusDieBot or not
	 * @return boolean True if the browser is the VirusDieBot otherwise false
	 */
	protected function wms7_check_browser_virusdiebot() {
		if ( stripos( $this->_agent, 'Virusdie crawler' ) !== false ) {
			$this->_browser_name = self::BROWSER_VIRUSDIEBOT;
			$this->wms7_set_robot( true );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is the CrawlerBot or not
	 * @return boolean True if the browser is the CrawlerBot otherwise false
	 */
	protected function wms7_check_browser_crawlerbot() {
		if ( preg_match( '/crawl|SemrushBot|DotBot|MJ12bot|NetcraftSurveyAgent|SeznamBot|python/i', $this->_agent ) ) {
			$this->_browser_name = self::BROWSER_CRAWLERBOT;
			$this->wms7_set_robot( true );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is Internet Explorer or not (last updated 1.7)
	 * @return boolean True if the browser is Internet Explorer otherwise false
	 */
	protected function wms7_check_browser_internetexplorer() {

		// Test for v1 - v1.5 IE.
		if ( stripos( $this->_agent, 'microsoft internet explorer' ) !== false ) {
			$this->wms7_set_browser( self::BROWSER_IE );
			$this->wms7_set_version( '1.0' );
			$aresult = stristr( $this->_agent, '/' );
			if ( preg_match( '/308|425|426|474|0b1/i', $aresult ) ) {
				$this->wms7_set_version( '1.5' );
			}
			return true;
			// Test for versions > 1.5.
		} elseif ( stripos( $this->_agent, 'msie' ) !== false && stripos( $this->_agent, 'opera' ) === false ) {
			// See if the browser is the odd MSN Explorer.
			if ( stripos( $this->_agent, 'msnb' ) !== false ) {
				$aresult = explode( ' ', stristr( str_replace( ';', '; ', $this->_agent ), 'MSN' ) );
				$this->wms7_set_browser( self::BROWSER_MSN );
				$this->wms7_set_version( str_replace( array( '(', ')', ';' ), '', $aresult[1] ) );
				return true;
			}
			$aresult = explode( ' ', stristr( str_replace( ';', '; ', $this->_agent ), 'msie' ) );
			$this->wms7_set_browser( self::BROWSER_IE );
			$this->wms7_set_version( str_replace( array( '(', ')', ';' ), '', $aresult[1] ) );
			return true;
			// Test for Pocket IE.
		} elseif ( stripos( $this->_agent, 'mspie' ) !== false || stripos( $this->_agent, 'pocket' ) !== false ) {
			$aresult = explode( ' ', stristr( $this->_agent, 'mspie' ) );
			$this->wms7_set_platform( self::PLATFORM_WINDOWS_CE );
			$this->wms7_set_browser( self::BROWSER_POCKET_IE );
			$this->wms7_set_mobile( true );

			if ( stripos( $this->_agent, 'mspie' ) !== false ) {
				$this->wms7_set_version( $aresult[1] );
			} else {
				$aversion = explode( '/', $this->_agent );
				$this->wms7_set_version( $aversion[1] );
			}
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is Opera or not (last updated 1.7)
	 * @return boolean True if the browser is Opera otherwise false
	 */
	protected function wms7_check_browser_opera() {
		if ( stripos( $this->_agent, 'opera mini' ) !== false ) {
			$resultant = stristr( $this->_agent, 'opera mini' );
			if ( preg_match( '/\//', $resultant ) ) {
				$aresult  = explode( '/', $resultant );
				$aversion = explode( ' ', $aresult[1] );
				$this->wms7_set_version( $aversion[0] );
			} else {
				$aversion = explode( ' ', stristr( $resultant, 'opera mini' ) );
				$this->wms7_set_version( $aversion[1] );
			}
			$this->_browser_name = self::BROWSER_OPERA_MINI;
			$this->wms7_set_mobile( true );
			return true;
		} elseif ( stripos( $this->_agent, 'opera' ) !== false ) {
			$resultant = stristr( $this->_agent, 'opera' );
			if ( preg_match( '/Version\/(10.*)$/', $resultant, $matches ) ) {
				$this->wms7_set_version( $matches[1] );
			} elseif ( preg_match( '/\//', $resultant ) ) {
				$aresult  = explode( '/', str_replace( '(', ' ', $resultant ) );
				$aversion = explode( ' ', $aresult[1] );
				$this->wms7_set_version( $aversion[0] );
			} else {
				$aversion = explode( ' ', stristr( $resultant, 'opera' ) );
				$this->wms7_set_version( isset( $aversion[1] ) ? $aversion[1] : '' );
			}
			$this->_browser_name = self::BROWSER_OPERA;
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is Chrome or not (last updated 1.7)
	 * @return boolean True if the browser is Chrome otherwise false
	 */
	protected function wms7_check_browser_chrome() {
		if ( stripos( $this->_agent, 'Chrome' ) !== false ) {
			$aresult  = explode( '/', stristr( $this->_agent, 'Chrome' ) );
			if (isset($aresult[1])) {
				$aversion = explode( ' ', $aresult[1] );
				$this->wms7_set_version( $aversion[0] );
			}
			$this->wms7_set_browser( self::BROWSER_CHROME );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is WebTv or not (last updated 1.7)
	 * @return boolean True if the browser is WebTv otherwise false
	 */
	protected function wms7_check_browser_webtv() {
		if ( stripos( $this->_agent, 'webtv' ) !== false ) {
			$aresult  = explode( '/', stristr( $this->_agent, 'webtv' ) );
			$aversion = explode( ' ', $aresult[1] );
			$this->wms7_set_version( $aversion[0] );
			$this->wms7_set_browser( self::BROWSER_WEBTV );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is NetPositive or not (last updated 1.7)
	 * @return boolean True if the browser is NetPositive otherwise false
	 */
	protected function wms7_check_browser_positive() {
		if ( stripos( $this->_agent, 'NetPositive' ) !== false ) {
			$aresult  = explode( '/', stristr( $this->_agent, 'NetPositive' ) );
			$aversion = explode( ' ', $aresult[1] );
			$this->wms7_set_version( str_replace( array( '(', ')', ';' ), '', $aversion[0] ) );
			$this->wms7_set_browser( self::BROWSER_NETPOSITIVE );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is Galeon or not (last updated 1.7)
	 * @return boolean True if the browser is Galeon otherwise false
	 */
	protected function wms7_check_browser_galeon() {
		if ( stripos( $this->_agent, 'galeon' ) !== false ) {
			$aresult  = explode( ' ', stristr( $this->_agent, 'galeon' ) );
			$aversion = explode( '/', $aresult[0] );
			$this->wms7_set_version( $aversion[1] );
			$this->wms7_set_browser( self::BROWSER_GALEON );
			return true;
		}
		return false;
	}

	/**
	 * Description:Determine if the browser is Konqueror or not (last updated 1.7)
	 * @return boolean True if the browser is Konqueror otherwise false
	 */
	protected function wms7_check_browser_konqueror() {
		if ( stripos( $this->_agent, 'Konqueror' ) !== false ) {
			$aresult  = explode( ' ', stristr( $this->_agent, 'Konqueror' ) );
			$aversion = explode( '/', $aresult[0] );
			$this->wms7_set_version( $aversion[1] );
			$this->wms7_set_browser( self::BROWSER_KONQUEROR );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is iCab or not (last updated 1.7)
	 * @return boolean True if the browser is iCab otherwise false
	 */
	protected function wms7_check_browser_icab() {
		if ( stripos( $this->_agent, 'icab' ) !== false ) {
			$aversion = explode( ' ', stristr( str_replace( '/', ' ', $this->_agent ), 'icab' ) );
			$this->wms7_set_version( $aversion[1] );
			$this->wms7_set_browser( self::BROWSER_ICAB );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is OmniWeb or not (last updated 1.7)
	 * @return boolean True if the browser is OmniWeb otherwise false
	 */
	protected function wms7_check_browser_omniweb() {
		if ( stripos( $this->_agent, 'omniweb' ) !== false ) {
			$aresult  = explode( '/', stristr( $this->_agent, 'omniweb' ) );
			$aversion = explode( ' ', isset( $aresult[1] ) ? $aresult[1] : '' );
			$this->wms7_set_version( $aversion[0] );
			$this->wms7_set_browser( self::BROWSER_OMNIWEB );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is Phoenix or not (last updated 1.7)
	 * @return boolean True if the browser is Phoenix otherwise false
	 */
	protected function wms7_check_browser_phoenix() {
		if ( stripos( $this->_agent, 'Phoenix' ) !== false ) {
			$aversion = explode( '/', stristr( $this->_agent, 'Phoenix' ) );
			$this->wms7_set_version( $aversion[1] );
			$this->wms7_set_browser( self::BROWSER_PHOENIX );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is Firebird or not (last updated 1.7)
	 * @return boolean True if the browser is Firebird otherwise false
	 */
	protected function wms7_check_browser_firebird() {
		if ( stripos( $this->_agent, 'Firebird' ) !== false ) {
			$aversion = explode( '/', stristr( $this->_agent, 'Firebird' ) );
			$this->wms7_set_version( $aversion[1] );
			$this->wms7_set_browser( self::BROWSER_FIREBIRD );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is Netscape Navigator 9+ or not (last updated 1.7)
	 * NOTE: (http://browser.netscape.com/ - Official support ended on March 1st, 2008)
	 * @return boolean True if the browser is Netscape Navigator 9+ otherwise false
	 */
	protected function wms7_check_browser_netscapenavigator9plus() {
		if ( stripos( $this->_agent, 'Firefox' ) !== false && preg_match( '/Navigator\/([^ ]*)/i', $this->_agent, $matches ) ) {
			$this->wms7_set_version( $matches[1] );
			$this->wms7_set_browser( self::BROWSER_NETSCAPE_NAVIGATOR );
			return true;
		} elseif ( stripos( $this->_agent, 'Firefox' ) === false && preg_match( '/Netscape6?\/([^ ]*)/i', $this->_agent, $matches ) ) {
			$this->wms7_set_version( $matches[1] );
			$this->wms7_set_browser( self::BROWSER_NETSCAPE_NAVIGATOR );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is Shiretoko or not (https://wiki.mozilla.org/Projects/shiretoko) (last updated 1.7)
	 * @return boolean True if the browser is Shiretoko otherwise false
	 */
	protected function wms7_check_browser_shiretoko() {
		if ( stripos( $this->_agent, 'Mozilla' ) !== false && preg_match( '/Shiretoko\/([^ ]*)/i', $this->_agent, $matches ) ) {
			$this->wms7_set_version( $matches[1] );
			$this->wms7_set_browser( self::BROWSER_SHIRETOKO );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is Ice Cat or not (http://en.wikipedia.org/wiki/GNU_IceCat) (last updated 1.7)
	 * @return boolean True if the browser is Ice Cat otherwise false
	 */
	protected function wms7_check_browser_icecat() {
		if ( stripos( $this->_agent, 'Mozilla' ) !== false && preg_match( '/IceCat\/([^ ]*)/i', $this->_agent, $matches ) ) {
			$this->wms7_set_version( $matches[1] );
			$this->wms7_set_browser( self::BROWSER_ICECAT );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is Nokia or not (last updated 1.7)
	 * @return boolean True if the browser is Nokia otherwise false
	 */
	protected function wms7_check_browser_nokia() {
		if ( preg_match( '/Nokia([^\/]+)\/([^ SP]+)/i', $this->_agent, $matches ) ) {
			$this->wms7_set_version( $matches[2] );
			if ( stripos( $this->_agent, 'Series60' ) !== false || strpos( $this->_agent, 'S60' ) !== false ) {
				$this->wms7_set_browser( self::BROWSER_NOKIA_S60 );
			} else {
				$this->wms7_set_browser( self::BROWSER_NOKIA );
			}
			$this->wms7_set_mobile( true );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is Firefox or not (last updated 1.7)
	 * @return boolean True if the browser is Firefox otherwise false
	 */
	protected function wms7_check_browser_firefox() {
		if ( stripos( $this->_agent, 'safari' ) === false ) {
			if ( preg_match( '/Firefox[\/ \(]([^ ;\)]+)/i', $this->_agent, $matches ) ) {
				$this->wms7_set_version( $matches[1] );
				$this->wms7_set_browser( self::BROWSER_FIREFOX );
				return true;
			} elseif ( preg_match( '/Firefox$/i', $this->_agent, $matches ) ) {
				$this->wms7_set_version( '' );
				$this->wms7_set_browser( self::BROWSER_FIREFOX );
				return true;
			}
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is Firefox or not (last updated 1.7)
	 * @return boolean True if the browser is Firefox otherwise false
	 */
	protected function wms7_check_browser_iceweasel() {
		if ( stripos( $this->_agent, 'Iceweasel' ) !== false ) {
			$aresult  = explode( '/', stristr( $this->_agent, 'Iceweasel' ) );
			$aversion = explode( ' ', $aresult[1] );
			$this->wms7_set_version( $aversion[0] );
			$this->wms7_set_browser( self::BROWSER_ICEWEASEL );
			return true;
		}
		return false;
	}
	/**
	 * Description: Determine if the browser is Mozilla or not (last updated 1.7)
	 * @return boolean True if the browser is Mozilla otherwise false
	 */
	protected function wms7_check_browser_mozilla() {
		if ( stripos( $this->_agent, 'mozilla' ) !== false && preg_match( '/rv:[0-9].[0-9][a-b]?/i', $this->_agent ) && stripos( $this->_agent, 'netscape' ) === false ) {
			$aversion = explode( ' ', stristr( $this->_agent, 'rv:' ) );
			preg_match( '/rv:[0-9].[0-9][a-b]?/i', $this->_agent, $aversion );
			$this->wms7_set_version( str_replace( 'rv:', '', $aversion[0] ) );
			$this->wms7_set_browser( self::BROWSER_MOZILLA );
			return true;
		} elseif ( stripos( $this->_agent, 'mozilla' ) !== false && preg_match( '/rv:[0-9]\.[0-9]/i', $this->_agent ) && stripos( $this->_agent, 'netscape' ) === false ) {
			$aversion = explode( '', stristr( $this->_agent, 'rv:' ) );
			$this->wms7_set_version( str_replace( 'rv:', '', $aversion[0] ) );
			$this->wms7_set_browser( self::BROWSER_MOZILLA );
			return true;
		} elseif ( stripos( $this->_agent, 'mozilla' ) !== false && preg_match( '/mozilla\/([^ ]*)/i', $this->_agent, $matches ) && stripos( $this->_agent, 'netscape' ) === false ) {
			$this->wms7_set_version( $matches[1] );
			$this->wms7_set_browser( self::BROWSER_MOZILLA );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is Lynx or not (last updated 1.7)
	 * @return boolean True if the browser is Lynx otherwise false
	 */
	protected function wms7_check_browser_lynx() {
		if ( stripos( $this->_agent, 'lynx' ) !== false ) {
			$aresult  = explode( '/', stristr( $this->_agent, 'Lynx' ) );
			$aversion = explode( ' ', ( isset( $aresult[1] ) ? $aresult[1] : '' ) );
			$this->wms7_set_version( $aversion[0] );
			$this->wms7_set_browser( self::BROWSER_LYNX );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is Amaya or not (last updated 1.7)
	 * @return boolean True if the browser is Amaya otherwise false
	 */
	protected function wms7_check_browser_amaya() {
		if ( stripos( $this->_agent, 'amaya' ) !== false ) {
			$aresult  = explode( '/', stristr( $this->_agent, 'Amaya' ) );
			$aversion = explode( ' ', $aresult[1] );
			$this->wms7_set_version( $aversion[0] );
			$this->wms7_set_browser( self::BROWSER_AMAYA );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is Safari or not (last updated 1.7)
	 * @return boolean True if the browser is Safari otherwise false
	 */
	protected function wms7_check_browser_safari() {
		if ( stripos( $this->_agent, 'Safari' ) !== false && stripos( $this->_agent, 'iPhone' ) === false && stripos( $this->_agent, 'iPod' ) === false ) {
			$aresult = explode( '/', stristr( $this->_agent, 'Version' ) );
			if ( isset( $aresult[1] ) ) {
				$aversion = explode( ' ', $aresult[1] );
				$this->wms7_set_version( $aversion[0] );
			} else {
				$this->wms7_set_version( self::VERSION_UNKNOWN );
			}
			$this->wms7_set_browser( self::BROWSER_SAFARI );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is iPhone or not (last updated 1.7)
	 * @return boolean True if the browser is iPhone otherwise false
	 */
	protected function wms7_check_browser_iphone() {
		if ( stripos( $this->_agent, 'iPhone' ) !== false ) {
			$aresult = explode( '/', stristr( $this->_agent, 'Version' ) );
			if ( isset( $aresult[1] ) ) {
				$aversion = explode( ' ', $aresult[1] );
				$this->wms7_set_version( $aversion[0] );
			} else {
				$this->wms7_set_version( self::VERSION_UNKNOWN );
			}
			$this->wms7_set_mobile( true );
			$this->wms7_set_browser( self::BROWSER_IPHONE );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is iPod or not (last updated 1.7)
	 * @return boolean True if the browser is iPad otherwise false
	 */
	protected function wms7_check_browser_ipad() {
		if ( stripos( $this->_agent, 'iPad' ) !== false ) {
			$aresult = explode( '/', stristr( $this->_agent, 'Version' ) );
			if ( isset( $aresult[1] ) ) {
				$aversion = explode( ' ', $aresult[1] );
				$this->wms7_set_version( $aversion[0] );
			} else {
				$this->wms7_set_version( self::VERSION_UNKNOWN );
			}
			$this->wms7_set_mobile( true );
			$this->wms7_set_browser( self::BROWSER_IPAD );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is iPod or not (last updated 1.7)
	 * @return boolean True if the browser is iPod otherwise false
	 */
	protected function wms7_check_browser_ipod() {
		if ( stripos( $this->_agent, 'iPod' ) !== false ) {
			$aresult = explode( '/', stristr( $this->_agent, 'Version' ) );
			if ( isset( $aresult[1] ) ) {
				$aversion = explode( ' ', $aresult[1] );
				$this->wms7_set_version( $aversion[0] );
			} else {
				$this->wms7_set_version( self::VERSION_UNKNOWN );
			}
			$this->wms7_set_mobile( true );
			$this->wms7_set_browser( self::BROWSER_IPOD );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is Android or not (last updated 1.7)
	 * @return boolean True if the browser is Android otherwise false
	 */
	protected function wms7_check_browser_android() {
		if ( stripos( $this->_agent, 'Android' ) !== false ) {
			$aresult = explode( ' ', stristr( $this->_agent, 'Android' ) );
			if ( isset( $aresult[1] ) ) {
				$aversion = explode( ' ', $aresult[1] );
				$this->wms7_set_version( $aversion[0] );
			} else {
				$this->wms7_set_version( self::VERSION_UNKNOWN );
			}
			$this->wms7_set_mobile( true );
			$this->wms7_set_browser( self::BROWSER_ANDROID );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is IEMobile or not (add new function)
	 * @return boolean True if the browser is IEMobile otherwise false
	 */
	protected function wms7_check_browser_iemobile() {
		if ( stripos( $this->_agent, 'IEMobile' ) !== false ) {
			$aresult = explode( ' ', stristr( $this->_agent, 'IEMobile' ) );
			if ( isset( $aresult[1] ) ) {
				$aversion = explode( ' ', $aresult[1] );
				$this->wms7_set_version( $aversion[0] );
			} else {
				$this->wms7_set_version( self::VERSION_UNKNOWN );
			}
			$this->wms7_set_mobile( true );
			$this->wms7_set_browser( self::BROWSER_IEMOBILE );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine if the browser is webOS or not (add new function)
	 * @return boolean True if the browser is webOS otherwise false
	 */
	protected function wms7_check_browser_web_os() {
		if ( stripos( $this->_agent, 'webOS' ) !== false ) {
			$aresult = explode( ' ', stristr( $this->_agent, 'webOS' ) );
			if ( isset( $aresult[1] ) ) {
				$aversion = explode( ' ', $aresult[1] );
				$this->wms7_set_version( $aversion[0] );
			} else {
				$this->wms7_set_version( self::VERSION_UNKNOWN );
			}
			$this->wms7_set_mobile( true );
			$this->wms7_set_browser( self::BROWSER_WEB_OS );
			return true;
		}
		return false;
	}

	/**
	 * Description: Determine the user's platform (last updated 1.7)
	 */
	protected function wms7_check_platform() {
		if ( stripos( $this->_agent, 'Windows NT 10' ) !== false ) {
			$this->_platform = self::PLATFORM_WINDOWS_10;
		} elseif ( stripos( $this->_agent, 'Windows NT 6.3' ) !== false ) {
			$this->_platform = self::PLATFORM_WINDOWS_8_1;
		} elseif ( stripos( $this->_agent, 'Windows NT 6.2' ) !== false ) {
			$this->_platform = self::PLATFORM_WINDOWS_8;
		} elseif ( stripos( $this->_agent, 'Windows NT 6.1' ) !== false ) {
			$this->_platform = self::PLATFORM_WINDOWS_7;
		} elseif ( stripos( $this->_agent, 'Windows NT 5.1' ) !== false ) {
			$this->_platform = self::PLATFORM_WINDOWS_XP;
		} elseif ( stripos( $this->_agent, 'Windows NT 5.0' ) !== false ) {
			$this->_platform = self::PLATFORM_WINDOWS_2K;
		} elseif ( stripos( $this->_agent, 'Windows 98' ) !== false ) {
			$this->_platform = self::PLATFORM_WINDOWS_98;
		} elseif ( stripos( $this->_agent, 'Windows 95' ) !== false ) {
			$this->_platform = self::PLATFORM_WINDOWS_95;
		} elseif ( stripos( $this->_agent, 'Windows CE' ) !== false ) {
			$this->_platform = self::PLATFORM_WINDOWS_CE;
		} elseif ( stripos( $this->_agent, 'iPad' ) !== false ) {
			$this->_platform = self::PLATFORM_IPAD;
		} elseif ( stripos( $this->_agent, 'iPod' ) !== false ) {
			$this->_platform = self::PLATFORM_IPOD;
		} elseif ( stripos( $this->_agent, 'iPhone' ) !== false ) {
			$this->_platform = self::PLATFORM_IPHONE;
		} elseif ( stripos( $this->_agent, 'mac' ) !== false ) {
			$this->_platform = self::PLATFORM_APPLE;
		} elseif ( stripos( $this->_agent, 'android' ) !== false ) {
			$this->_platform = self::PLATFORM_ANDROID;
		} elseif ( stripos( $this->_agent, 'linux' ) !== false ) {
			$this->_platform = self::PLATFORM_LINUX;
		} elseif ( stripos( $this->_agent, 'Nokia' ) !== false ) {
			$this->_platform = self::PLATFORM_NOKIA;
		} elseif ( stripos( $this->_agent, 'BlackBerry' ) !== false ) {
			$this->_platform = self::PLATFORM_BLACKBERRY;
		} elseif ( stripos( $this->_agent, 'FreeBSD' ) !== false ) {
			$this->_platform = self::PLATFORM_FREEBSD;
		} elseif ( stripos( $this->_agent, 'OpenBSD' ) !== false ) {
			$this->_platform = self::PLATFORM_OPENBSD;
		} elseif ( stripos( $this->_agent, 'NetBSD' ) !== false ) {
			$this->_platform = self::PLATFORM_NETBSD;
		} elseif ( stripos( $this->_agent, 'OpenSolaris' ) !== false ) {
			$this->_platform = self::PLATFORM_OPENSOLARIS;
		} elseif ( stripos( $this->_agent, 'SunOS' ) !== false ) {
			$this->_platform = self::PLATFORM_SUNOS;
		} elseif ( stripos( $this->_agent, 'OS\/2' ) !== false ) {
			$this->_platform = self::PLATFORM_OS2;
		} elseif ( stripos( $this->_agent, 'BeOS' ) !== false ) {
			$this->_platform = self::PLATFORM_BEOS;
		} elseif ( stripos( $this->_agent, 'win' ) !== false ) {
			$this->_platform = self::PLATFORM_WINDOWS;
		}
	}
}
