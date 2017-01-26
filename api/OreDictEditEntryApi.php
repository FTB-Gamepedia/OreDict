<?php

class OreDictEditEntryApi extends ApiBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'od');
    }

    public function getAllowedParams() {
        return array(
            'token' => null,
            'mod' => [
                ApiBase::PARAM_TYPE => 'string',
            ],
            'tag' => [
                ApiBase::PARAM_TYPE => 'string',
            ],
            'item' => [
                ApiBase::PARAM_TYPE => 'string',
            ],
            'params' => [
                ApiBase::PARAM_TYPE => 'string',
            ],
            'flags' => [
                ApiBase::PARAM_TYPE => 'integer',
            ],
            'id' => [
                ApiBase::PARAM_TYPE => 'integer',
                ApiBase::PARAM_MIN => 1,
                ApiBase::PARAM_REQUIRED => true,
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
        if (!in_array('editoredict', $this->getUser()->getRights())) {
            $this->dieUsage('You do not have the permission to add OreDict entries', 'permissiondenied');
        }
        
        $id = $this->getParameter('id');

        if (!OreDict::checkExistsByID($id)) {
            $this->dieUsage("Entry $id does not exist", 'entrynotexist');
            return;
        }

        $mod = $this->getParameter('mod');
        $item = $this->getParameter('item');
        $tag = $this->getParameter('tag');
        $params = $this->getParameter('params');
        $flags = $this->getParameter('flags');

        $update = array(
            'mod_name' => $mod,
            'item_name' => $item,
            'tag_name' => $tag,
            'grid_params' => $params,
            'flags' => $flags
        );

        $result = OreDict::editEntry($update, $id, $this->getUser());
        $ret = array();
        switch ($result) {
            case 0: {
                $ret = array($id => true);
                break;
            }
            case 1: {
                $this->dieUsage("Failed to edit $id in the database", 'dbfail');
                return;
            }
            case 2: {
                $this->dieUsage("There was no change made for entry $id", 'nodiff');
                return;
            }
        }

        $this->getResult()->addValue('edit', 'editoredict', $ret);
    }
}