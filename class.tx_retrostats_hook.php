<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Loek Hilgersom <typo3extensions@netcoop.nl>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */


/**
 * Plugin '' for the 'retrostats' extension.
 *
 * @author	Loek Hilgersom <typo3extensions@netcoop.nl>
 * @package	TYPO3
 * @subpackage	tx_retrostats
 */
class tx_retrostats_hook {

	var $prefixId      = 'tx_retrostats';		// Same as class name
	var $scriptRelPath = 'class.tx_retrostats_hook.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'retrostats';	// The extension key.
	var $pi_checkCHash = true;

	function statisticsInitHook(&$params, &$parentObject) {
		if (!is_object($GLOBALS['TT'])) {
			$GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\TimeTracker();
			$GLOBALS['TT']->start();
		}

		$this->pObj = &$parentObject;
		if ($parentObject->tmpl->loaded && is_array($parentObject->pSetup)) {
			// Initialize statistics handling: Check filename and permissions
			$setStatPageName = $this->statistics_init();

			// We want nice names, so we need to handle the charset
			if ($setStatPageName) {
				$this->statistics_init_pagename();
			}
		}
	}

	function statisticsHook(&$params, &$parentObject) {
		$this->pObj = &$parentObject;
		$this->statistics();
	}


	// From ln 3703 in class.tslib_fe.php in TYPO3 4.7:


	/**
	 * Initialize file-based statistics handling: Check filename and permissions, and create the logfile if it does not exist yet.
	 * This function should be called with care because it might overwrite existing settings otherwise.
	 *
	 * @return	boolean		TRUE if statistics are enabled (will require some more processing after charset handling is initialized)
	 * @access private
	 */
	protected function statistics_init() {
		$setStatPageName = FALSE;

		$theLogFile = $GLOBALS['TYPO3_CONF_VARS']['FE']['logfile_dir'].strftime($this->pObj->config['config']['stat_apache_logfile']);

		// Add PATH_site left to $theLogFile if the path is not absolute yet
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($theLogFile)) {
			$theLogFile = PATH_site.$theLogFile;
		}

		if ($this->pObj->config['config']['stat_apache'] && $this->pObj->config['config']['stat_apache_logfile'] && !strstr($this->pObj->config['config']['stat_apache_logfile'],'/')) {
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($theLogFile)) {
				if (!@is_file($theLogFile)) {
					touch($theLogFile);	// Try to create the logfile
					\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($theLogFile);
				}

				if (@is_file($theLogFile) && @is_writable($theLogFile)) {
					$this->pObj->config['stat_vars']['logFile'] = $theLogFile;
					$setStatPageName = TRUE;	// Set page name later on
				} else {
					$GLOBALS['TT']->setTSlogMessage('Could not set logfile path. Check filepath and permissions.',3);
				}
			}
		}

		return $setStatPageName;
	}

	/**
	 * Set the pagename for the logfile entry
	 *
	 * @return	void
	 * @access private
	 */
	protected function statistics_init_pagename() {
		if (preg_match('/utf-?8/i', $this->pObj->config['config']['stat_apache_niceTitle'])) {	// Make life easier and accept variants for utf-8
			$this->pObj->config['config']['stat_apache_niceTitle'] = 'utf-8';
		}

		if ($this->pObj->config['config']['stat_apache_niceTitle'] == 'utf-8') {
			$shortTitle = $this->pObj->csConvObj->utf8_encode($this->pObj->page['title'],$this->pObj->renderCharset);
		} elseif ($this->pObj->config['config']['stat_apache_niceTitle']) {
			$shortTitle = $this->pObj->csConvObj->specCharsToASCII($this->pObj->renderCharset,$this->pObj->page['title']);
		} else {
			$shortTitle = $this->pObj->page['title'];
		}

		$len = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->pObj->config['config']['stat_apache_pageLen'],1,100,30);
		if ($this->pObj->config['config']['stat_apache_niceTitle'] == 'utf-8') {
			$shortTitle = rawurlencode($this->pObj->csConvObj->substr('utf-8',$shortTitle,0,$len));
		} else {
			$shortTitle = substr(preg_replace('/[^.[:alnum:]_-]/','_',$shortTitle),0,$len);
		}

		$pageName = $this->pObj->config['config']['stat_apache_pagenames'] ? $this->pObj->config['config']['stat_apache_pagenames'] : '[path][title]--[uid].html';
		$pageName = str_replace('[title]', $shortTitle ,$pageName);
		$pageName = str_replace('[uid]',$this->pObj->page['uid'],$pageName);
		$pageName = str_replace('[alias]',$this->pObj->page['alias'],$pageName);
		$pageName = str_replace('[type]',$this->pObj->type,$pageName);
		$pageName = str_replace('[request_uri]',\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'),$pageName);

		$temp = $this->pObj->config['rootLine'];
		if ($temp) {	// rootLine does not exist if this function is called at early stage (e.g. if DB connection failed)
			array_pop($temp);
			if ($this->pObj->config['config']['stat_apache_noRoot']) {
				array_shift($temp);
			}

			$len = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->pObj->config['config']['stat_titleLen'],1,100,20);
			if ($this->pObj->config['config']['stat_apache_niceTitle'] == 'utf-8') {
				$path = '';
				$c = count($temp);
				for ($i=0; $i<$c; $i++) {
					if ($temp[$i]['uid']) {
						$p = $this->pObj->csConvObj->crop('utf-8',$this->pObj->csConvObj->utf8_encode($temp[$i]['title'],$this->pObj->renderCharset),$len,"\xE2\x80\xA6");	// U+2026; HORIZONTAL ELLIPSIS
						$path.= '/' . rawurlencode($p);
					}
				}
			} elseif ($this->pObj->config['config']['stat_apache_niceTitle']) {
				$path = $this->pObj->csConvObj->specCharsToASCII($this->pObj->renderCharset,$this->pObj->sys_page->getPathFromRootline($temp,$len));
			} else {
				$path = $this->pObj->sys_page->getPathFromRootline($temp,$len);
			}
		} else {
			$path = '';	// If rootLine is missing, we just drop the path...
		}

		if ($this->pObj->config['config']['stat_apache_niceTitle'] == 'utf-8') {
			$this->pObj->config['stat_vars']['pageName'] = str_replace('[path]', $path.'/', $pageName);
		} else {
			$this->pObj->config['stat_vars']['pageName'] = str_replace('[path]', preg_replace('/[^.[:alnum:]\/_-]/','_',$path.'/'), $pageName);
		}
	}

	/**
	 * Get the (partially) anonymized IP address for the log file
	 *  	configure: set set config.stat_IP_anonymize=1
	 *
	 *  @return string the IP to log
	 */
	public function getLogIPAddress(){
		$result = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR');
		if ($this->pObj->config['config']['stat_IP_anonymize']) {
			if (strpos($result, ':')) {
				$result = $this->stripIPv6($result);
			} else {
				$result = $this->stripIPv4($result);
			}
		}
		return $result;
	}

	/**
	 * Strip parts from a IPv6 address
	 *
	 * configure: set config.stat_IP_anonymize_mask_ipv6 to a prefix-length (0 to 128)
	 * 			  defaults to 64  if not set
	 *
	 * @param string raw IPv6 address
	 * @return string stripped address
	 */
	protected function stripIPv6($strIP) {
		if(isset($this->pObj->config['config']['stat_IP_anonymize_mask_ipv6'])) {
			$netPrefix = intval($this->pObj->config['config']['stat_IP_anonymize_mask_ipv6']);
		} else {
			$netPrefix = 64;
		}
		$bytesIP = \TYPO3\CMS\Core\Utility\GeneralUtility::IPv6Hex2Bin($strIP);

		$bitsToStrip = (128 - $netPrefix);

		for($counter = 15; $counter >= 0; $counter--)
		{
			$bitsToStripPart = min($bitsToStrip, 8);
			// TODO find a nicer solution for bindec and chr/ord below - but it works :-)
			$mask = bindec(str_pad('', 8 - $bitsToStripPart, '1') . str_pad('', $bitsToStripPart, '0'));
			$bytesIP[$counter] = chr(ord($bytesIP[$counter]) & $mask);
			$bitsToStrip -= $bitsToStripPart;
		}
		$strIP = inet_ntop($bytesIP);
		return $strIP;
	}

	/**
	 * Strip parts from IPv4 addresses
	 *
	 * configure: set config.stat_IP_anonymize_mask_ipv4 to a prefix-length (0 to 32)
	 * 			  defaults to 24, if not set
	 *
	 * @param string IPv4 address
	 * @return string  stripped IP address
	 */
	protected function stripIPv4($strIP) {
		if(isset($this->pObj->config['config']['stat_IP_anonymize_mask_ipv4'])) {
			$netPrefix = intval($this->pObj->config['config']['stat_IP_anonymize_mask_ipv4']);
		} else {
			$netPrefix = 24;
		}

		$bitsToStrip = (32 - $netPrefix);
		$ip = ip2long($strIP);
		// shift right
		$ip = $ip >> $bitsToStrip;
		// shift left; last bytes will be zero now
		$ip = $ip << $bitsToStrip;
		$strIP = long2ip($ip);
		return $strIP;
	}

	/**
	 * Get the (possibly) anonymized host name for the log file
	 *  	configure: set config.stat_IP_anonymize=1
	 *
	 * @return the host name to log
	 */
	public function getLogHostName() {
		if($this->pObj->config['config']['stat_IP_anonymize']) {
			// ignore hostname if IP anonymized
			$hostName = '<anonymized>';
		} else {
			$hostName = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_HOST');
		}
		return $hostName;
	}

	/**
	 * Get the (possibly) anonymized username or user id for the log file
	 *      configure: set config.stat_IP_anonymize=1
	 *
	 * @return the user name /uid to log
	 */
	public function getLogUserName() {
		$logUser = (isset($this->pObj->config['config']['stat_logUser'])) ? $this->pObj->config['config']['stat_logUser'] : TRUE;
		if ($this->pObj->loginUser && $logUser) {
			$userName =  $this->pObj->fe_user->user['username'];
		} else {
			$userName = '-';
		}
		return $userName;
	}

	/**
	 * Saves hit statistics
	 *
	 * @return	void
	 */
	function statistics() {
		if (!empty($this->pObj->config['config']['stat']) &&
			(!strcmp('',$this->pObj->config['config']['stat_typeNumList']) || \TYPO3\CMS\Core\Utility\GeneralUtility::inList(str_replace(' ','',$this->pObj->config['config']['stat_typeNumList']), $this->pObj->type)) &&
			(empty($this->pObj->config['config']['stat_excludeBEuserHits']) || !$this->pObj->beUserLogin) &&
			(empty($this->pObj->config['config']['stat_excludeIPList']) || !\TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'),str_replace(' ','',$this->pObj->config['config']['stat_excludeIPList'])))) {

			// Apache:
			if (!empty($this->pObj->config['config']['stat_apache']) && !empty($this->pObj->config['stat_vars']['pageName'])) {
				if (@is_file($this->pObj->config['stat_vars']['logFile'])) {
					// Build a log line (format is derived from the NCSA extended/combined log format)
					// Log part 1: Remote hostname / address
					$LogLine = (\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_HOST') && empty($this->pObj->config['config']['stat_apache_noHost'])) ? $this->getLogHostName() : $this->getLogIPAddress();
					// Log part 2: Fake the remote logname
					$LogLine .= ' -';
					// Log part 3: Remote username
					$LogLine .= ' ' . $this->getLogUserName();
					// Log part 4: Time
					$LogLine .= ' ' . date('[d/M/Y:H:i:s +0000]',$GLOBALS['EXEC_TIME']);
					// Log part 5: First line of request (the request filename)
					$LogLine .= ' "GET ' . $this->pObj->config['stat_vars']['pageName'].' HTTP/1.1"';
					// Log part 6: Status and content length (ignores special content like admin panel!)
					$LogLine .= ' 200 ' . strlen($this->pObj->content);

					if (empty($this->pObj->config['config']['stat_apache_notExtended'])) {
						$referer = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_REFERER');
						$LogLine .= ' "' . ($referer ? $referer : '-') . '" "' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT') . '"';
					}

					$GLOBALS['TT']->push('Write to log file (fputs)');
					$logfilehandle = fopen($this->pObj->config['stat_vars']['logFile'], 'a');
					fputs($logfilehandle, $LogLine.LF);
					@fclose($logfilehandle);
					$GLOBALS['TT']->pull();

					$GLOBALS['TT']->setTSlogMessage('Writing to logfile: OK',0);
				} else {
					$GLOBALS['TT']->setTSlogMessage('Writing to logfile: Error - logFile did not exist!',3);
				}
			}
			$GLOBALS['TT']->pull();
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/retrostats/class.tx_retrostats_hook.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/retrostats/class.tx_retrostats_hook.php']);
}

