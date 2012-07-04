<?php 

/**
 * @file
 * @ingroup Extensions
 * @license cc-by-sa http://creativecommons.org/licenses/by-sa/3.0/  
 * @since 0.1, MediaWiki 1.13.0+
 * @note coding convention followed: http://www.mediawiki.org/wiki/Manual:Coding_conventions
 */ 

if ( !defined( 'MEDIAWIKI' ) ) {
        die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

$wgExtensionCredits['other'][] = array(
  'name' => 'FormatUsernames',
  'author' =>'Olivier Beaton', 
  'version' => '0.1.0',
  'url' => 'http://www.mediawiki.org/wiki/Extension:FormatUsernames', 
  'description' => 'Change how usernames are displayed on special pages'
 );
 
$wgFormatUsernamesOptions = array(
  'name-style' => 'replace',
  'name-forceblank' => false,
  /*
    $1  link start
    $2  username
    $3  real name
    $4  link end
  */  
  'name-styles' => array(
    'standard' => '$1$2$4',
    'append' => '$1$2$4 [$3]',
    'replace' => '$1$3$4',
    'reverse' => '$1$3$4 [$2]',
    'dash' => '$1$2$4 - $3',
    'bare-append' => '$2 [$3]',
  ),
  
  'SpecialListUsers' => array(
    'enabled' => true,
    'name-style' => false,
  ),
  'PageHistory' => array(
    'enabled' => true,
    'name-style' => false,
  ),
  'ChangesList' => array(
    'enabled' => true,
    'name-style' => false,
  ),
  'SpecialSearch' => array(
    'enabled' => true,
    'name-style' => 'bare-append',
  ),
); 
 
$wgExtensionFunctions[] = 'wfSetupFormatUsernames';
 
$wgAutoloadClasses['ExtFormatUsernamesSpecialListUsers'] = dirname(__FILE__) . '/FormatUsernames.SpecialListUsers.php';
$wgAutoloadClasses['ExtFormatUsernamesPageHistory'] = dirname(__FILE__) . '/FormatUsernames.PageHistory.php';
$wgAutoloadClasses['ExtFormatUsernamesChangesList'] = dirname(__FILE__) . '/FormatUsernames.ChangesList.php';
$wgAutoloadClasses['ExtFormatUsernamesSpecialSearch'] = dirname(__FILE__) . '/FormatUsernames.SpecialSearch.php';
$wgExtensionMessagesFiles['FormatUsernames'] = dirname( __FILE__ ) . '/FormatUsernames.i18n.php';

$wgFormatUsernamesHooks = array();

/**
 * @since 0.1, MediaWiki 1.13.0+
 */ 
function wfSetupFormatUsernames() {
  global $wgHooks, $wgFormatUsernamesHooks;
  
  wfLoadExtensionMessages('FormatUsernames');
  
  // Special:ListUsers
  $wgFormatUsernamesHooks['SpecialListUsers'] = new ExtFormatUsernamesSpecialListUsers();
  $wgHooks['SpecialListusersQueryInfo'][] = array(&$wgFormatUsernamesHooks['SpecialListUsers'], 'query');
  $wgHooks['SpecialListusersFormatRow'][] = array(&$wgFormatUsernamesHooks['SpecialListUsers'], 'row');
  
  // PageHistory
  $wgFormatUsernamesHooks['PageHistory'] = new ExtFormatUsernamesPageHistory();
  $wgHooks['PageHistoryPager::getQueryInfo'][] = array(&$wgFormatUsernamesHooks['PageHistory'], 'query');
  $wgHooks['PageHistoryLineEnding'][] = array(&$wgFormatUsernamesHooks['PageHistory'], 'row');
  
  // ChangesList => Special:Watchlist, Special:Recentchanges
  $wgFormatUsernamesHooks['ChangesList'] = new ExtFormatUsernamesChangesList();
  $wgHooks['SpecialRecentChangesQuery'][] = array(&$wgFormatUsernamesHooks['ChangesList'], 'query');
  $wgHooks['FetchChangesList'][] = array(&$wgFormatUsernamesHooks['ChangesList'], 'newClass');
  $wgHooks['OldChangesListRecentChangesLine'][] = array(&$wgFormatUsernamesHooks['ChangesList'], 'oldRow'); 
  
  // Special:Search
  $wgFormatUsernamesHooks['SpecialSearch'] = new ExtFormatUsernamesSpecialSearch();
  $wgHooks['ShowSearchHitTitle'][] = array(&$wgFormatUsernamesHooks['SpecialSearch'], 'showHit'); 
} // function

/**
 * @param[in] $p parameters array(linkstart =>, username =>, realname =>, linkend =>)
 * @param[in] $page FormatUsernames module 
 * @since 0.1
 */ 
function wfFormatUsernamesDisplay($p,$page) {
  global $wgFormatUsernamesOptions;
  
  $style = false;
  
  if ($wgFormatUsernamesOptions[$page]) {
    $style = $wgFormatUsernamesOptions[$page]['name-style'];
  }
  
  if ($style === false) {
    $style = $wgFormatUsernamesOptions['name-style'];
  }
  
  $style = $wgFormatUsernamesOptions['name-styles'][$style];
  
  $text = wfMsgReplaceArgs($style, array($p['linkstart'],$p['username'],$p['realname'],$p['linkend'])); 
  
  return $text;  
} // function 
