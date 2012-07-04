<?php 

/**
 * @file
 * @ingroup Extensions
 * @license cc-by-sa http://creativecommons.org/licenses/by-sa/3.0/  
 * @since 0.1, MediaWiki 1.13.0
 * @note non-gpl blind port/modification of PageHistoryRealnames by John Erling Blad http://www.mediawiki.org/wiki/Extension:PageHistoryRealnames
 */

if ( !defined( 'MEDIAWIKI' ) ) {
        die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

/**
 * @ingroup Extensions
 * @since 0.1, MediaWiki 1.13.0
 */ 
class ExtFormatUsernamesPageHistory {

  /**
   * add real name to db fetched fields for use in row()  
   * @param[inout] &$pager the pager, instance of class HistoryPager defined in HistoryPage.php
   * @param[inout] &$queryInfo the query parameters
   * @return true, continue hook processing     
   * @since 0.1, MediaWiki 1.13.0
   * @see hook documentation http://www.mediawiki.org/wiki/Manual:Hooks/PageHistoryPager::getQueryInfo
   * @note When a history pager query parameter set is constructed.       
   */     
  public function query(&$pager, &$queryInfo) {
    global $wgFormatUsernamesOptions;
    
    if (!$wgFormatUsernamesOptions['PageHistory']['enabled']) {
      return true;
    }
    
    // the extra field we want
    $queryInfo['fields'][] = 'user_real_name';
         
    // we need to join the users in so we can see user_real_name
    $queryInfo['tables'][] = 'user';
    $queryInfo['join_conds']['user'] = array('LEFT JOIN', 'user_id=rev_user');
  
    return true;                   
  } // function
  
  /**
   * @param[in] $history the calling PageHistory object
   * @param[inout] &$row the revision row for this line
   * @param[inout] $s the string representing this parsed line
   * @param[inout] $classes css classes  
   * @return true, continue hook processing
   * @since 0.1, MediaWiki 1.10.0     
   * @see hook documentation http://www.mediawiki.org/wiki/Manual:Hooks/PageHistoryLineEnding
   */     
  public function row($history, &$row, &$s, &$classes) {
    global $wgFormatUsernamesOptions;
    
    $realname = htmlspecialchars( trim( $row->user_real_name ) );
    if (!$wgFormatUsernamesOptions['PageHistory']['enabled'] 
      || ($realname === ""  && !$wgFormatUsernamesOptions['name-forceblank'])
      ) {
      return true; # nothing to do
    }
       
    // example $s: <span class="mw-history-histlinks">(cur | <a href="/w/index.php?title=Category:Main&amp;diff=68&amp;oldid=25" title="Category:Main">prev</a>) </span><input type="radio" value="68" style="visibility:hidden" name="oldid" id="mw-oldid-null" /><input type="radio" value="68" checked="checked" name="diff" id="mw-diff-68" /> <a href="/w/index.php?title=Category:Main&amp;oldid=68" title="Category:Main">2011-09-15T00:47:16</a> <span class='history-user'><a href="/w/index.php?title=User:Finlay&amp;action=edit&amp;redlink=1" class="new mw-userlink" title="User:Finlay (page does not exist)">Finlay</a>  <span class="mw-usertoollinks">(<a href="/w/index.php?title=User_talk:Finlay&amp;action=edit&amp;redlink=1" class="new" title="User talk:Finlay (page does not exist)">Talk</a> | <a href="/w/index.php?title=Special:Contributions/Finlay" title="Special:Contributions/Finlay">contribs</a> | <a href="/w/index.php?title=Special:Block/Finlay" title="Special:Block/Finlay">block</a>)</span></span> <span class="history-size">(17 bytes)</span> (<span class="mw-rollback-link"><a href="/w/index.php?title=Category:Main&amp;action=rollback&amp;from=Finlay&amp;token=b9eaaf86052ffcda7381d6bbff4d949e%2B%5C" title="&quot;Rollback&quot; reverts edit(s) to this page of the last contributor in one click">rollback</a></span> | <span class="mw-history-undo"><a href="/w/index.php?title=Category:Main&amp;action=edit&amp;undoafter=25&amp;undo=68" title="&quot;Undo&quot; reverts this edit and opens the edit form in preview mode. It allows adding a reason in the summary.">undo</a></span>) 
    
    $m = array();
    if(preg_match('/^(.*?)(<a\b[^>]*class="[^>]*mw-userlink"[^>]*>)([^<]*)(<\/a\b[^>]*>)(.*)$/',$s,$m)) {
      $s = $m[1].wfFormatUsernamesDisplay( array('linkstart'=>$m[2], 'username'=>$m[3], 'realname'=>$realname, 'linkend'=>$m[4]), 'PageHistory' ).$m[5];
    }
    
    return true;
  } // function
} // class