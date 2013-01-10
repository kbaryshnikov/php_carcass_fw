<?php

namespace Carcass\Rule;

use Carcass\Field;

class FileIsNotLonger extends Base {

    protected $ERROR = 'size_too_large';
    protected $max_size;
    
    public function __construct($max_size) {
        $this->max_size = $max_size;
    }

    protected function validateFieldValue(Field\FieldInterface $Field) {
        return $this->validate($Field->getUploadedFileData()['size']);
    }

    public function validate($size) {
        return ($size <= $this->max_size);
    }
}
