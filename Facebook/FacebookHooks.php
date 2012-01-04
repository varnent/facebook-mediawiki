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

/**
 * Class FacebookHooks
 * 
 * This class contains all the hooks used in this extension. HOOKS DO NOT NEED
 * TO BE EXPLICITLY ADDED TO $wgHooks. Simply write a public static function
 * with the same name as the hook that provokes it, place it inside this class
 * and let FacebookInit::init() do its magic. Helper functions should be private,
 * because only public static methods are added as hooks.
 */
class FacebookHooks {
	
	/**
	 * Injects some important CSS and Javascript into the <head> of the page.
	 */
	public static function BeforePageDisplay( &$out, &$sk ) {
		global $wgUser, $wgVersion, $wgFbLogo, $wgFbScript, $wgFbExtensionScript, $wgJsMimeType, $wgStyleVersion;
		
		// Check to see if we should localize the JS SDK
		if (strpos( $wgFbScript, FACEBOOK_LOCALE ) !== false) {
			wfProfileIn( __METHOD__ . '::fb-locale-by-mediawiki-lang' );
			// NOTE: Can't use $wgLanguageCode here because the same Facebook config can
			// run for many $wgLanguageCode's on one site (such as Wikia).
			global $wgLang;
			// Attempt to find a matching Facebook locale
			$locale = FacebookLanguage::getFbLocaleForLangCode( $wgLang->getCode() );
			$wgFbScript = str_replace( FACEBOOK_LOCALE, $locale, $wgFbScript );
			wfProfileOut( __METHOD__ . '::fb-locale-by-mediawiki-lang' );
		}
		
		// Asynchronously load the Facebook JavaScript SDK before the page's content
		// See <https://developers.facebook.com/docs/reference/javascript>
		global $wgNoExternals;
		if ( !empty($wgFbScript) && empty($wgNoExternals) ) {
			$out->prependHTML('
				<div id="fb-root"></div>
<script type="' . $wgJsMimeType . '">
(function(d){var js,id="facebook-jssdk";if(!d.getElementById(id)){js=d.createElement("script");js.id=id;js.async=true;js.type="' .
	$wgJsMimeType . '";js.src="' . $wgFbScript . '";d.getElementsByTagName("head")[0].appendChild(js);}}(document));
</script>' . "\n"
			);
		}
		
		// Inserts list of global JavaScript variables if necessary
		if (self::MGVS_hack( $mgvs_script )) {
			$out->addInlineScript( $mgvs_script );
		}
		
		// Add a Facebook logo to the class .mw-fblink
		$style = empty($wgFbLogo) ? '' : <<<STYLE
.mw-facebook-logo {
	background-image: url($wgFbLogo) !important;
	background-repeat: no-repeat !important;
	background-position: left top !important;
	padding-left: 19px !important;
}
STYLE;
		$style .= '.fbInitialHidden {display:none;}';
		
		// Things get a little simpler in 1.16...
		if ( version_compare( $wgVersion, '1.16', '>=' ) ) {
			/*
			// Add a pretty Facebook logo if $wgFbLogo is set
			if ( !empty( $wgFbLogo) ) {
				$out->addInlineStyle( $style );
			}
			*/
			$out->addInlineStyle( $style );
			// Include the common jQuery library (alias defaults to $j instead of $)
			$out->includeJQuery();
			// Add the script file specified by $url
			if ( !empty( $wgFbExtensionScript ) ) {
				$out->addScriptFile( $wgFbExtensionScript );
			}
		} else {
			/*
			// Add a pretty Facebook logo if $wgFbLogo is set
			if ( !empty( $wgFbLogo) ) {
				$out->addScript( '<style type="text/css">' . $style . '</style>' );
			}
			*/
			$out->addScript( '<style type="text/css">' . $style . '</style>' );
			// Include the most recent 1.7 version
			$out->addScriptFile( 'http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js' );
			// Add the script file specified by $url
			if( !empty( $wgFbExtensionScript ) ) {
				$out->addScript("<script type=\"$wgJsMimeType\" src=\"$wgFbExtensionScript?$wgStyleVersion\"></script>\n");
			}
		}
		return true;
	}
	
	/**
	 * Fired when MediaWiki is updated (from the command line updater utility or,
	 * if using version 1.17+, from the initial installer). This hook allows
	 * Facebook to update the database with the required tables. Each table
	 * listed below should have a corresponding schema file in the sql directory
	 * for each supported database type.
	 * 
	 * MYSQL ONLY: If $wgDBprefix is set, then the table 'user_fbconnect' will
	 * be prefixed accordingly. Make sure that the .sql files are modified with
	 * the database prefix beforehand.
	 * 
	 * The $updater parameter added in r71140 (after 1.16)
	 * <http://svn.wikimedia.org/viewvc/mediawiki?view=revision&revision=71140>
	 */
	static function LoadExtensionSchemaUpdates( $updater = null ) {
		global $wgSharedDB, $wgDBname, $wgDBtype, $wgDBprefix;
		// Don't create tables on a shared database
		if( !empty( $wgSharedDB ) && $wgSharedDB !== $wgDBname ) {
			return true;
		}
		// Tables to add to the database
		$tables = array( 'user_fbconnect', 'fbconnect_event_stats', 'fbconnect_event_show' );
		// Sql directory inside the extension folder
		$sql = dirname( __FILE__ ) . '/sql';
		// Extension of the table schema file (depending on the database type)
		switch ( $updater !== null ? $updater->getDB()->getType() : $wgDBtype ) {
			case 'mysql':
				$ext = 'sql';
				break;
			case 'postgres':
				$ext = 'pg.sql';
				break;
			default:
				$ext = 'sql';
		}
		// Do the updating
		foreach ( $tables as $table ) {
			if ( $wgDBprefix ) {
				$table = $wgDBprefix . $table;
			}
			// Location of the table schema file
			$schema = "$sql/$table.$ext";
			// If we're using the new version of the LoadExtensionSchemaUpdates hook
			if ( $updater !== null ) {
				$updater->addExtensionUpdate( array( 'addTable', $table, $schema, true ) );
			} else {
				global $wgExtNewTables;
				$wgExtNewTables[] = array( $table, $schema );
			}
		}
		return true;
	}
	
	/**
	 * Adds several Facebook Connect variables to the page:
	 * 
	 * fbAppId		 The application ID (see $wgFbAppId in config.php)
	 * fbUseXFBML    Should XFBML tags be rendered (see $wgFbSocialPlugins in config.default.php)
	 * fbLogo        Facebook logo (see $wgFbLogo in config.php)
	 * 
	 * This hook was added in MediaWiki version 1.14. See:
	 * http://svn.wikimedia.org/viewvc/mediawiki/trunk/phase3/includes/Skin.php?view=log&pathrev=38397
	 * If we are not at revision 38397 or later, this function is called from BeforePageDisplay
	 * to retain backward compatability.
	 */
	public static function MakeGlobalVariablesScript( &$vars ) {
		global $wgFbAppId, $facebook, $wgFbSocialPlugins, $wgTitle, $wgRequest, $wgStyleVersion, $wgUser;
		if (!isset($vars['wgPageQuery'])) {
			$query = $wgRequest->getValues();
			if (isset($query['title'])) {
				unset($query['title']);
			}
			$vars['wgPageQuery'] = wfUrlencode( wfArrayToCGI( $query ) );
		}
		if (!isset($vars['wgStyleVersion'])) {
			$vars['wgStyleVersion'] = $wgStyleVersion;
		}
		$vars['fbAppId']     = $wgFbAppId;
		$vars['fbUseXFBML']  = $wgFbSocialPlugins;
		
		// Let JavaScript know if the Facebook ID belongs to someone else
		if ($wgUser->isLoggedIn() /*&& !$facebook->getUser()*/) { // TODO: uncomment
			$ids = FacebookDB::getFacebookIDs($wgUser);
			if ( count($ids) > 0 ) {
				// Turn numbers into strings
				foreach ( $ids as $index => $id ) {
					$ids[$index] = strval( $id );
				}
				$vars['fbIds'] = $ids; // possibly more than 1 Facebook ID for this user
			}
		}
		return true;
	}
	
	/**
	 * Hack: Run MakeGlobalVariablesScript for backwards compatability.
	 * The MakeGlobalVariablesScript hook was added to MediaWiki 1.14 in revision 38397:
	 * http://svn.wikimedia.org/viewvc/mediawiki/trunk/phase3/includes/Skin.php?view=log&pathrev=38397
	 */
	private static function MGVS_hack( &$script ) {
		global $wgVersion, $IP;
		if (version_compare($wgVersion, '1.14.0', '<')) {
			$script = "";
			$vars = array();
			wfRunHooks('MakeGlobalVariablesScript', array(&$vars));
			foreach( $vars as $name => $value ) {
				$script .= "\t\tvar $name = " . json_encode($value) . ";\n";
    		}
    		return true;
		}
		return false;
	}
	
	/**
	 * Installs a parser hook for every tag reported by FacebookXFBML::availableTags().
	 * Accomplishes this by asking FacebookXFBML to create a hook function that then
	 * redirects to FacebookXFBML::parserHook().
	 */
	public static function ParserFirstCallInit( &$parser ) {
		$pHooks = FacebookXFBML::availableTags();
		foreach( $pHooks as $tag ) {
			$parser->setHook( $tag, FacebookXFBML::createParserHook( $tag ));
		}
		return true;
	}
	
	/**
	 * Modify the user's persinal toolbar (in the upper right).
	 */
	public static function PersonalUrls( &$personal_urls, &$wgTitle ) {
		global $wgUser;
		
		wfLoadExtensionMessages('Facebook');
		
		// Add an option to connect via Facebook Connect
		if ( !$wgUser->isLoggedIn() ) {
			$personal_urls['facebook'] = array(
				'text'   => wfMsg( 'facebook-connect' ),
				'href'   => '#', # SpecialPage::getTitleFor('Connect')->getLocalUrl('returnto=' . $wgTitle->getPrefixedURL()),
				'class' => 'mw-facebook-logo',
				'active' => $wgTitle->isSpecial('Connect'),
			);
		}
		return true;
	}
	
	/**
	 * Adds the class "mw-userlink" to links belonging to Connect accounts on
	 * the page Special:ListUsers.
	 */
	static function SpecialListusersFormatRow( &$item, $row ) {
		global $fbSpecialUsers;
		
		// Only modify Facebook Connect users
		if (empty( $fbSpecialUsers ) ||
				!count(FacebookDB::getFacebookIDs(User::newFromName($row->user_name)))) {
			return true;
		}
		
		// Look to see if class="..." appears in the link
		$regs = array();
		preg_match( '/^([^>]*?)class=(["\'])([^"]*)\2(.*)/', $item, $regs );
		if (count( $regs )) {
			// If so, append " mw-userlink" to the end of the class list
			$item = $regs[1] . "class=$regs[2]$regs[3] mw-userlink$regs[2]" . $regs[4];
		} else {
			// Otherwise, stick class="mw-userlink" into the link just before the '>'
			preg_match( '/^([^>]*)(.*)/', $item, $regs );
			$item = $regs[1] . ' class="mw-userlink"' . $regs[2];
		}
		return true;
	}
	
	/**
	 * HACK: Please someone fix me or explain why this is necessary!
	 * 
	 * Unstub $wgUser to avoid race conditions and stop returning stupid false
	 * negatives!
	 * 
	 * This might be due to a bug in User::getRights() [called from
	 * User::isAllowed('read'), called from Title::userCanRead()], where mRights
	 * is retrieved from an uninitialized user. From my probing, it seems that
	 * the user is uninitialized with almost all members blank except for mFrom,
	 * equal to 'session'. The second time around, $user seems to point to the
	 * User object after being loaded from the session. After the user is loaded
	 * it has all the appropriate groups. However, before being loaded it seems
	 * that instead of being null, mRights is equal to the array
	 * (createaccount, createpage, createtalk, writeapi).
	 */
	static function userCan (&$title, &$user, $action, &$result) {
		// Unstub $wgUser (is there a more succinct way to do this?)
		$user->getId();
		return true;
	}
	
	/**
	 * We need to override the password checking so that Facebook users can
	 * reset their passwords and give themselves a valid password to log in
	 * without Facebook. This only works if the user specifies a blank password
	 * and hasn't already given themselves one.
	 *
	 * To that effect, you may want to modify the 'resetpass-wrong-oldpass' msg.
	 *
	 * Before version 1.14, MediaWiki used Special:Preferences to reset
	 * passwords instead of Special:ChangePassword, so this hook won't get
	 * called and Facebook users won't be able to give themselves a password
	 * unless they request one over email.
	 */
	public static function UserComparePasswords( $hash, $password, $userId, &$result ) {
		global $wgTitle;
		// Only allow the override if no password exists and a blank old password was specified
		if ( $hash != '' || $password != '' || !$userId ) {
			return true;
		}
		// Only check for password on Special:ChangePassword
		if ( !$wgTitle->isSpecial( 'Resetpass' ) ) {
			return true;
		}
		// Check to see if the MediaWiki user has connected via Facebook before
		// For a more strict check, we could check if the user is currently logged in to Facebook
		$user = User::newFromId( $userId );
		$fb_ids = FacebookDB::getFacebookIDs($user);
		if (count($fb_ids) == 0 || !$fb_ids[0]) {
			return true;
		}
		$result = true;
		return false; // to override internal check
	}
}
