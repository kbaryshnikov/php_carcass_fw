<?php

namespace Carcass\Rule;

use Carcass\Field;

class FileMimeTypeMatchesRegexp extends Base {

    protected $ERROR = 'invalid_file_type';
    protected $regexps;

    public function __construct($match_regexp) {
        $this->regexps = (array)$match_regexp;
    }

    protected function validateFieldValue(Field\FieldInterface $Field) {
        return $this->validate(['value' => $Field->getValue(), 'data' => $Field->getUploadedFileData()]);
    }

    public function validate($values) {
        extract($values);
        $filename = $value;
        if (null === $filename) {
            return true;
        }
        if (Field\Base::INVALID_VALUE !== $filename) {
            $got_mime = $data['type'];
            if (!empty($got_mime) && $this->checkMime($got_mime) && is_file($filename)) {
                try {
                    $finfo = finfo_open(FILEINFO_MIME);
                    $mime = finfo_file($finfo, $filename);
                    $mime = trim(strtok($mime, ';'));
                    if ($this->checkMime($mime)) {
                        return true;
                    }
                } catch (\Exception $e) { // got error in finfo_file, or invalid regexp - treat as error, ignore exception details
                    // pass
                }
            }
        }
        return false;
    }

    protected function checkMime($mime) {
        foreach ($this->regexps as $regexp) {
            if (preg_match($regexp, $mime)) {
                return true;
            }
        }
        return false;
    }

}
