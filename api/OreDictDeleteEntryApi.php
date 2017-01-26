<?php

class OreDictDeleteEntryApi extends ApiBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'od');
    }

    public function getAllowedParams() {
        return array(
            'ids' => array(
                ApiBase::PARAM_TYPE => 'integer',
                ApiBase::PARAM_ISMULTI => true,
                ApiBase::PARAM_ALLOW_DUPLICATES => false,
                ApiBase::PARAM_MIN => 1,
                ApiBase::PARAM_REQUIRED => true,
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
            'api.php?action=deleteoredict&odids=1|2|3',
        );
    }

    public function execute() {
        if (!in_array('editoredict', $this->getUser()->getRights())) {
            $this->dieUsage('You do not have the permission to add OreDict entries', 'permissiondenied');
        }
        $entryIds = $this->getParameter('ids');
        $ret = array();

        foreach ($entryIds as $id) {
            if (OreDict::checkExistsByID($id)) {
                $result = OreDict::deleteEntry($id, $this->getUser());
                $ret[$id] = $result;
            } else {
                $ret[$id] = false;
            }
        }

        $this->getResult()->addValue('edit', 'deleteoredict', $ret);
    }
}