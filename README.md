# Realnames

_Updated instructions and releases available at:_ https://www.mediawiki.org/wiki/Extension:Realnames 

This mediawiki extension attempts to add _realname_ to all _username_ links. 

Modifies a page's html content right before display to keep the approach generic and simple. This means it works for most links and does not need special consideration with new hooks. Compatibility with other extensions should be very strong. 

This means it works on:  
* Special:ListUsers
* Special:RecentChanges (new and old)
* Special:Search of the User: space
* History of page
* Revisions list of page
* Revision view of page
* Revision Compare
* Page header + html title (limited)
* and any page where user links are found

While the focus is on realname display, it can customized to change the display of username links to anything desired (add an image? another class?). This can be incredibly powerful.

It was developed mainly for Enterprise/Corporate users of MediaWiki where realnames are much more important, and often make much more sense than the usual algorithmically chosen usernames.

The default configuration prioritises realnames first with the username in parenthesis. Since usernames are still needed for wiki links, wiki actions, and realnames can sometimes collide; displaying both is recommended. This is optional, see the configuration options bellow.

There is currently no way to preserve User: prefixes on links or text.

## Download instructions
You can download the extension directly from github [releases](https://github.com/ofbeaton/mediawiki-realnames/releases).

Consult the (CHANGELOG) and (HISTORY) for version history.

## Installation
To install this extension, add the following to `LocalSettings.php`:
```php
wfLoadExtension( 'Realnames' );
#add optional configuration parameters here
```

or before mediawiki 1.25 instead do:
```php
include_once("$IP/extensions/Realnames/Realnames.php");
#add optional configuration parameters here
```

### Configuration parameters
#### $wgRealnamesLinkStyle [>=0.1]
Values | Example | Description
------ | ------- | -----------
"standard" | [mw305](User:mw305) | Provided for convenience, this is the default MediaWiki behavior
"replace" | [Martha Stewart](User:mw305)
"append" | [mw305 \[Martha Stewart\]](User:mw305)
"reverse" | [Martha Stewart \[mw305\]](User:mw305)
"dash" | [mw305 &ndash; Martha Stewart](User:mw305)
"dash-reverse" | [Martha Stewart &ndash; mw305](User:mw305)
"paren-append" | [mw305 (Martha Stewart)](User:mw305)
"paren-reverse" | [Martha Stewart (mw305)](User:mw305) | Default extension behaviour

#### $wgRealnamesBareStyle [>=0.1]
Values | Example | Description
------ | ------- | -----------
false | N/A | Uses same style name as $wgRealnamesLinkStyle<br>Default extension behaviour
"standard" | mw305 | Provided for convenience, this is the standard MediaWiki behavior
"replace" | Martha Stewart
"append" | mw305 \[Martha Stewart\]
"reverse" | Martha Stewart \[mw305\]
"dash" | mw305 &ndash; Martha Stewart
"dash-reverse" | Martha Stewart &ndash; mw305
"paren-append" | mw305 (Martha Stewart)
"paren-reverse" | Martha Stewart (mw305)

#### $wgRealnamesBlank [>=0.1]
Do you want to show blank real names? This can make sense for _append_ style but looks silly in _reverse_. The default _false_ which shows a _standard_ link instead.

Values:
* TRUE
* FALSE (default)

Output:
* mw305 []
* mw305

#### $wgRealnamesLinkStyleBlankName [>0.6]
The style to use on links when realname is blank, as long as `$wgRealnamesBlank === FALSE`. Default is `standard`.

Same value options as described for `$wgRealnamesLinkStyle`.

#### $wgRealnamesBareStyleBlankName [>0.6]
The style to use on text when username=realname, as long as `$wgRealnamesBlank === FALSE`. Default is `false` which shows `standard`. 

Same value options as described for `$wgRealnamesBareStyle`.

#### $wgRealnamesStyles [>=0.1]
Allows for the custom creation of style types that can then be assigned for ''link'' and ''bare'' styles. Usually to add custom text. You get 4 variables in your style:<br>
* `$1` link start<br>
* `$2`  username<br>
* `$3`  real name<br>
* `$4`  link end<br>
Ensure to use `'` quotes around your style string instead of `"` quotes, so that the `$x` do not get evaluated.

Value:

_array_

Example:

`$wgRealnamesStyles['mystyle'] = '&lt;span class="custom"&gt;$1$3$4&lt;/span&gt;';`

> Note: HTML does not work in Bare style.

#### $wgRealnamesReplacements [>=0.1]
Allows you to turn off replacement in specific sections.

Value:

_array_

Example:

```php
$wgRealnamesReplacements['title'] = TRUE;
$wgRealnamesReplacements['subtitle'] = TRUE;
$wgRealnamesReplacements['personnal'] = TRUE;
$wgRealnamesReplacements['body'] = TRUE;
```

#### $wgRealnamesSmart [>=0.3]
Allows you to turn off specific smart features

Value:

_array_

Example:

Key | Description
--- | ---
`$wgRealnamesSmart['same'] = TRUE;` | same &mdash; does not replace if username=realname

#### $wgRealnamesLinkStyleSameName [>0.6]
The style to use on links when username=realname, as long as `$wgRealnamesSmart['same'] === TRUE`. Default is `standard`. 

Same value options as described for `$wgRealnamesLinkStyle`.

#### $wgRealnamesBareStyleSameName [>0.6]
The style to use on text when username=realname, as long as `$wgRealnamesSmart['same'] === TRUE`. Default is `false` which shows `standard`. 

Same value options as described for `$wgRealnamesBareStyle`.

#### $wgRealnamesNamespaces [>=0.2]
Allows you to add more namespaces for it to search for. Use this only if the article name is the username in a given namespace. Do not include the :, and keep it mind this is a regular expression string, you can use regexp modifiers, but as well may need to escape some characters.

Value:

_array_

Example:

```php
$wgRealnamesNamespaces[] = 'CustomUserBasedNamespace';
```
### Testing
* MediaWiki 1.32.0, 1.31.1 (LTS), 1.18.0, 1.17.1, 1.16.5, 1.15.5,

## See also
* [Extension:LDAP_Authentication](https://www.mediawiki.org/wiki/Extension:LDAP_Authentication) popular import username/realnames from LDAP/AD
* [Extension:Windows_NTLM_LDAP_Auto_Auth](https://www.mediawiki.org/wiki/Extension:Windows_NTLM_LDAP_Auto_Auth) import username/realnames from LDAP/AD

## Alternative extensions
* [Extension:ShowRealUsernames](https://www.mediawiki.org/wiki/Extension:ShowRealUsernames)
