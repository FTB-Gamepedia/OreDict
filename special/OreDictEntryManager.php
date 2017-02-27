<?php
/**
 * OreDictEntryManager special page file
 *
 * @file
 * @ingroup Extensions
 * @version 1.0.1
 * @author Jinbobo <paullee05149745@gmail.com>
 * @license
 */

class OreDictEntryManager extends SpecialPage {
	public function __construct() {
		parent::__construct('OreDictEntryManager', 'editoredict');
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
		// Restrict access from unauthorized users
		$this->checkPermissions();

		$out = $this->getOutput();

		// Add modules
		$out->addModules( 'ext.oredict.manager' );

		$this->setHeaders();
		$this->outputHeader();

		$opts = new FormOptions();

		$opts->add( 'entry_id', 0 );
		$opts->add( 'tag_name', '' );
		$opts->add( 'item_name', '' );
		$opts->add( 'mod_name', '' );
		$opts->add( 'grid_params', '' );
		$opts->add( 'update', 0 );

		$opts->fetchValuesFromRequest( $this->getRequest() );

		// Give precedence to subpage syntax
		if ( isset($par)) {
			$opts->setValue( 'entry_id', $par );
		}

		$out->addHtml($this->outputSearchForm());

		if ($opts->getValue('update') == 1 && $opts->getValue('entry_id') == -1) {
			$opts->setValue('entry_id', $this->createEntry($opts));
		}
		if ($opts->getValue('entry_id') === 0) {
			return;
		}
		if ($opts->getValue('update') == 1 && $opts->getValue('entry_id') != -1) {
			// XSRF prevention
			if ( !$this->getUser()->matchEditToken( $this->getRequest()->getVal( 'token' ) ) ) {
				return;
			}

			$this->updateEntry($opts);
		}

		// Load data
		$dbr = wfGetDB(DB_SLAVE);
		$results = $dbr->select('ext_oredict_items','*',array('entry_id' => $opts->getValue('entry_id')));

		if ($results->numRows() == 0 && $opts->getValue('entry_id') != -1 && $opts->getValue('entry_id') != -2) {
			$out->addWikiText(wfMessage('oredict-manager-fail-norows')->text());
			// $out->addHtml($this->outputUpdateForm());
		} else if ($opts->getValue('entry_id') == -2) {
			$out->addWikiText(wfMessage('oredict-manager-fail-insert')->text());
			$out->addHtml($this->outputUpdateForm());
		} else if ($results->numRows() == 1) {
			$out->addHtml($this->outputUpdateForm($results->current()));
		} else {
			$out->addHtml($this->outputUpdateForm());
		}
	}

	private function createEntry(FormOptions $opts) {
		$item = $opts->getValue('item_name');
		$tag = $opts->getValue('tag_name');
		$mod = $opts->getValue('mod_name');
		$params = $opts->getValue('grid_params');

		// Check if exists
		if (OreDict::entryExists($opts->getValue('item_name'), $opts->getValue('tag_name'), $opts->getValue('mod_name'))) {
			return -2;
		}

		return OreDict::addEntry($mod, $item, $tag, $this->getUser(), $params);
	}

	private function updateEntry(FormOptions $opts) {
		$dbw = wfGetDB(DB_MASTER);
		$entryId = intval($opts->getValue('entry_id'));
		$stuff = $dbw->select('ext_oredict_items', '*', array('entry_id' => $entryId));
		$ary = array(
			'tag_name' => $opts->getValue('tag_name'),
			'item_name' => $opts->getValue('item_name'),
			'mod_name' => $opts->getValue('mod_name'),
			'grid_params' => $opts->getValue('grid_params'),
		);
		if ($stuff->numRows() == 0) {
			return;
		}

		OreDict::editEntry($ary, $entryId, $this->getUser());
	}

	private function outputUpdateForm(stdClass $opts = NULL) {
		global $wgScript;
		$vEntryId = is_object($opts) ? $opts->entry_id : -1;
		$vTagName = is_object($opts) ? $opts->tag_name : '';
		$vTagReadonly = is_object($opts) ? "readonly=\"readonly\"" : '';
		$vItemName = is_object($opts) ? $opts->item_name : '';
		$vModName = is_object($opts) ? $opts->mod_name : '';
		$vGridParams = is_object($opts) ? $opts->grid_params : '';
		$msgFieldsetMain = is_object($opts) ? wfMessage('oredict-manager-edit-legend') : wfMessage('oredict-manager-create-legend');
		$msgSubmitValue = is_object($opts) ? wfMessage('oredict-manager-update') : wfMessage('oredict-manager-create');
		$form = "<table>";
		$form .= OreDictForm::createFormRow('manager', 'entry_id', $vEntryId, "text", "readonly=\"readonly\"");
		$form .= OreDictForm::createFormRow('manager', 'tag_name', $vTagName, "text", $vTagReadonly);
		$form .= OreDictForm::createFormRow('manager', 'item_name', $vItemName);
		$form .= OreDictForm::createFormRow('manager', 'mod_name', $vModName);
		$form .= OreDictForm::createFormRow('manager', 'grid_params', $vGridParams);
		$form .= "<input type=\"submit\" value=\"".$msgSubmitValue."\">";
		$form .= "</table>";

		$out = Xml::openElement('form', array('method' => 'get', 'action' => $wgScript, 'id' => 'ext-oredict-manager-form'))
			 . Xml::fieldset($msgFieldsetMain->text())
			 . Html::hidden('title', $this->getTitle()->getPrefixedText())
			 . Html::hidden('token', $this->getUser()->getEditToken())
			 . Html::hidden('update', 1)
			 . $form
			 . Xml::closeElement( 'fieldset' )
			 . Xml::closeElement( 'form' )
			 . "\n";

		return $out;
	}

	private function outputSearchForm() {
		global $wgScript;
		$form = "<table>";
		$form .= OreDictForm::createFormRow('manager-filter', 'entry_id', '', 'number', 'min="1" id="form-entry-id"');
		$form .= "<tr><td></td><td><input type=\"submit\" value=\"".wfMessage("oredict-manager-submit")."\"><input type=\"button\" value=\"Create new entry\" id=\"form-create-new\"></td></tr>";
		$form .= "</table>";

		$out = Xml::openElement('form', array('method' => 'get', 'action' => $wgScript, 'id' => 'ext-oredict-manager-filter')) .
			Xml::fieldset($this->msg('oredict-manager-filter-legend')->text()) .
			Html::hidden('title', $this->getTitle()->getPrefixedText()) .
			$form .
			Xml::closeElement( 'fieldset' ) . Xml::closeElement( 'form' ) . "\n";

		return $out;
	}
}
