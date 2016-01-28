# OreDict
A MediaWiki extension that mimics the Ore Dictionary in Minecraft Forge.

## Installation
This extension works together with [the Tilesheets extension](https://github.com/hydrawiki/tilesheets), so you'll want to install that as well.

### Installation Steps
* Place the `OreDict` directory (this repository) into the extension directory at the root of your MediaWiki Install.
* Load the extension in your LocalSettings.php file (for more information, see [Extension Registration](https://www.mediawiki.org/wiki/Manual:Extension_registration))
    * For MediaWiki 1.25+, simply add `wfLoadExtension('OreDict')`
    * For MediaWiki 1.24 and below, add `require_once("$IP/extensions/FooBar/FooBar.php")`
* Run the MediaWiki update script
    * `php maintenance/Update.php` (if in your MediaWiki root directory)

## Configuration

Some group(s) will need to have the `editoredict` and `importoredict` rights, otherwise the extension will not be usable.
Typically, groups of power should have `editoredict`, and bots should have `importoredict`.

### Example Config
```
$wgGroupPermissions['bureaucrat']['editoredict']    = true;
$wgGroupPermissions['sysop']['editoredict']    = true;
$wgGroupPermissions['bot']['importoredict']    = true;
```

For more information about settings up User Rights, see the [Official MediaWiki Documentation](https://www.mediawiki.org/wiki/Manual:User_rights)

## Additional Information

For detailed usage information, see the [help wiki](https://help.gamepedia.com/Extension:OreDict).
