<?php 

/*
Copyright 2011 Olivier Finlay Beaton. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are
permitted provided that the following conditions are met:

   1. Redistributions of source code must retain the above copyright notice, this list of
      conditions and the following disclaimer.

   2. Redistributions in binary form must reproduce the above copyright notice, this list
      of conditions and the following disclaimer in the documentation and/or other materials
      provided with the distribution.

THIS SOFTWARE IS PROVIDED BY <COPYRIGHT HOLDER> ''AS IS'' AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/ 

/*
  By comitting against this file you agree to assign copyright for your changes
  to Olivier Finlay Beaton under the same BSD-2-Clause license (attribution). 
*/

/**
 * @file
 * @ingroup Extensions
 * @authors Olivier Finlay Beaton (olivierbeaton.com) 
 * @copyright BSD-2-Clause http://www.opensource.org/licenses/BSD-2-Clause  
 * @since 2011-09-15, 0.1
 * @note requires MediaWiki 1.7.0
 * @note coding convention followed: http://www.mediawiki.org/wiki/Manual:Coding_conventions 
 */

if ( !defined( 'MEDIAWIKI' ) ) {
        die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

/**
 * @ingroup Extensions
 * @since 2011-09-15, 0.1
 * @note requires MediaWiki 1.7.0
 */ 
class ExtRealnames { 
  /**
   * A cache of realnames for given users
   * @since 2011-09-16, 0.1    
   */     
  // this used to be a cache of User objects as $users
  protected static $realnames = array();
  
  /**
   * Specify the <em>rc-debug=true</em> key/value pair in your GET parameters 
   *  to see the debug messages from this extension about the replacements it's doing.
   *  @note populated on first call to debug(), null until then 
   *  @since 2011-09-15, 0.1 
   */ 
  protected static $debug = null;
  
  /**
   * namespace regex option string
   * @since 2011-09-16, 0.2
   */         
  protected static $namespacePrefixes = false;
  
  /**
   * checks a data set to see if we should proceed with the replacement.
   * @param[in] $matches \array keyed with regex matches   
   * @return \string text to replace the match with     
   * @since 2011-09-16, 0.1    
   * @see lookForBare() for regex       
   */     
  protected static function checkBare($matches) {
    // matches come from self::lookForBare()'s regular experession 
    $m = array(
      'all' => $matches[0],
      'username' => $matches[1],
    );
        
    if (self::debug()) {
      echo 'checkBare: ';
      var_dump($m);
      echo "<br/>\n";    
    }
    
    // we do not currently do any checks on Bare replacements, a User: find is 
    // always valid but we could add one in the future, and the debug 
    // information is still conveniant and keeps things consistent with checkLink
        
    return self::replace($m);
  } // function
  
  
  /**
   * checks a data set to see if we should proceed with the replacement.
   * @param[in] $matches \array keyed with regex matches 
   * @return \string text to replace the match with                
   * @since 2011-09-16, 0.1  
   * @see lookForBare() for regex      
   */
  protected static function checkLink($matches) {
    // matches come from self::lookForLinks()'s regular experession 
    $m = array(
      'all' => $matches[0],
      'linkstart' => $matches[1],
      'linkuser' => $matches[2],
      'username' => $matches[3],
      'linkend' => $matches[4],
    );
    
    if (self::debug()) {
      echo 'checkLink: ';
      var_dump($m);
      echo "<br/>\n";    
    }
   
    // some links point to user pages but do not display the username, we can safely ignore those
    // we need to urldecode the link for accents and special characters,
    // and ensure our username has underscores instead of spaces to match our link
    // before being able to do the comparison.
    if (urldecode($m['linkuser']) != str_replace(' ','_',$m['username'])) { 
      return $m['all'];
    }
         
    return self::replace($m);
  } // function
  
  /**
   * checks if we are printing debug messages
   * @return \bool true/false
   * @since 2011-09-17, 0.1.1
   * @note sets $debug if set to null based on <em>rn-debug</em> parameter   
   * @todo use $out->getRequest for 1.18         
   */     
  protected static function debug() {
    global $wgRequest;
    if (self::$debug === null) {
      self::$debug = $wgRequest->getBool('rn-debug');
    }
    return self::$debug;
  } // function
   
  /**
   * formats the final string in the configured style to display the real name.  
   * @param[in] $m \array keyed with strings called 
   *    \li<em>linkstart</em>
   *    \li<em>username</em> 
   *    \li<em>realname</em>
   *    \li<em>linkend</em>
   * @return \string formatted text to replace the match with                
   * @since 2011-09-16, 0.1
   * @see $wgRealnamesLinkStyle
   * @see $wgRealnamesBareStyle
   * @see $wgRealnamesStyles
   * @see $wgRealnamesBlank            
   */ 
  protected static function display($m) {
    global $wgRealnamesLinkStyle, $wgRealnamesBareStyle, 
      $wgRealnamesStyles, $wgRealnamesBlank;
    
    // what kind of formatting will we do?
    $style = $wgRealnamesLinkStyle;
    if (empty($m['linkstart'])) {
      if ($wgRealnamesBareStyle !== false) {
        $style = $wgRealnamesBareStyle;
      }      
      $m['linkstart'] = '';
      $m['linkend'] = '';
    }
    
    if (empty($style)) {
      // error
      if (self::debug()) {
        echo 'display: error, blank style configuration<br>\n';          
      }
      return $m['all'];
    }
    
    // get the formatting code      
    $format = $wgRealnamesStyles[$style];
    
    if (empty($style)) {
      // error
      if (self::debug()) {
        echo 'display: error, blank format configuration<br>\n';          
      }
      return $m['all'];
    }
    
    // we have a blank username, and the admin doesn't want to see them, 
    // or his chosen format will not display a username at all
    if (empty($m['realname']) && (
      !$wgRealnamesBlank || strpos($format,'$2') === false
      )) {
      // swap in the username where they expected the realname
      $format = str_replace('$3','$2',$format);          
    }
    
    // plug in our values to the format desired
    $text = wfMsgReplaceArgs($format, array( // redo to ensure order
      $m['linkstart'],
      str_replace('_', ' ',$m['username']),
      str_replace('_', ' ',$m['realname']),
      $m['linkend']
      )); 
    
    if (self::debug()) {
        echo "display: replacing with \n";
        var_dump($text);  
        echo "<br/>\n";         
      }
    
    return $text;  
  } // function  
   
  /**
   * gather list of namespace prefixes in the wiki's language.
   * this is a regex string.
   * @return \string regex namespace options
   * @since 2011-09-22, 0.2
   */              
  static function getNamespacePrefixes() {
    global $wgRealnamesNamespaces, $wgContLang, $wgNamespaceAliases;
    
    // if we already figured it all out, just use that again
    if (self::$namespacePrefixes !== false) {
      return self::$namespacePrefixes;
    }
        
    // always catch this one
    $namespaces = array('User');
    
    // add in user specified ones
    $namespaces = array_merge($namespaces, array_values($wgRealnamesNamespaces));
    
    // try to figure out the wiki language
    $lang = $wgContLang; 
    
    // user namespace's primary name in the wiki lang
    $namespaces[] = $lang->getNsText ( NS_USER );
     
     if (method_exists($lang,'getNameSpaceAliases')) {
      // namespace aliases and gendered namespaces (1.18+) in the wiki's lang
      foreach ($lang->getNamespaceAliases() as $name=>$space) {
        if ($space == NS_USER) {
          $namespaces[] = $name;
        }
      }
    } else {
      // try it the hard way... pre 1.16
      foreach ($wgNamespaceAliases as $name=>$space) {
        if ($space == NS_USER) {
          $namespaces[] = $name;
        }
      }
    }
    
    // clean up
    $namespaces = array_unique($namespaces); 
    
    self::$namespacePrefixes = '(?:'.implode('|',$namespaces).':)';
  } // function
   
  /**
   * change all usernames to realnames   
   * @param[inout] &$out OutputPage The OutputPage object.
   * @param[inout] &$sk Skin object that will be used to generate the page, added in 1.13.
   * @return \bool true, continue hook processing
   * @since 2011-09-16, 0.1      
   * @see hook documentation http://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
   * @note requires MediaWiki 1.7.0      
   */     
  public static function hookBeforePageDisplay(&$out, &$sk = false) {
    global $wgTitle;
    
    // pre 1.16 no getTitle()
    $title = method_exists($out,'getTitle') ? $out->getTitle() : $wgTitle;
        
    // special user page handling
    if ($title->getNamespace() == NS_USER) { // User:             
      // swap out the specific username from title
      // this overcomes the problem lookForBare has with spaces and underscores in names
      $out->setPagetitle(self::lookForBare($out->getPageTitle(),'/'.self::getNamespacePrefixes().'('.$title->getText().')/'));
    }
       
    // article title
    if (self::debug()) {
      echo "hookBeforePageDisplay: searching article title...<br>\n";          
    }
    // this should also affect the html head title
    $out->setPageTitle(self::lookForBare($out->getPageTitle()));

    // subtitle (say, on revision pages)
    if (self::debug()) {
      echo "hookBeforePageDisplay: searching article subtitle...<br>\n";          
    }
    $out->setSubtitle(self::lookForLinks($out->getSubtitle()));

    // article html text
    if (self::debug()) {
      echo "hookBeforePageDisplay: searching article body...<br>\n";          
    }
    $out->mBodytext = self::lookForLinks($out->getHTML()); 
 
    return true;
  } // function
  
  /**
   * change all usernames to realnames in url bar  
   * @param[inout] &$personal_urls \array the array of URLs set up so far
   * @param[inout] &$title \obj the Title object of the current article
   * @return \bool true, continue hook processing
   * @since 2011-09-22, 0.2      
   * @see hook documentation http://www.mediawiki.org/wiki/Manual:Hooks/PersonalUrls
   * @note requires MediaWiki 1.7.0 
   */     
  public static function hookPersonalUrls(&$personal_urls, &$title) {
    global $wgUser;
    
    // replace the name of the logged in user
    if (isset($personal_urls['userpage']) && isset($personal_urls['userpage']['text'])) {
      // fake the match, we know it's there
      $m = array(
        'all' => $personal_urls['userpage']['text'],
        'username' => $personal_urls['userpage']['text'],
        'realname' => $wgUser->getRealname(),
      );
      $personal_urls['userpage']['text'] = self::replace($m);        
    }
    return true;  
  } // function
  
  /**
   * scan and replace plain usernames of the form User:username into real names.
   * @param[in] \string text to scan
   * @param[in] \string pattern to match, \bool false for default
   * @return \string with realnames replaced in      
   * @since 2011-09-16, 0.1
   * @bug we have problems with users with underscores (they become spaces) or spaces, we tend to just strip the User: and leave the username, but we only modify the first word so some weird style might screw it up (2011-09-17, of     
   */     
  protected static function lookForBare($text,$pattern=false) {
    if (empty($pattern)) {
      // considered doing [^<]+ here to catch names with spaces or underscores, 
      // which works for most titles but is not universal 
      $pattern = '/'.self::getNamespacePrefixes().'([^ \t]+)/'; 
    }
    return preg_replace_callback(
      $pattern,
      array( __CLASS__, 'checkBare' ), // create_function is slow
      $text
      );            
  } // function
  
  /**
   * scan and replace username links into realname links
   * @param[in] \string text to scan
   * @param[in] \string pattern to match, \bool false for default   
   * @return \string with realnames replaced in
   * @since 2011-09-16, 0.1           
   */  
  protected static function lookForLinks($text,$pattern=false) {
    if (empty($pattern)) {
      $pattern = '/(<a\b[^">]+href="[^">]+'.self::getNamespacePrefixes().'([^"\\?\\&>]+)[^>]+>)'.self::getNamespacePrefixes().'?([^>]+)(<\\/a>)/';
    }  
    return preg_replace_callback(
      $pattern,
      array( __CLASS__, 'checkLink' ), // create_function is slow
      $text
      );
  } // function
  
  /**
   * obtains user information based on a match for future replacement
   * @param[in] $m \array keyed with strings called 
   *    \li<em>linkstart</em> (optional)
   *    \li<em>username</em> 
   *    \li<em>realname</em> (optional)
   *    \li<em>linkend</em> (optional)
   * @return \string formatted text to replace the match with                
   * @since 2011-09-16, 0.1  
   */     
  protected static function replace($m) {    
    if (self::debug()) {
      echo "replace: matched<br>\n";
    }
    
    if (!isset(self::$realnames[$m['username']])) {
      // we don't have it cached
      $realname = null;
      
      if (isset($m['realname'])) {
        // we got it elsewhere
        $realname = $m['realname'];
      } else {      
        // time to do a lookup
        $user = User::newFromName( $m['username'] );
        
        if (!is_object($user)) {
          if (self::debug()) {
            echo "replace: skipped, invalid user: ".$m['username']."<br>\n";          
          }  
          return $m['all'];
        }
        
        $realname = $user->getRealname();
      }
            
      self::$realnames[$m['username']] = htmlspecialchars( trim( $realname ));  
    }  

    // this may be blank      
    $m['realname'] = self::$realnames[$m['username']];

    return self::display($m);
  } // function

} // class
