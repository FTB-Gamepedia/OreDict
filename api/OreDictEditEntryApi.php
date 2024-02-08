<?php

use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;
use Wikimedia\Rdbms\ILoadBalancer;

class OreDictEditEntryApi extends ApiBase {
    public function __construct($query, $moduleName, private ILoadBalancer $dbLoadBalancer, private PermissionManager $permissionManager) {
        parent::__construct($query, $moduleName, 'od');
    }

    public function getAllowedParams() {
        return array(
            'token' => null,
            'mod' => [
            	ParamValidator::PARAM_TYPE => 'string',
            ],
            'tag' => [
            	ParamValidator::PARAM_TYPE => 'string',
            ],
            'item' => [
            	ParamValidator::PARAM_TYPE => 'string',
            ],
            'params' => [
            	ParamValidator::PARAM_TYPE => 'string',
            ],
            'id' => [
            	ParamValidator::PARAM_TYPE => 'integer',
                IntegerDef::PARAM_MIN => 1,
            	ParamValidator::PARAM_REQUIRED => true,
            ],
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
            'api.php?action=editoredict&odmod=NEWMOD&odid=1',
        );
    }

    public function execute() {
        if (!$this->permissionManager->userHasRight($this->getUser(), 'editoredict')) {
            $this->dieWithError('You do not have the permission to add OreDict entries', 'permissiondenied');
        }

        $id = $this->getParameter('id');

        if (!OreDict::checkExistsByID($id, $this->dbLoadBalancer)) {
            $this->dieWithError("Entry $id does not exist", 'entrynotexist');
            return;
        }

        $mod = $this->getParameter('mod');
        $item = $this->getParameter('item');
        $tag = $this->getParameter('tag');
        $params = $this->getParameter('params');

        $update = array(
            'mod_name' => $mod,
            'item_name' => $item,
            'tag_name' => $tag,
            'grid_params' => $params,
        );

        $result = OreDict::editEntry($update, $id, $this->getUser(), $this->dbLoadBalancer);
        $ret = array();
        switch ($result) {
            case 0: {
                $ret = array($id => true);
                break;
            }
            case 1: {
                $this->dieWithError("Failed to edit $id in the database", 'dbfail');
                return;
            }
            case 2: {
                $this->dieWithError("There was no change made for entry $id", 'nodiff');
                return;
            }
        }

        $this->getResult()->addValue('edit', 'editoredict', $ret);
    }
}
