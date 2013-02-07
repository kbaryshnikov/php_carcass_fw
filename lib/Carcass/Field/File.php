<?php

namespace Carcass\Field;

class File extends Base {

    protected $upload_struct;

    protected function isValidUploadedFileStructure($value) {
        static $required_fields = [ 'name', 'type', 'size', 'tmp_name' ];
        if (!is_array($value)) {
            return false;
        }
        foreach ($required_fields as $field) {
            if (!isset($value[$field])) {
                return false;
            }
        }
        return true;
    }

    public function setValue($value) {
        $this->upload_struct = null;
        if (empty($value)) {
            $this->value = null;
        } elseif (!$this->isValidUploadedFileStructure($value)) {
            $this->value = self::INVALID_VALUE;
        } else {
            $this->upload_struct = $value;
            $this->value = empty($value['tmp_name']) ? null : $value['tmp_name'];
        }
        return $this;
    }

    public function getUploadedFileData($key = null) {
        return $key === null
            ? $this->upload_struct
            : ( isset($this->upload_struct[$key]) ? $this->upload_struct[$key] : null );
    }

    public function exportArray() {
        $set = parent::exportArray();
        $set['value'] = '';

        return $set;
    }
}