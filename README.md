# OreDict
A MediaWiki extension that mimics the Ore Dictionary in Minecraft Forge.

## Installation
This extension works together with [the Tilesheets extension](https://github.com/hydrawiki/tilesheets), so you'll want to install that as well.

To install this extension, plop it in the extension directory as usual, and `require_once` the `OreDict.php` file in `LocalSettings.php`. Additionally, you'll need to run the SQL script `install/ext_oredict_items.sql` to set up the database. Otherwise, every time you try to edit or import ore dict entries, you will be met with a Database error saying that the table does not exist.

Lastly, some group(s) will need to have the `editoredict` and `importoredict` rights, otherwise the extension will not be usable. Typically, groups of power should have editoredict, and bots should have importoredict.

For detailed usage information, see the [help wiki](https://help.gamepedia.com/Extension:OreDict).
