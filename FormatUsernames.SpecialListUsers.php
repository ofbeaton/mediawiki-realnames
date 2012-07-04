<?php 

/**
 * @file
 * @ingroup Extensions
 * @license cc-by-sa http://creativecommons.org/licenses/by-sa/3.0/  
 * @since 0.1, MediaWiki 1.13.0 
 * @note derived from extension ShowRealUsernames http://www.mediawiki.org/wiki/Extension:ShowRealUsernames (public domain)
 */

if ( !defined( 'MEDIAWIKI' ) ) {
        die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

/**
 * @ingroup Extensions
 * @since 0.1, MediaWiki 1.13.0
 */ 
class ExtFormatUsernamesSpecialListUsers {
  /**
   * add real name to db fetched fields for use in row()   
   * @param[in] $pager The UsersPager instance
   * @param[inout] &$query The query array to be returned
   * @return true, continue hook processing        
   * @since 0.1, MediaWiki 1.13.0
   * @see hook documentation http://www.mediawiki.org/wiki/Manual:Hooks/SpecialListusersQueryInfo
   * @note function called right before the end of UsersPager::getQueryInfo()         
   */     
  public function query($pager, &$query) {
    global $wgFormatUsernamesOptions;
    
    if (!$wgFormatUsernamesOptions['SpecialListUsers']['enabled']) {
      return true;
    }
    
    $query['fields'][] = 'user_real_name';  
    
    return true;
  } // function
  
  /**
   * @param[inout] &$item HTML to be returned. Will be wrapped in <li></li> after the hook finishes
   * @param[in] $row Database row object     
   * @return true, continue hook processing
   * @since 0.1, MediaWiki 1.13.0     
   * @see hook documentation http://www.mediawiki.org/wiki/Manual:Hooks/SpecialListusersFormatRow
   */     
  public function row(&$item, $row) {
    global $wgFormatUsernamesOptions;
    
    $realname = htmlspecialchars( trim( $row->user_real_name ) );
    if (!$wgFormatUsernamesOptions['SpecialListUsers']['enabled'] 
      || ($realname === ""  && !$wgFormatUsernamesOptions['name-forceblank'])
      ) {
      return true; # nothing to do
    }
    
    // example $item: <a href="/w/index.php?title=User:Finlay&amp;action=edit&amp;redlink=1" class="new" title="User:Finlay (page does not exist)">Olivier Finlay Beaton</a> ‎(<a href="/w/index.php?title=OFB:Bureaucrats&amp;action=edit&amp;redlink=1" class="new" title="OFB:Bureaucrats (page does not exist)">bureaucrat</a>, <a href="/w/index.php?title=OFB:Administrators&amp;action=edit&amp;redlink=1" class="new" title="OFB:Administrators (page does not exist)">administrator</a>) (Created on 2010-06-03 at 20:05:53)
    $m = array();
    if(preg_match('/^(.*?)(<a\b[^>]*>)([^<]*)(<\/a\b[^>]*>)(.*)$/',$item,$m)) {    
      $item = $m[1].wfFormatUsernamesDisplay( array('linkstart'=>$m[2], 'username'=>$m[3], 'realname'=>$realname, 'linkend'=>$m[4]),'SpecialListUsers' ).$m[5];
    }
    
    return true;
  } // function
  
} // class