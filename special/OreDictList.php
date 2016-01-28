<?php
/**
 * OreDictList special page file
 *
 * @file
 * @ingroup Extensions
 * @version 1.0.1
 * @author Jinbobo <paullee05149745@gmail.com>
 * @license
 */

class OreDictList extends SpecialPage {
	protected $opts;

	public function __construct() {
		parent::__construct('OreDictList');
	}

	/**
	 * Return the group name for this special page.
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function getGroupName() {
		return 'oredict';
	}

	public function execute($par) {
		global $wgQueryPageDefaultLimit;
		$out = $this->getOutput();

		$this->setHeaders();
		$this->outputHeader();

		$opts = new FormOptions();

		$opts->add('limit', $wgQueryPageDefaultLimit);
		$opts->add('mod', '');
		$opts->add('tag', '');
		$opts->add('start', '');
		$opts->add('from', 1);
		$opts->add('page', 0);

		$opts->fetchValuesFromRequest($this->getRequest());
		$opts->validateIntBounds('limit', 0, 5000);

		// Give precedence to subpage syntax
		if (isset($par)) {
			$opts->setValue('from', $par);
		}

		// Bind to member variable
		$this->opts = $opts;

		$limit = intval($opts->getValue('limit'));
		$page = intval($opts->getValue('page'));

		// Load data
		$dbr = wfGetDB(DB_SLAVE);
		$results =  $dbr->select(
			'ext_oredict_items',
			'COUNT(`entry_id`) AS row_count',
			array(
				'entry_id >= '.intval($opts->getValue('from')),
				'mod_name LIKE '.$dbr->addQuotes($opts->getValue('mod')),
				'item_name BETWEEN '.$dbr->addQuotes($opts->getValue('start'))." AND 'zzzzzzzz'"
			)
		);
		foreach ($results as $result) {
			$maxRows = $result->row_count;
		}

		if (!isset($maxRows)) return;

		$order = $opts->getValue('start') == '' ? 'entry_id ASC' : 'item_name ASC';
		$results = $dbr->select(
			'ext_oredict_items',
			'*',
			array(
				'entry_id >= '.intval($opts->getValue('from')),
				'(mod_name = '.$dbr->addQuotes($opts->getValue('mod')).' OR '.$dbr->addQuotes($opts->getValue('mod')).' = \'\')',
				'(tag_name = '.$dbr->addQuotes($opts->getValue('tag')).' OR '.$dbr->addQuotes($opts->getValue('tag')).' = \'\')',
				'item_name BETWEEN '.$dbr->addQuotes($opts->getValue('start'))." AND 'zzzzzzzz'"
			),
			__METHOD__,
			array(
				'ORDER BY' => $order,
				'LIMIT' => intval($opts->getValue('limit')),
				'OFFSET' => $page * $limit
			)
		);

		// Output table
		$maxId = 0;
		$table = "{| class=\"mw-datatable\" style=\"width:100%\"\n";
		$msgTagName = wfMessage('oredict-tag-name');
		$msgItemName = wfMessage('oredict-item-name');
		$msgModName = wfMessage('oredict-mod-name');
		$msgGridParams = wfMessage('oredict-grid-params');
		$msgFlags = wfMessage('oredict-flags');
		$canEdit = in_array("editoredict", $this->getUser()->getRights());
		$table .= "! !! # !! $msgTagName !! $msgItemName !! $msgModName !! $msgGridParams !! $msgFlags\n";
		foreach ($results as $result) {
			$lId = $result->entry_id;
			$lTag = $result->tag_name;
			$lItem = $result->item_name;
			$lMod = $result->mod_name;
			$lParams = $result->grid_params;
			$lFlags = $result->flags;
			$table .= "|-\n";
			// Check user rights
			if ($canEdit) {
				$editLink = "[[Special:OreDictEntryManager/$lId|Edit]]";
			} else {
				$editLink = "";
			}
			$table .= "| style=\"width:23px; padding-left:5px; padding-right:5px; text-align:center; font-weight:bold;\" | $editLink || $lId || $lTag || $lItem || $lMod || $lParams || $lFlags\n";

			if ($lId > $maxId) $maxId = $lId;
		}
		$table .= "|}\n";

		// Page nav stuff
		// TODO possibly replace with our own pagination code?
		$page = $opts->getValue('page');
		$pPage = $page-1;
		$nPage = $page+1;
		$lPage = floor($maxRows / $limit);
		if ($page == 0) {
			$prevPage = "'''First Page'''";
		} else {
			if ($page == 1) {
				$prevPage = "[{{fullurl:{{FULLPAGENAME}}|start=".$opts->getValue('start')."&mod=".$opts->getValue('mod')."&limit=".$opts->getValue('limit')."}} &laquo; First Page]";
			} else {
				$prevPage = "[{{fullurl:{{FULLPAGENAME}}|start=".$opts->getValue('start')."&mod=".$opts->getValue('mod')."&limit=".$opts->getValue('limit')."}} &laquo; First Page] [{{fullurl:{{FULLPAGENAME}}|page={$pPage}&start=".$opts->getValue('start')."&mod=".$opts->getValue('mod')."&limit=".$opts->getValue('limit')."}} &lsaquo; Previous Page]";
			}
		}
		if ($lPage == $page - 1) {
			$nextPage = "'''Last Page'''";
		} else {
			if ($lPage == $page) {
				$nextPage = "[{{fullurl:{{FULLPAGENAME}}|page={$nPage}&start=".$opts->getValue('start')."&mod=".$opts->getValue('mod')."&limit=".$opts->getValue('limit')."}} Last Page &raquo;]";
			} else {
				$nextPage = "[{{fullurl:{{FULLPAGENAME}}|page={$nPage}&start=".$opts->getValue('start')."&mod=".$opts->getValue('mod')."&limit=".$opts->getValue('limit')."}} Next Page &rsaquo;] [{{fullurl:{{FULLPAGENAME}}|page={$lPage}&start=".$opts->getValue('start')."&mod=".$opts->getValue('mod')."&limit=".$opts->getValue('limit')."}} Last Page &raquo;]";
			}
		}
		$pageSelection = "<div style=\"text-align:center;\" class=\"plainlinks\">$prevPage | $nextPage</div>";

		$out->addHtml($this->buildForm($opts));
		$out->addWikitext('Displaying entries from #'.$opts->getValue('from').' to #'.$maxId.'.'." $pageSelection\n");
		$out->addWikitext($table);

		// Add modules
		$out->addModules('ext.oredict.list');
	}

	public function buildForm(FormOptions $opts) {
		global $wgScript;
		$optionTags = "";
		foreach ([20,50,100,250,500,5000] as $lim) {
			if ($opts->getValue('limit') == $lim) {
				$optionTags .= "<option selected=\"\" value=\"$lim\">$lim</option>";
			} else {
				$optionTags .= "<option value=\"$lim\">$lim</option>";
			}
		}

		$form = "<table>";
		$form .= OreDictForm::createFormRow('list', 'from', $opts->getValue('from'), "number", "min=\"1\"");
		$form .= OreDictForm::createFormRow('list', 'start', $opts->getValue('start'));
		$form .= OreDictForm::createFormRow('list', 'tag', $opts->getValue('tag'));
		$form .= OreDictForm::createFormRow('list', 'mod', $opts->getValue('mod'));
		$form .= '<tr><td style="text-align:right"><label for="limit">'.$this->msg('oredict-list-limit').'</td><td><select name="limit">'.$optionTags.'</select></td></tr>';
		$form .= OreDictForm::createSubmitButton('list');
		$form .= "</table>";

		$out = Xml::openElement('form', array('method' => 'get', 'action' => $wgScript, 'id' => 'ext-oredict-list-filter')) .
			Xml::fieldset($this->msg('oredict-list-legend')->text()) .
			Html::hidden('title', $this->getTitle()->getPrefixedText()) .
			$form .
			Xml::closeElement('fieldset') . Xml::closeElement('form') . "\n";

		return $out;
	}
}
