<?php 

/**
 * @file
 * @ingroup Extensions
 * @license cc-by-sa http://creativecommons.org/licenses/by-sa/3.0/  
 * @since 0.1, MediaWiki 1.16.0
 * @note non-gpl blind port/modification of SearchRealnames by John Erling Blad http://www.mediawiki.org/wiki/Extension:SearchRealnames
 */

if ( !defined( 'MEDIAWIKI' ) ) {
        die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

/**
 * @ingroup Extensions
 * @since 0.1, MediaWiki 1.13.0
 */ 
class ExtFormatUsernamesSpecialSearch {
  
  /**
   * add the user's real name to User: search results   
   * @param[inout] &$title Title to link to
   * @param[inout] &$text Text to use for the link
   * @param[in] $result The search result
   * @param[in] $terms The search terms entered
   * @param[in] $page The SpecialSearch object.
   * @return true, continue hook processing
   * @since 0.1, MediaWiki 1.16.0     
   * @see hook documentation http://www.mediawiki.org/wiki/Manual:Hooks/ShowSearchHitTitle
   */     
  function showHit(&$title, &$text, $result, $terms, $page) {
    global $wgFormatUsernamesOptions;
    
    if (!$wgFormatUsernamesOptions['SpecialSearch']['enabled']) {
      return true;
    }
    
    if ($title->mNamespace == 2) { // User: namespace
      $user = User::newFromName( $title->mDbkeyform );
      $realname = htmlspecialchars( trim( $user->getRealname() ) );
      
      if ($realname === ""  && !$wgFormatUsernamesOptions['name-forceblank']) {
        return true;
      }
      
      $title->mTextform = wfFormatUsernamesDisplay(array('username'=>$title->mTextform, 'realname'=>$realname), 'SpecialSearch');
    } 
    return true;
  } // function
} // class