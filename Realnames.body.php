<?php

/*
Copyright 2011-2019 Finlay Beaton. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are
permitted provided that the following conditions are met:

   1. Redistributions of source code must retain the above copyright notice, this list of
      conditions and the following disclaimer.

   2. Redistributions in binary form must reproduce the above copyright notice, this list
      of conditions and the following disclaimer in the documentation and/or other materials
      provided with the distribution.

THIS SOFTWARE IS PROVIDED BY Finlay Beaton ''AS IS'' AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL Finlay Beaton OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

if ( !defined( 'MEDIAWIKI' ) ) {
        die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

/** >= 0.1 */
class ExtRealnames {
  protected static $realnames = array();
  protected static $namespacePrefixes = false;

  protected static function checkBare($matches) {
    // matches come from static::lookForBare()'s regular experession
    $m = array(
      'all' => $matches[0],
      'username' => $matches[1],
    );

    static::debug(__METHOD__, print_r($m,true));

    // we do not currently do any checks on Bare replacements, a User: find is
    // always valid but we could add one in the future, and the debug
    // information is still conveniant and keeps things consistent with checkLink

    return static::replace($m);
  } // function


  protected static function checkLink($matches) {
    // matches come from static::lookForLinks()'s regular experession
    $m = array(
      'all' => $matches[0],
      'linkstart' => $matches[1],
      'linkuser' => $matches[2],
      'username' => $matches[3],
      'linkend' => $matches[4],
    );

    static::debug(__METHOD__, print_r($m,true));

    // some links point to user pages but do not display the username, we can safely ignore those
    // we need to urldecode the link for accents and special characters,
    // and ensure our username has underscores instead of spaces to match our link
    // before being able to do the comparison.
    if (urldecode($m['linkuser']) != str_replace(' ','_',$m['username'])) {
      return $m['all'];
    }

    return static::replace($m);
  } // function

  protected static function debug($method, $text) {
    wfDebugLog('realnames', $method.': '.$text);  
  }

  protected static function display($m) {
    global $wgRealnamesLinkStyle, $wgRealnamesBareStyle,
      $wgRealnamesStyles, $wgRealnamesBlank, $wgRealnamesSmart,
      $wgRealnamesLinkStyleBlankName, $wgRealnamesBareStyleBlankName,
      $wgRealnamesLinkStyleSameName, $wgRealnamesBareStyleSameName;

    // what kind of formatting will we do?
    $style = $wgRealnamesLinkStyle;
    $styleBlankName = $wgRealnamesLinkStyleBlankName;
    $styleSameName = $wgRealnamesLinkStyleSameName;
    if (empty($m['linkstart'])) {
      if ($wgRealnamesBareStyle !== false) {
        $style = $wgRealnamesBareStyle;
      }
      if ($wgRealnamesBareStyleBlankName !== false) {
        $styleBlankName = $wgRealnamesBareStyleBlankName;  
      }
      if ($wgRealnamesBareStyleSameName !== false) {
        $styleSameName = $wgRealnamesBareStyleSameName;  
      }
      $m['linkstart'] = '';
      $m['linkend'] = '';
    }

    if (empty($style)) {
      // error
      static::debug(__METHOD__, 'error, blank style configuration');
      return $m['all'];
    }

    // get the formatting code
    $format = $wgRealnamesStyles[$style];

    if (empty($style)) {
      // error
      static::debug(__METHOD__, 'error, blank format configuration');
      return $m['all'];
    }

    // we have a blank realname, and the admin doesn't want to see them,
    // or his chosen format will not display a username at all
    if (empty($m['realname']) && (
      !$wgRealnamesBlank || strpos($format,'$2') === false
      )) {
        $format = $wgRealnamesStyles[$styleBlankName];
    }

    if ($wgRealnamesSmart !== FALSE
        && $wgRealnamesSmart['same'] === TRUE
        && $m['username'] === $m['realname']
        && strpos($format, '$2') !== FALSE
        && strpos($format, '$3') !== FALSE
      ) {
      // we only do this if both username and realname will be displayed in
      // the user's format

      static::debug(__METHOD__, 'smart dupe detected');

      // we're going to display: John - John
      // this is silly. The smart thing to do
      // is infact nothing (in the name)
      $format = $wgRealnamesStyles[$styleSameName];

    }

    // plug in our values to the format desired
    $text = wfMsgReplaceArgs($format, array( // redo to ensure order
      $m['linkstart'],
      str_replace('_', ' ',$m['username']),
      str_replace('_', ' ',$m['realname']),
      $m['linkend']
      ));

      static::debug(__METHOD__, 'replacing with '.print_r($text,true));

    return $text;
  } // function

  public static function getNamespacePrefixes() {
    global $wgRealnamesNamespaces, $wgContLang, $wgNamespaceAliases;

    // if we already figured it all out, just use that again
    if (static::$namespacePrefixes !== false) {
      return static::$namespacePrefixes;
    }

    // always catch this one
    $namespaces = array('User', 'User_talk', 'User talk');

    // add in user specified ones
    $namespaces = array_merge($namespaces, array_values($wgRealnamesNamespaces));

    // try to figure out the wiki language
    //! get language from the context somehow? (2011-09-26, ofb)
    $lang = $wgContLang;

    // user namespace's primary name in the wiki lang
    $namespaces[] = $lang->getNsText ( NS_USER );
    $namespaces[] = $lang->getNsText ( NS_USER_TALK );

    // namespace aliases and gendered namespaces (1.18+) in the wiki's lang
    // fallback for pre 1.16
    $nss = method_exists($lang, 'getNamespaceAliases') ? $lang->getNamespaceAliases() : $wgNamespaceAliases;
    foreach ($nss as $name=>$space) {
      if (in_array($space, array(NS_USER,NS_USER_TALK))) {
        $namespaces[] = $name;
      }
    }

    // clean up
    $namespaces = array_unique($namespaces);

    static::$namespacePrefixes = '(?:(?:'.implode('|',$namespaces).'):)';

    static::debug(__METHOD__, 'namespace prefixes: '.static::$namespacePrefixes);

    // how did I forget this line before?
    return static::$namespacePrefixes;
  } // function

  /**
   * >= 0.1
   * @note OutputPageBeforeHTML does not work for Special pages like RecentChanges or ActiveUsers   
   * @return \bool true, continue hook processing
   * @see hook documentation http://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
   * @note requires MediaWiki 1.7.0
   */
  public static function hookBeforePageDisplay(&$out, &$skin = false) {
    global $wgTitle, $wgRealnamesReplacements;

    // pre 1.16 no getTitle()
    $title = method_exists($out,'getTitle') ? $out->getTitle() : $wgTitle;

    if ($wgRealnamesReplacements['title'] === TRUE) {
      // article title
      static::debug(__METHOD__, "searching article title...");

      // special user page handling
      if (in_array($title->getNamespace(), array(NS_USER, NS_USER_TALK))) { // User:
        // swap out the specific username from title
        // this overcomes the problem lookForBare has with spaces and underscores in names
        $out->setPagetitle(static::lookForBare($out->getPageTitle(),'/'.static::getNamespacePrefixes().'\s*('.$title->getText().')(?:\/.+)?/'));
      }

      // this should also affect the html head title
      $out->setPageTitle(static::lookForBare($out->getPageTitle()));
    } // opt-out

    if ($wgRealnamesReplacements['subtitle'] === TRUE) {
      // subtitle (say, on revision pages)
      static::debug(__METHOD__, "searching article subtitle...");
      $out->setSubtitle(static::lookForLinks($out->getSubtitle()));
    } // opt-out

    if ($wgRealnamesReplacements['body'] === TRUE) {
      // article html text
      static::debug(__METHOD__, "searching article body...");
      $out->mBodytext = static::lookForLinks($out->getHTML());
    } // opt-out

    return true;
  } // function

  /** >= 0.2
   * change all usernames to realnames in skin top right links bar
   * @return \bool true, continue hook processing
   * @see hook documentation http://www.mediawiki.org/wiki/Manual:Hooks/PersonalUrls
   * @note requires MediaWiki 1.7.0
   * @note does nothing for Timeless skin
   */
  public static function hookPersonalUrls(&$personal_urls, $title) {
    global $wgUser, $wgRealnamesReplacements;

    if ($wgRealnamesReplacements['personnal'] === TRUE) {
      static::debug(__METHOD__, "searching personnal urls...");

      // replace the name of the logged in user
      if (isset($personal_urls['userpage']) && isset($personal_urls['userpage']['text'])) {
        // fake the match, we know it's there
        $m = array(
          'all' => $personal_urls['userpage']['text'],
          'username' => $personal_urls['userpage']['text'],
          'realname' => $wgUser->getRealname(),
        );
        $personal_urls['userpage']['text'] = static::replace($m);
      }
    } // opt out
    
    return true;
  } // function

  /**
   * @bug we have problems with users with underscores (they become spaces) or spaces,
   *    we tend to just strip the User: and leave the username, but we only modify the
   *    first word so some weird style might screw it up (2011-09-17, ofb)
   */
  protected static function lookForBare($text,$pattern=false) {
    if (empty($pattern)) {
      // considered doing [^<]+ here to catch names with spaces or underscores,
      // which works for most titles but is not universal
      $pattern = '/'.static::getNamespacePrefixes().'([^ \t]+)(\/.+)?/';
    }
    static::debug(__METHOD__, "pattern: ".$pattern);
    return preg_replace_callback(
      $pattern,
      array( __CLASS__, 'checkBare' ), // create_function is slow
      $text
      );
  } // function

  protected static function lookForLinks($text,$pattern=false) {
    if (empty($pattern)) {
      $pattern = '/(<a\b[^">]+href="[^">]+'.static::getNamespacePrefixes().'([^"\\?\\&>]+)[^>]+>(?:<bdi>)?)'.static::getNamespacePrefixes().'?([^>]+)((?:<\\/bdi>)?<\\/a>)/';
    }
    return preg_replace_callback(
      $pattern,
      array( __CLASS__, 'checkLink' ), // create_function is slow
      $text
      );
  } // function

  protected static function replace($m) {
    static::debug(__METHOD__, "matched ".(isset($m['username']) ? $m['username'] : print_r($m,true)));

    if (!isset(static::$realnames[$m['username']])) {
      // we don't have it cached
      $realname = null;

      if (isset($m['realname'])) {
        // we got it elsewhere
        $realname = $m['realname'];
      } else {
        // time to do a lookup
        $user = User::newFromName( $m['username'] );

        if (!is_object($user)) {
          static::debug(__METHOD__, "skipped, invalid user: ".$m['username']);
          return $m['all'];
        }

        $realname = $user->getRealname();
      }

      static::$realnames[$m['username']] = htmlspecialchars( trim( $realname ));
    }

    // this may be blank
    $m['realname'] = static::$realnames[$m['username']];

    return static::display($m);
  } // function

} // class
