<?php

namespace Carcass\Rule;

use Carcass\Field;

class FileIsValidImage extends Base {

    protected $ERROR = 'invalid_image_file';
    protected $allowed_formats = null;
    protected $max_dim = null;

    public function __construct(array $allowed_formats = null, $max_dim = null) {
        $this->allowed_formats = $allowed_formats;
        $this->max_dim = intval($max_dim) ?: null;
    }

    protected function validateFieldValue(Field\FieldInterface $Field) {
        return $this->validate(['value' => $Field->getValue(), 'data' => $Field->getUploadedFileData()]);
    }

    public function validate($values) {
        extract($values);
        if (null === $value) {
            return true;
        }
        if (empty($data['tmp_name'])) {
            return false;
        }
        try {
            return $this->isValidImage($data['tmp_name']);
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function isValidImage($filename) {
        $Imagick = new \Imagick;
        $Imagick->readImage($filename);
        $format = $Imagick->getImageFormat();
        return !empty($format)
            && ( $this->allowed_formats === null || in_array(strtolower($format), $this->allowed_formats, true) )
            && ( $this->max_dim === null ||
                    (
                        $Imagick->getImageWidth() <= $this->max_dim
                        && $Imagick->getImageHeight() <= $this->max_dim
                    )
            );
    }

}
