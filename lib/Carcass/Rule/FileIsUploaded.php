<?php

namespace Carcass\Rule;

use Carcass\Field;

class FileIsUploaded extends Base {

    protected $ERROR = 'not_uploaded';

    protected function validateFieldValue(Field\FieldInterface $Field) {
        return $this->validate(['value' => $Field->getValue(), 'data' => $Field->getUploadedFileData()]);
    }

    public function validate($values) {
        extract($values);
        if (null === $value) {
            return true;
        }
        return file_exists($data['tmp_name']) && empty($data['error']);
    }
}
