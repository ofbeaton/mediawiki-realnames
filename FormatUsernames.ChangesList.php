<?php 

/**
 * @file
 * @ingroup Extensions
 * @license cc-by-sa http://creativecommons.org/licenses/by-sa/3.0/  
 * @since 0.1, MediaWiki 1.14.0
 */

if ( !defined( 'MEDIAWIKI' ) ) {
        die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

/**
 * @ingroup Extensions
 * @since 0.1, MediaWiki 1.14.0
 */ 
class ExtFormatUsernamesChangesList {
  
  /**
   * crudely replace the EnhancedChangesList with our own wrapper EnhancedChangesListHooked so we can rewrite the usernames.  
   * @param[in] $user The user viewing the recent changes list.
   * @param[in] $skin The user's skin.
   * @param[inout] &$list The recent changes list. Should be returned as a ChangesList object.
   * @return true, continue hook processing
   * @since 0.1, MediaWiki 1.10.0     
   * @see hook documentation http://www.mediawiki.org/wiki/Manual:Hooks/FetchChangesList
   * @todo play nice with http://www.mediawiki.org/wiki/Extension:Improved_Access_Control (ofb 2011-09-15)   
   */     
  public function newClass($user, $skin, &$list) {
    global $wgFormatUsernamesOptions,$wgRequest;
    
    if (!$wgFormatUsernamesOptions['ChangesList']['enabled']) {
      return true;
    }
     
    // code from includes/ChangesList.php, would rather have a hook to tweak the output instead of forced copy+paste
    
    $sk = $user->getSkin();
    $list = null;
    $new = $wgRequest->getBool( 'enhanced', $user->getOption( 'usenewrc' ) );
    if ($new) {
      $list = new EnhancedChangesListHooked( $sk );  
      return false; // we replaced the object, don't create a new one
    } else
      return true; // catch it in oldRow()  
  } // function
  
  /**
   * replaces the usernames in the old-style RecentChanges and Watchlist using the hook in OldChangesList  
   * @param[inout] &$changeslist The OldChangesList instance
   * @param[inout] &$s  HTML of the form "<li>...</li>" containing one RC entry
   * @param $rc The RecentChange object
   * @return true, continue hook processing
   * @since 0.1, MediaWiki 1.14.0     
   * @see hook documentation http://www.mediawiki.org/wiki/Manual:Hooks/OldChangesListRecentChangesLine   
   */
  public function oldRow(&$changeslist, &$s, $rc) {
    global $wgFormatUsernamesOptions;
  
    $realname = htmlspecialchars( trim( $rc->mAttribs['user_real_name'] ) );
    if (!$wgFormatUsernamesOptions['ChangesList']['enabled'] 
      || ($realname === ""  && !$wgFormatUsernamesOptions['name-forceblank'])
      ) {
      return true; # nothing to do
    }
  
    // example $s: (<a href="/w/index.php?title=OFB:Copyright&amp;curid=7&amp;diff=71&amp;oldid=70" title="OFB:Copyright" tabindex="1">diff</a> | <a href="/w/index.php?title=OFB:Copyright&amp;curid=7&amp;action=history" title="OFB:Copyright">hist</a>) . .   <a href="/w/index.php?title=OFB:Copyright" title="OFB:Copyright">OFB:Copyright</a>?; 22:54:43 . . <span class='mw-plusminus-neg'>(-31)</span>  . . <a href="/w/index.php?title=User:Finlay&amp;action=edit&amp;redlink=1" class="new mw-userlink" title="User:Finlay (page does not exist)">Finlay</a> <span class="mw-usertoollinks">(<a href="/w/index.php?title=User_talk:Finlay&amp;action=edit&amp;redlink=1" class="new" title="User talk:Finlay (page does not exist)">Talk</a> | <a href="/w/index.php?title=Special:Contributions/Finlay" title="Special:Contributions/Finlay">contribs</a> | <a href="/w/index.php?title=Special:Block/Finlay" title="Special:Block/Finlay">block</a>)</span> <span class="comment">(testing)</span> <span class="mw-rollback-link">[<a href="/w/index.php?title=OFB:Copyright&amp;action=rollback&amp;from=Finlay&amp;token=f5d1d4cd97206485836ad8af5e15d9b1%2B%5C" title="&quot;Rollback&quot; reverts edit(s) to this page of the last contributor in one click">rollback</a>]</span>
  
    $m = array();
    if(preg_match('/^(.*?)(<a\b[^>]*class="[^>]*mw-userlink"[^>]*>)([^<]*)(<\/a\b[^>]*>)(.*)$/',$s,$m)) {
      $s = $m[1].wfFormatUsernamesDisplay( array('linkstart'=>$m[2], 'username'=>$m[3], 'realname'=>$realname, 'linkend'=>$m[4]), 'ChangesList' ).$m[5];
    }
  
    return true;
  } // function
  
  /**
   * add real name to db fetched fields for use in row()  
   * @param[inout] &$conds array of where conditionals for query
   * @param[inout] &$tables array of tables to be queried
   * @param[inout] &$join_conds join conditions for the tables
   * @param[in] $opts FormOptions for this request
   * @param[inout] &$query_options additional query options
   * @param[inout] &$select array of table fields to be fetched
   * @return true, continue hook processing     
   * @since 0.1, MediaWiki 1.13.0
   * @see hook documentation http://www.mediawiki.org/wiki/Manual:Hooks/SpecialRecentChangesQuery
   * @note Called when building sql query for SpecialRecentChanges.       
   */     
  function query( &$conds, &$tables, &$join_conds, $opts, &$query_options, &$select ) {
    global $wgFormatUsernamesOptions;
    
    if (!$wgFormatUsernamesOptions['ChangesList']['enabled']) {
      return true;
    }
    
    // we need to join the users in so we can see user_real_name
    $tables[] = 'user';
    $join_conds['user'] = array('LEFT JOIN', 'rc_user=user_id');
    
    return true; 
  } // function
  
} // class

/**
 * @ingroup Extensions
 * @since 0.1
 */  
class EnhancedChangesListHooked extends EnhancedChangesList {
  /**
   * load and display all the change block groups     
   * @return all output from blocks of changes   
   * @since 0.1
   * @see EnhancedChangesList::recentChangesBlock()   
   */    
  protected function recentChangesBlock() {
    global $wgFormatUsernamesOptions;

    if (!$wgFormatUsernamesOptions['ChangesList']['enabled']) {
      return parent::recentChangesBlock();
    }
    
    foreach( $this->rc_cache as $block ) {
      foreach( $block as &$rcObj ) {
        $realname = htmlspecialchars( trim( $rcObj->mAttribs['user_real_name'] ) );
        if ($realname === ""  && !$wgFormatUsernamesOptions['name-forceblank']) {
          continue; # nothing to do
        }
      
        // example userlink: <a href="/w/index.php?title=User:Test2&amp;action=edit&amp;redlink=1" class="new mw-userlink" title="User:Test2 (page does not exist)">Test2</a>
        $m = array();
        if(preg_match('/^(.*?)(<a\b[^>]*>)([^<]*)(<\/a\b[^>]*>)(.*)$/',$rcObj->userlink,$m)) {
          $rcObj->userlink = $m[1].wfFormatUsernamesDisplay( array('linkstart'=>$m[2], 'username'=>$m[3], 'realname'=>$realname, 'linkend'=>$m[4]), 'ChangesList' ).$m[5];
        }
      } // foreach change
    } // foreach block
    
    $ret = parent::recentChangesBlock();

    return $ret;
  } // function
  
} // class