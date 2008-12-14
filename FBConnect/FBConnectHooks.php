<?php

/**
 *  Class containing all the hooks used in this extension
 */
class FBConnectHooks {
	/**
	 * Set the global variable $wgAuth to our custom authentification plugin
	 */
	static function onAuthPluginSetup (&$auth) {
		$auth = new StubObject('wgAuth', 'FBConnectAuthPlugin');
		return true;
	}
	
	/**
	 * If the user isn't logged in, try to auto-authenticate via Facebook Connect
	 */
	static function onUserLoadFromSession($user, &$result) {
		global $wgAuth;
		
		$fb_uid = FBConnectClient::getClient()->get_loggedin_user();	
		if (!isset($fb_uid) || $fb_uid == 0) {
			// No connection with facebook, so use local sessions only
			return true;
		}
		if( $user->isLoggedIn() ) {
			// Already logged in; don't worry about the global session
			// TODO: check against the user's facebook ID, and log them out if they don't match
			return true;
		}
		
		// Username is the user's facebook ID
		$userName = "$fb_uid";
		
		// Only create a new user if we can see the user's real name from facebook
		$real_name = FBConnectClient::get_fields($fb_uid, array('name'));
		
		
		$localId = User::idFromName( $userName );
		
		// If the user does not exist locally, attempt to create it
		if ( !$localId ) {
			/* 
			// Denied by configuration?
			if ( !$wgAuth->autoCreate() ) {
				wfDebug( __METHOD__.": denied by configuration\n" );
				// Can't create new user, give up now
				return true;
			}
			/**/	
			
			/* Skip this check for now, until we hammer down the other problems
			$anon = new User;
			// Is the user blocked?
			if ( !$anon->isAllowedToCreateAccount() ) {
				wfDebug( __METHOD__.": denied by configuration. \$user->isAllowedToCreateAccount() returned false.\n" );
				// Can't create new user, give up now
				return true;
			}
			/**/

			// Checks passed, create the user
			//wfDebug( __METHOD__.": creating new user\n" );
			$user->loadDefaults( $userName );
			$user->addToDatabase();

			$wgAuth->initUser( $user, true );
			// $wgAuth->updateUser() is called by $wgAuth->initUser(). Should it be called here instead?
			// $wgAuth->updateUser( $user );

			// Update user count
			$ssUpdate = new SiteStatsUpdate( 0, 0, 0, 0, 1 );
			$ssUpdate->doUpdate();

			// Notify hooks (e.g. Newuserlog)
			wfRunHooks( 'AuthPluginAutoCreate', array( $user ) );
			//$user->addNewUserLogEntryAutoCreate();	// Which MediaWiki versions can we call this function in?
		} else {
			$user->setID( $localId );
			$user->loadFromId();
			if ($user->getRealName() == '') {
				$wgAuth->updateUser( $user );
			}
		}
		
		// Auth OK.
		wfDebug( __METHOD__.": logged in from session\n" );
		wfSetupSession();
		$result = true;
		
		return true;
	}

	/**
	 * Modify the preferences form. At the moment, we simply turn the user name
	 * into a link to the user's facebook profile.
	 * 
	 */
	public static function onRenderPreferencesForm($form, $output) {
		global $wgUser;
		$fb_uid = $wgUser->getName();
		// If the user name is not a valid facebook ID (i.e. not a bunch of numbers) then we're done here
		// TODO: I need a function that actually tests this
		if ($fb_uid == "Admin") {
			return true;
		}
		$html = $output->getHTML();
		$i = strpos( $html, $fb_uid );
		if ($i !== FALSE) {
			// Replace the old output with the new output
			$output->clearHTML();
			$output->addHTML( substr($html, 0, $i) . preg_replace("($fb_uid)",
			    "<a href='http://www.facebook.com/profile.php?id=$fb_uid'>$fb_uid</a>", substr($html, $i, -1), 1 ) );
		}
		return true;
	}

	/**
	 * Modify the user's persinal toolbar (in the upper right)
	 */
	static function onPersonalUrls(&$personal_urls, &$title) {
		global $wgUser, $wgLang, $wgOut, $wgFBConnectOnly;
		wfLoadExtensionMessages('FBConnect');
		$sk = $wgUser->getSkin();
		
		if ( !$wgUser->isLoggedIn() ) {
			$returnto = ($title->getPrefixedUrl() == $wgLang->specialPage( 'Userlogout' )) ?
			  '' : ('returnto=' . $title->getPrefixedURL());

			$personal_urls['fbconnect'] = array('text' => wfMsg('fbconnectlogin'),
			                                    #'href' => $sk->makeSpecialUrl( 'Userlogin', $returnto ),
			                                    'href' => $sk->makeSpecialUrl( 'Connect', $returnto ),
			                                    'active' => $title->isSpecial( 'Userlogin' ) );
			if ($wgFBConnectOnly) {
				# remove other personal toolbar links
				foreach (array('login', 'anonlogin') as $k) {
					if (array_key_exists($k, $personal_urls)) {
						unset($personal_urls[$k]);
					}
				}
			}
		} else {
			/* User's real name is not set at account creation. Why not? And why doesn't this workaround seem to work?
			if ($wgUser->getRealName() == "") {
				$wgAuth->updateUser($wgUser);
			}
			/**/
			if ($wgUser->getRealName() == "") {
				$personal_urls['userpage']['text'] .= ' (change "Real Name" in preferences) ';
			} else {
				$personal_urls['userpage']['text'] = $wgUser->getRealName();
			}
			unset($personal_urls['logout']);
			/**/
			$thisurl = $title->getPrefixedURL();
			$personal_urls['fblogout'] = array('text' => wfMsg('fbconnectlogout'),
			                                   'href' => Skin::makeSpecialUrl('Userlogout', $title->isSpecial('Preferences') ?
			                                             '' : "returnto={$thisurl}"),
			                                   'active' => false);
			$personal_urls['fblink'] = array('text' => wfMsg('fbconnectlink'),
			                                 'href' => 'http://www.facebook.com/profile.php?id=' . $wgUser->getName(),
			                                 'active' => false);
		}
		
		// Unset user talk page links
		if (array_key_exists('mytalk', $personal_urls))
			unset($personal_urls['mytalk']);

		return true;
	}
	
	/**
	 * We seriously need to use a better hook... But which one allows injecting javascript src's into the page's body?
	 * The dynamic source code loading [newElement("source") ...] technique didn't work for me.
	 *
	 */
	static function onParserAfterTidy(&$parser, &$text) {
		static $wgOnce = false;
		//if (!isset($wgOnce) || !$wgOnce) {
		if (!$wgOnce) {
			$wgOnce = true;
			self::onSomeHookThatAllowsOneTimeRenderingToFooter($text);
		}
		return true;
	}
	
	/**
	 * Is there any hook for this task?
	 *
	 * Perhaps one of the skin hooks: SkinAfterBottomScripts, SkinAfterContent or SkinBuildSidebar...
	 *
	 */
	static function onSomeHookThatAllowsOneTimeRenderingToFooter(&$text) {
		$text .= '<script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php"></script>';
		//$text .= '<script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/XdCommReceiver.js" type="text/javascript"></script>';
		//$text .= '<script src="/w/extensions/FBConnect/fbconnect.js"></script>';
		return true;
	}

	/**
	 * Injects some CSS and Javascript into the <head> of the page
	 */
	static function onBeforePageDisplay(&$out, &$sk) {
		global $wgTitle, $wgFBConnectLogoUrl, $wgScriptPath;
		$thisurl = $wgTitle->getPrefixedURL();
		
		// Add a pretty Facebook logo in front of the userpage's link
		$style = '<style type="text/css">
			li#pt-userpage {
				background: url(' . $wgFBConnectLogoUrl . ') top left no-repeat;
			}
		</style>';
		
		// Setup some variables and pseudo window.onload functions for Facebook Connect
		$script = "";
		$js_vars = array(
			'api_key' => FBConnectClient::get_api_key(),
			'already_logged_into_facebook' => FBConnectClient::getClient()->get_loggedin_user() ? "true" : "false",
			'logout_url' => Skin::makeSpecialUrl('Userlogout', $wgTitle->isSpecial('Preferences') ? '' : "returnto={$thisurl}")
		);
		foreach( $js_vars as $name => $value ) {
			if( $value == "true" || $value == "false" ) {
				$script .= "var " . $name . " = " . $value . ";\n";
			} else {
				$script .= "var " . $name . " = '" . $value . "';\n";
			}
		}
		// Onload functions from fbconnect.js (actually called in the <body> by addOnloadHook())
		foreach( array( 'facebook_onload_addFBConnectButtons', 'facebook_init', 'facebook_onload' ) as $hook ) {
			$script .= "addOnloadHook($hook);\n";
		}
		
		// Styles and Scripts have been built, so add them to the page
		if (isset($wgFBConnectLogoUrl) && $wgFBConnectLogoUrl) {
			$out->addScript($style . "\n\t\t");
		}
		$out->addScript("<script src='$wgScriptPath/extensions/FBConnect/fbconnect.js'></script>");
		$out->addInlineScript($script);
		return true;
	}
}
