<?php

use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Rdbms\ILoadBalancer;
use MediaWiki\Permissions\PermissionManager;

class OreDictAddEntryApi extends ApiBase {
	public function __construct($query, $moduleName, private ILoadBalancer $dbLoadBalancer, private PermissionManager $permissionManager) {
		parent::__construct($query, $moduleName, 'od');
	}

	public function getAllowedParams() {
		return array(
			'mod' => array(
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			),
			'tag' => array(
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			),
			'item' => array(
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			),
			'params' => array(
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_DEFAULT => '',
			),
			'token' => null,
		);
	}

	public function needsToken() {
		return 'csrf';
	}

	public function getTokenSalt() {
		return '';
	}

	public function mustBePosted() {
		return true;
	}

	public function isWriteMode() {
		return true;
	}

	public function getExamples() {
		return array(
			'api.php?action=neworedict&odmod=V&odtag=logWood&oditem=Oak Wood Log',
		);
	}

	public function execute() {
		if (!$this->permissionManager->userHasRight($this->getUser(), 'editoredict')) {
			$this->dieWithError('You do not have the permission to add OreDict entries', 'permissiondenied');
		}

		$mod = $this->getParameter('mod');
		$item = $this->getParameter('item');
		$tag = $this->getParameter('tag');
		$params = $this->getParameter('params');

		if (!OreDict::entryExists($item, $tag, $mod, $this->dbLoadBalancer)) {
			$result = OreDict::addEntry($mod, $item, $tag, $this->getUser(), $this->dbLoadBalancer, $params);
			$ret = array('result' => $result);
			$this->getResult()->addValue('edit', 'neworedict', $ret);
		} else {
			$this->dieWithError('Entry already exists', 'entryexists');
		}
	}
}
