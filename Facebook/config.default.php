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
$wgFbExtensionScript = "$wgScriptPath/extensions/Facebook/facebook.min.js";
