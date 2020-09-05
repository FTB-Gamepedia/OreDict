# Changelog
This changelog only shows recent version history, because of the lack of documentation from the former maintainers. The very first changelog (1.0.2) is likely incomplete.

## Version 3
### 3.3.5
* Remove unused OreDictForm class.
* Update ImportOreDict, OreDictEntryManager (update form only), and OreDictList to use HTMLFormg (#74).
* Fix license issue on Special:Version (#76) (xbony2).
* editEntry: Use sanitized inputs in SQL UPDATE. We sanitized the inputs to make sure there are no bad (empty, invalid string) values, but then use the provided inputs rather than the sanitized inputs in the SQL query. This was introduced in #54 (v3.2.0) and was exposed in the API as seen in #77.

### 3.3.4
* Fix API descriptions/summaries (#73).

### 3.3.3
* Fix ImportOreDict textarea multiline
* Remove wgShowExceptionDetails config assignment

### 3.3.2
* Fix GrantPermissions (#66).
* Update `mw.util.wikiGetlink`, deprecated in 1.23, to `mw.util.getUrl` (#70) (chaud).

### 3.3.1
* Return ID as int instead of string in oredictsearch list API and oredictentry prop API (#63).
* Fix comma splice in update hint on import page (#61).
* Fix editentry log entry ID (#60).
* Fix type in Special:Version.
* Add missing internationalization for "No warnings," Notice in the edit window.

### 3.3.0
* New `no-fallback` param for `#dict` to prevent the fallback icon from showing for missing OreDict entries. OreDict entries that direct to missing tiles still display the fallback icon (#57).
* "Entry already exists and no changes were made!" message no longer shows when creating an entry in the OreDictEntryManager; it also doesn't submit redundant queries to the database (#58).
* Fix entries per page in OreDictList (#56).
* Improve OreDictList display message to be more correct (#59).
* Localization for errors and warnings (#43).
* Don't show empty column in OreDictList when the user cannot edit entries (#55).
* Correct ImportOreDict textarea width
* Fix up weird spacing in ImportOreDict import button area

### 3.2.0
* Sanitize/validate tag name, item name, and mod abbreviation inputs (#53, #54).
* Use localization for "create new entry" button in OreDictEntryManager.
* Fix OreDictEntryManager "create new entry" filter button doing nothing (#50).
* Add a button to OreDictEntryManager to delete the entry, disabled when creating a new entry (#51).
* Update OreDictEntryManager, OreDictList, and ImportOreDict to use OOjs UI (#52).

### 3.1.0
* Add ability to grant permissions to bots (#48, #49)
* Remove flag-related things from the log entries (#47)

### 3.0.0
* Remove the entire flag/mode/call type system and flags column (schema update required) (#46, #44).
* Localize "Importing entries." log comment
* Localize everything applicable in the following special pages:
  * ImportOreDict
  * OreDictEntryManager
  * OreDictList
* Use `Message#text` instead of the `Message` directly in `returnMessage`
* API help messages are now localized (part of #43).
* Source code is officially licensed under the MIT license (#41).

## Version 2
### 2.1.0
* Use query continue API for the OreDictSearch query API (#38)

### 2.0.1
* New offset parameter in oredictsearch for continuation (#34).
* oredictsearch no longer sorts by entry ID as the key; it is now an array of hashes.
* oredictentry and oredictsearch now return the entry ID with the rest of the data.
* Fix oredictsearch registration in the entry point (not the extension.json)
* Improve oredictsearch API query.
* Fix oredictsearch limit issues (#35).
* Fix oredictsearch
* Clean up database things and improve security.

### 2.0.0
* Fix OreDict entry shuffling and page loading issues related to that.
* Database sanitization issues that could later turn into security holes.
* Some general code style clean up.
* Fix OreDictList database error and limit error (#27).
* Fix some database queries resulted in 0 rows in exec (#dict) (#26).
* Implement a web API with the following (#1):
  * action=neworedict
  * action=deleteoredict
  * action=editoredict
  * action=query&prop=oredictentry
  * action=query&list=oredictsearch

## Version 1
### 1.1.0
* Upgrade to MediaWiki 1.26 things
* Improve OreDictList pagination
* Clean up codestyle and modernize database handling.
* Marking entries as deleted now actually removes their rows from the database, rather than simply flagging them as deleted. You will need to either manually delete all entries, wait for the API, or run an SQL query to delete all of the still-flagged entries.
* Modernize special page handling
* Add action and right localization
* Add generic Spanish as well as Nicaraguan Spanish localization

### 1.0.2
* Naming is now consistently "OreDict"
* Add missing log-description-oredict i18n message
