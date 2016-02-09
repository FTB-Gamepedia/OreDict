# Changelog
This changelog only shows recent version history, because of the lack of documentation from the former maintainers. The very first changelog (1.0.2) is likely incomplete.

## Version 2
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
