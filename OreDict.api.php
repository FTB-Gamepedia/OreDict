<?php
/**
 * OreDict API class
 *
 * @author Noah Manneschmidt <nmanneschmidt@curse.com>
 */

/**
 * Actions available:
 *  1. get a list of entries with filtering (by tag, prefix, or mod)
 *  2. add entries
 *  3. delete entries
 *  4. edit entries
 */
class OreDictApi extends ApiBase {
	public function getDescription() {
		return 'Provides an API to query and modify entries of the OreDict extension.';
	}

	/**
	 * Returns example usage url
	 */
	public function getExamples() {
		return [
			'api.php?action=oredict&oredict-search-tag=logWood',
			'api.php?action=oredict&oredict-search-tag=logWood&oredict-search-mod=V',
			'api.php?action=oredict&oredict-add=<newentry1>|<newentry2>|<newentry3>&token=<edittoken>',
			'api.php?action=oredict&oredict-del=1|2|3|4&token=<edittoken>',
		];
	}

	/**
	 * Returns the descriptions of the various parameters that are supported by this API
	 *
	 * @return	array	param names as keys, english descriptions as values
	 */
	protected function getParamDescription() {
		return [
			'token' => 'The edit token for the current user. Required for write actions.',
			'oredict-add' => [
				'Provides data for new entries to be created. Multiple entries are separated by a pipe character.',
				'The data format is TBD, but likely to be comma separated fields matching the DB layout.'
			],
			'oredict-edit' => [
				'Provides new data for existing entries to be updated. Multiple entries are separated by a pipe character.',
				'The data format is TBD, but will resemble the format used for oredict-add with the addition of an ID number.'
			],
			'oredict-del' => 'Provides IDs of existing entries to be deleted. Multiple entries are separated by a pipe character.',
			'oredict-search-prefix' => 'Limits the search results returned to those having a name with the given prefix.',
			'oredict-search-tag' => 'Limits the search results returned to those having a given tag.',
			'oredict-search-mod' => 'Limits the search results returned to those belonging to a given mod.',
		];
	}

	/**
	 * Defines names and types of parameters that can be used with this API
	 *
	 * @return	array
	 */
	protected function getAllowedParams() {
		return [
			# required for editing actions
			'token' => [
				ApiBase::PARAM_TYPE => 'string',
			],
			# editing actions
			'oredict-add' => [
				ApiBase::PARAM_TYPE => 'string',  # objects aren't supported types, so we have to agree on an encoding format
				ApiBase::PARAM_ISMULTI => true,   # accepts multiple values (separated by a pipe character)
				ApiBase::PARAM_ALLOW_DUPLICATES => false,
			],
			'oredict-edit' => [
				ApiBase::PARAM_TYPE => 'string',  # same encoding format as add, but with an id
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_ALLOW_DUPLICATES => false,
			],
			'oredict-del' => [
				ApiBase::PARAM_TYPE => 'integer', # id of an entry to delete
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_ALLOW_DUPLICATES => false,
				ApiBase::PARAM_MIN => 1,          # id < 1 is invalid
			],
			# searching filters
			'oredict-search-prefix' => [
				ApiBase::PARAM_TYPE => 'string',
			],
			'oredict-search-tag' => [
				ApiBase::PARAM_TYPE => 'string',
			],
			'oredict-search-mod' => [
				ApiBase::PARAM_TYPE => 'string',
			],
		];
	}

	/**
	 * Returns true when an editing action is being attempted
	 */
	private function isEditAction() {
		$editParams = ['oredict-add', 'oredict-edit', 'oredict-del'];
		foreach ($editParams as $param) {
			$val = $this->getParameter($param);
			if (!empty($val)) return true;
		}
		return false;
	}

	/**
	 * Returns true when a searching action is being attempted
	 */
	private function isSearchAction() {
		$searchParams = ['oredict-search-prefix', 'oredict-search-tag', 'oredict-search-mod'];
		foreach ($searchParams as $param) {
			$val = $this->getParameter($param);
			if (!empty($val)) return true;
		}
		return false;
	}

	public function needsToken() {
		return $this->isEditAction();
	}

	public function mustBePosted() {
		return false;
		return $this->isEditAction();
	}

	public function getTokenSalt() {
		return $this->needsToken() ? '' : false;
	}

	/**
	 * Main execution entry point
	 */
	public function execute() {
		if ($this->isEditAction()) {
			// enforce permissions
			if ($this->getUser()->isAllowed('editoredict')) {
				$this->doDelete();
				$this->doEdit();
				$this->doAdd();
			} else {
				$this->dieUsage(wfMessage('badaccess-groups', 'editoredict', 1)->text(), 'permission_needed');
			}
		}

		if ($this->isSearchAction()) {
			$results = $this->doSearch();
			$this->getResult()->setIndexedTagName($results, 'entry');
			$this->getResult()->addValue($this->getModuleName(), 'entries', $results);
		}
	}

	/**
	 * Delete the entries given by the IDs in the oredict-del parameter
	 *
	 * @return	boolean		true for success or false for failure
	 */
	protected function doDelete() {
		$entryIds = $this->getParameter('oredict-del');
		if (empty($entryIds)) return;
		// TODO delete entries with given ids

		// add success/fail result to the returned data
		$result = true;
		$this->getResult()->addValue([$this->getModuleName(), 'actionresult'], 'delete', $result);
	}

	protected function doEdit() {
		$editData = $this->getParameter('oredict-edit');
		if (empty($editData)) return;
		// TODO edit entries with given data

		// add success/fail result to the returned data
		$result = true;
		$this->getResult()->addValue([$this->getModuleName(), 'actionresult'], 'edit', $result);
	}

	protected function doAdd() {
		$newEntries = $this->getParameter('oredict-add');
		if (empty($newEntries)) return;
		// TODO create entries with given data

		// add success/fail result to the returned data
		$result = true;
		$this->getResult()->addValue([$this->getModuleName(), 'actionresult'], 'add', $result);
	}

	protected function doSearch() {
		$prefix = $this->getMain()->getVal('oredict-search-prefix');
		$tag = $this->getMain()->getVal('oredict-search-tag');
		$mod = $this->getMain()->getVal('oredict-search-mod');
		// TODO perform search and return an array of matching entries

		// TODO remove this demo data when properly implemented above
		return [
			[
				'entry_id' => 1,
				'tag_name' => 'logWood',
				'item_name' => 'Oak Wood',
				'mod_name' => 'V',
				'grid_params' => '',
				'flags' => 207
			],
			[
				'entry_id' => 2,
				'tag_name' => 'logWood',
				'item_name' => 'Spruce Wood',
				'mod_name' => 'V',
				'grid_params' => '',
				'flags' => 207
			],
			[
				'entry_id' => 3,
				'tag_name' => 'logWood',
				'item_name' => 'Birch Wood',
				'mod_name' => 'V',
				'grid_params' => '',
				'flags' => 207
			],
			[
				'entry_id' => 4,
				'tag_name' => 'logWood',
				'item_name' => 'Jungle Wood',
				'mod_name' => 'V',
				'grid_params' => '',
				'flags' => 207
			],
		];
	}
}
