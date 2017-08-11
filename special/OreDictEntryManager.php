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
		$out->enableOOUI();

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
		$opts->add('delete', 0);

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
		if (($opts->getValue('update') == 1 || $opts->getValue('delete') == 1) && $opts->getValue('entry_id') != -1) {
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

		// Try to delete before doing any other processing
		if ($opts->getValue('delete') == 1) {
			OreDict::deleteEntry($entryId, $this->getUser());
			return;
		}

		OreDict::editEntry($ary, $entryId, $this->getUser());
	}

	private function outputUpdateForm(stdClass $opts = NULL) {
		global $wgScript;
		$vEntryId = is_object($opts) ? $opts->entry_id : -1;
		$vTagName = is_object($opts) ? $opts->tag_name : '';
		$vTagReadonly = is_object($opts);
		$vItemName = is_object($opts) ? $opts->item_name : '';
		$vModName = is_object($opts) ? $opts->mod_name : '';
		$vGridParams = is_object($opts) ? $opts->grid_params : '';
		$msgFieldsetMain = is_object($opts) ? $this->msg('oredict-manager-edit-legend') : $this->msg('oredict-manager-create-legend');
		$msgSubmitValue = is_object($opts) ? $this->msg('oredict-manager-update') : $this->msg('oredict-manager-create');

		$fieldset = new OOUI\FieldsetLayout([
			'label' => $msgFieldsetMain->text(),
			'items' => [
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'type' => 'number',
						'name' => 'entry_id',
						'value' => $vEntryId,
						'readOnly' => true
					]),
					[
						'label' => $this->msg('oredict-manager-entry_id')->text()
					]
				),
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'name' => 'tag_name',
						'value' => $vTagName,
						'readOnly' => $vTagReadonly
					]),
					[
						'label' => $this->msg('oredict-manager-tag_name')->text()
					]
				),
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'name' => 'item_name',
						'value' => $vItemName
					]),
					[
						'label' => $this->msg('oredict-manager-item_name')->text()
					]
				),
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'name' => 'mod_name',
						'value' => $vModName
					]),
					[
						'label' => $this->msg('oredict-manager-mod_name')->text()
					]
				),
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'name' => 'grid_params',
						'value' => $vGridParams
					]),
					[
						'label' => $this->msg('oredict-manager-grid_params')->text()
					]
				),
				new OOUI\HorizontalLayout([
					'items' => [
						new OOUI\ButtonInputWidget([
							'type' => 'submit',
							'label' => $msgSubmitValue->text(),
							'flags' => ['primary', 'progressive'],
							'name' => 'update',
							'value' => 1
						]),
						new OOUI\ButtonInputWidget([
							'type' => 'submit',
							'label' => $this->msg('oredict-manager-entry_del')->text(),
							'flags' => ['destructive'],
							'icon' => 'remove',
							'name' => 'delete',
							'value' => 1,
							'disabled' => $vEntryId == -1
						])
					]
				])
			]
		]);
		$form = new OOUI\FormLayout([
			'method' => 'GET',
			'action' => $wgScript,
			'id' => 'ext-oredict-manager-form'
		]);

		$form->appendContent(
			$fieldset,
			new OOUI\HtmlSnippet(
				Html::hidden('title', $this->getTitle()->getPrefixedText()) .
				Html::hidden('token', $this->getUser()->getEditToken())
			)
		);

		return new OOUI\PanelLayout([
			'classes' => ['entry-manager-wrapper'],
			'framed' => true,
			'expanded' => false,
			'padded' => true,
			'content' => $form
		]);
	}

	private function outputSearchForm() {
		global $wgScript;
		$fieldset = new OOUI\FieldsetLayout([
			'label' => $this->msg('oredict-manager-filter-legend')->text(),
			'items' => [
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'type' => 'number',
						'name' => 'entry_id',
						'value' => '',
						'min' => '1',
						'id' => 'form-entry-id',
						'icon' => 'search'
					]),
					[
						'label' => $this->msg('oredict-manager-filter-entry_id')->text()
					]
				),
				new OOUI\HorizontalLayout([
					'items' => [
						new OOUI\ButtonInputWidget([
							'label' => $this->msg('oredict-manager-submit')->text(),
							'type' => 'submit'
						]),
						new OOUI\ButtonInputWidget([
							'id' => 'form-create-new',
							'label' => $this->msg('oredict-manager-create-legend')->text(),
							'name' => 'entry_id',
							'value' => -1,
							'type' => 'submit'
						])
					]
				])
			]
		]);
		$form = new OOUI\FormLayout([
			'method' => 'GET',
			'action' => $wgScript,
			'id' => 'ext-oredict-manager-filter'
		]);
		$form->appendContent(
			$fieldset,
			new OOUI\HtmlSnippet(Html::hidden('title', $this->getTitle()->getPrefixedText()))
		);

		return new OOUI\PanelLayout([
			'classes' => ['entry-manager-filter-wrapper'],
			'framed' => true,
			'expanded' => false,
			'padded' => true,
			'content' => $form
		]);
	}
}
