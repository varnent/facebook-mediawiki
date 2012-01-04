<?php
/*
 * Copyright � 2008-2012 Garrett Brown <http://www.mediawiki.org/wiki/User:Gbruin>
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */


/*
 * Not a valid entry point, skip unless MEDIAWIKI is defined.
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}


/**
 * Class FacebookAPI
 * 
 * This class contains the code used to interface with Facebook via the
 * Facebook Platform API.
 */
class FacebookAPI extends Facebook {
	// Constructor
	public function __construct() {
		global $wgFbAppId, $wgFbSecret;
		// Check to make sure config.default.php was renamed properly, unless we
		// are running update.php from the command line
		// TODO: use $wgCommandLineMode, if it is propper to do so
		if ( !defined( 'MW_CMDLINE_CALLBACK' ) && !$this->isConfigSetup() ) {
			die ( '<strong>Please update $wgFbAppId and $wgFbSecret.</strong>' );
		}
		$config = array(
			'appId'      => $wgFbAppId,
			'secret'     => $wgFbSecret,
			'fileUpload' => false, // optional
		);
		parent::__construct( $config );
	}
	
	/**
	 * Check to make sure config.sample.php was properly renamed to config.php
	 * and the instructions to fill out the first two important variables were
	 * followed correctly.
	 */
	public function isConfigSetup() {
		global $wgFbAppId, $wgFbSecret;
		$isSetup = isset( $wgFbAppId ) && $wgFbAppId != 'YOUR_APP_KEY' &&
		           isset( $wgFbSecret ) && $wgFbSecret != 'YOUR_SECRET';
		if( !$isSetup ) {
			// Check to see if they are still using the old variables
			global $fbApiKey, $fbApiSecret;
			if ( isset( $fbApiKey ) ) {
				$wgFbAppId = $fbApiKey;
			}
			if ( isset( $fbApiSecret ) ) {
				$wgFbSecret= $fbApiSecret;
			}
			$isSetup = isset( $wgFbAppId ) && $wgFbAppId != 'YOUR_APP_KEY' &&
		               isset( $wgFbSecret ) && $wgFbSecret != 'YOUR_SECRET';
		}
		return $isSetup;
	}
}
