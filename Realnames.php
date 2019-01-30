<?php 

/**
 * Extension to display a user's real name wherever and whenever possible.
 * @file
 * @ingroup Extensions
 * @version 0.1.0
 * @authors Olivier Finlay Beaton (olivierbeaton.com)  
 * @copyright cc-by http://creativecommons.org/licenses/by/3.0/  
 * @since 2011-09-15, 0.1
 * @note this extension is pay-what-you-want, please consider a purchase at http://olivierbeaton.com/ 
 * @note coding convention followed: http://www.mediawiki.org/wiki/Manual:Coding_conventions
 */ 

if ( !defined( 'MEDIAWIKI' ) ) {
        die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

/* (not our var to doc)
 * extension credits
 * @since 2011-09-16, 0.1
 */
$wgExtensionCredits['parserhook'][] = array(
  'name' => 'Realnames',
  'author' =>array('[http://olivierbeaton.com/ Olivier Finlay Beaton]'), 
  'version' => '0.1.0',
  'url' => 'http://www.mediawiki.org/wiki/Extension:Realnames', 
  'description' => 'Displays a user\'s real name everywhere',
 );

/**
 * The format to apply to a user link.
 * @since 2011-09-15, 0.1
 * @see $wgRealnamesFormats 
 */ 
$wgRealnamesLinkStyle = 'replace';

/**
 * The format to apply to a user's name in text. 
 * This typically only replaces User: text in titles
 * @since 2011-09-16, 0.1
 */  
$wgRealnamesBareStyle = false;

/**
 * Do you want to show blank real names?
 * If this is false, then it will fall back on a 'replace' username style.
 * If true, then in a style like 'append' ( Joe [Joe Cardigan] )you will see: Joe []  
 * @note User:Joe text will still become Joe.
 * @since 2011-09-15, 0.1
 */    
$wgRealnamesBlank = false;

/**
 * Possible styles to pick from, you can define new ones as well.
 * The following variables are set:<br> 
 * \li $1  link start
 * \li $2  username
 * \li $3  real name
 * \li $4  link end
 * @note If you want to add markup, you should set $wgRealnamesBareStyle to a style without html (it's doesnt work in bare)
 * @since 2011-09-15, 0.1  
 */
$wgRealnamesStyles = array( 
    'standard' => '$1$2$4',
    'append' => '$1$2$4 [$3]',
    'replace' => '$1$3$4',
    'reverse' => '$1$3$4 [$2]',
    'dash' => '$1$2$4 &ndash; $3',
  ); 
  
/**
 * Specify the <em>rc-debug=true</em> key/value pair in your GET parameters 
 *  to see the debug messages from this extension about the replacements it's doing.
 *  @since 2011-09-15, 0.1 
 */ 
$wgRealnamesDebug = $_GET['rn-debug']; // $wgRequest doesn't exist yet
 
/* (not our var to doc)
 * Our extension class, it will load the first time the core tries to access it
 * @since 2011-09-16, 0.1  
 */ 
$wgAutoloadClasses['ExtRealnames'] = dirname(__FILE__) . '/Realnames.body.php';

/* (not our var to doc)
 * This hook is called before the article is displayed.  
 * @since 2011-09-16, 0.1  
 * @see $wgAutoloadClasses for how the class gets defined.  
 */
$wgHooks['BeforePageDisplay'][] = 'ExtRealnames::hookBeforePageDisplay';
