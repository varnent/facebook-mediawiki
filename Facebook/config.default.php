<?php
### FACEBOOK CONFIGURATION VARIABLES ###

/**
 * To use Facebook you will first need to create a Facebook application:
 *    1.  Visit the "Create an Application" setup wizard:
 *        https://developers.facebook.com/setup/
 *    2.  Enter a descriptive name for your wiki in the Site Name field.
 *        This will be seen by users when they sign up for your site.
 *    3.  Enter the Site URL and Locale, then click "Create application".
 *    4.  Copy the displayed App ID and Secret into this config file.
 *    5.  One more step... Inside the developer app select your new app
 *        and click "Edit App". Now scroll down, click the check next to
 *        "Website," and finally enter your wiki's URL.
 * 
 * Optionally, you may customize your application:
 *    A.  Upload icon and logo images. The icon appears in Timeline events.
 *    B.  Create a Page for your app (if you don't already have one). Visit the
 *        settings, click Advanced, scroll to the bottom and click the buttom in
 *        the "App Page" field. This will create a new page for your app. Paste
 *        the Page ID for your app below.
 * 
 * It is recommended that, rather than changing the settings in this file, you
 * instead override them in LocalSettings.php by adding new settings after
 * require_once("$IP/extensions/Facebook/Facebook.php");
 */
$wgFbAppId          = 'YOUR_APP_ID';    # Change this!
$wgFbSecret         = 'YOUR_SECRET';    # Change this!
//$wgFbPageId       = 'YOUR_PAGE_ID';   # Optional

/**
 * Turns the wiki into a Facebook-only wiki. This setting has three side-effects:
 *    1.  All users are stripped of the 'createaccount' right. To override this
 *        behavior, see FacebookHooks::UserGetRights.
 *    2.  Special:Userlogin and Special:CreateAccount redirect to Special:Connect
 *    3.  The "Log in / create account" links in the personal toolbar are removed.
 * 
 * If you have a Facebook-only wiki, you probably want to hide IP addresses. This
 * can be done by setting $wgShowIPinHeader to false in LocalSettings.php. For more
 * information, see <http://www.mediawiki.org/wiki/Manual:$wgShowIPinHeader>.
 */
$wgFbDisableLogin = false;

/**
 * For easier wiki rights management, create a group on Facebook and place the
 * group ID here. The "user_groups" right will automatically be requested from
 * users.
 * 
 * Two new implicit groups will be created:
 * 
 *     fb-groupie    A member of the specified group
 *     fb-admin      An administrator of the Facebook group
 * 
 * By default, they map to User and Sysop privileges. Users will automatically
 * be promoted or demoted when their membership or admin status is modified
 * from the group page within Facebook. Unfortunately, this has a minor
 * degredation on performance.
 * 
 * This setting can also be used in conjunction with $wgFbDisableLogin. To have
 * this group exclusively control access to the wiki, set $wgFbDisableLogin
 * to true and add the following settings to Localsettings.php:
 * 
 * # Disable reading and editing by anonymous users
 * $wgGroupPermissions['*']['edit'] = false;
 * $wgGroupPermissions['*']['read'] = false;
 * 
 * # Reserve normal wiki browsing for only Facebook group members (and admins)
 * $wgGroupPermissions['sysop'] = array_merge($wgGroupPermissions['sysop'], $wgGroupPermissions['user']);
 * $wgGroupPermissions['user'] = $wgGroupPermissions['fb-user'] = $wgGroupPermissions['*'];
 * 
 * # But allow all users to read these pages:
 * $wgWhitelistRead = array('-', 'Special:Connect', 'Special:UserLogin', 'Special:UserLogout');
 */
$wgFbUserRightsFromGroup = false;  # Or a group ID

/**
 * Allow the use of social plugins in wiki text. To learn more about social
 * plugins, please see <https://developers.facebook.com/docs/plugins>.
 * 
 * Open Graph Beta social plugins can also be used.
 * <https://developers.facebook.com/docs/beta/plugins>
 */
$wgFbSocialPlugins = true;

/**
 * Shows the real name for all Facebook users instead of their wiki user name.
 */
$wgFbUseRealName = false;

/**
 * Options regarding the personal toolbar (in the upper right).
 */
$wgFbShowPersonalUrls = array(
	'connect',        // Show the "Log in with Facebook Connect" button
#	'convert',        // Give logged-in users a link to Connect their accounts (not available)
#	'logout',         // Log the user out of Facebook when they log out of the wiki
);

/**
 * The Personal Urls above can be blacklisted for specific skins. An asterisk
 * acts to match all Facebook-related buttons.
 */
$wgFbHidePersonalUrlsBySkin = array(
	'SkinMonaco' => array( 'connect', 'convert', 'logout' ),
	'SkinAnswers' => array( 'convert', 'logout' ),
	'SkinCorporate' => '*',
	'SkinOasis' => array( 'connect', 'convert', 'logout' ),
);

/**
 * The Facebook icon. You can copy this image to your server if you want, or
 * set to false to disable.
 */
$wgFbLogo = 'http://static.ak.fbcdn.net/images/icons/favicon.gif';

/**
 * URL of the Facebook JavaScript SDK. If the URL includes the token "%LOCALE%"
 * then it will be replaced with the correct Facebook locale based on the user's
 * configured language. To disable localization, use e.g.
 * 
 * https://connect.facebook.net/en_US/all.js
 * 
 * You may wish to insulate your production wiki from changes by downloading and
 * hosting your own copy of the JavaScript SDK. If you still wish to support
 * multiple languages, you will also need to host localized versions of the SDK.
 * For a list of locales supported by Facebook, see FacebookLanguage.php.
 */
$wgFbScript = 'https://connect.facebook.net/%LOCALE%/all.js';

/**
 * Path to the extension's client-side JavaScript.
 *     facebook.js        For development
 *     facebook.min.js    Minified version for deployment
 */
global $wgScriptPath;
$wgFbExtensionScript = "$wgScriptPath/extensions/Facebook/facebook.js";

/**
 * Optionally override the default javascript handling which occurs when a user
 * logs in. This will generally not be needed unless you are doing heavy
 * customization of this extension.
 *
 * NOTE: This will be put inside of double-quotes, so any single-quotes should
 * be used inside of any JavaScript in this variable.
 */
#$wgFbOnLoginJsOverride = 'sendToConnectOnLogin();';
