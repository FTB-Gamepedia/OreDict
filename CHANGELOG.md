# Changelog
This changelog only shows recent version history, because of the lack of documentation from the former maintainers. The very first changelog (1.0.2) is likely incomplete.

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
