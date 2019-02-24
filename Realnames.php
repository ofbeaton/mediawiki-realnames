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

/** Mediawiki Special:Version data */
$wgExtensionCredits['parserhook'][] = array(
  'name' => 'Realnames',
  'author' => array( '[http://ofbeaton.com/ Finlay Beaton]', '...' ),
  'version' => '0.6.2',
  'url' => 'http://www.mediawiki.org/wiki/Extension:Realnames',
  'description' => 'Displays a user\'s real name everywhere',
 );

/** Extension default configuration, docs in README.md, overwrite in LocalSettings.php */
$wgRealnamesLinkStyle = 'paren-reverse';
$wgRealnamesLinkStyleBlankName = 'standard';
$wgRealnamesLinkStyleSameName = 'standard';
$wgRealnamesBareStyle = false;
$wgRealnamesBareStyleBlankName = false;
$wgRealnamesBareStyleSameName = false;
$wgRealnamesBlank = false;
$wgRealnamesReplacements = array(
	'title' => true,
	'subtitle' => true,
	'personnal' => true,
	'body' => true,
  );
/**
 * $1  link start
 * $2  username
 * $3  real name
 * $4  link end
 */
$wgRealnamesStyles = array(
	'standard' => '$1$2$4',
	'append' => '$1$2 [$3]$4',
	'replace' => '$1$3$4',
	'reverse' => '$1$3 [$2]$4',
	'dash' => '$1$2 &ndash; $3$4',
	'dash-reverse' => '$1$3$4 &ndash; $2',
	'paren-append' => '$1$2 ($3)$4',
	'paren-reverse' => '$1$3 ($2)$4',
);
$wgRealnamesSmart = array(
	'same' => true,
);
/**
 * do not include the ':'
 * this is a regexp so escaping may be required.
 */
$wgRealnamesNamespaces = array();

/** Mediawiki extension plugin */
$wgAutoloadClasses['ExtRealnames'] = dirname( __FILE__ ) . '/Realnames.body.php';
$wgHooks['BeforePageDisplay'][] = 'ExtRealnames::hookBeforePageDisplay';
$wgHooks['PersonalUrls'][] = 'ExtRealnames::hookPersonalUrls';
