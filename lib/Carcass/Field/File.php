<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Field;

/**
 * File upload field
 * @package Carcass\Field
 */
class File extends Base {

    protected $upload_struct;

    protected $mime_type = null;

    /**
     * @param $value
     * @return bool
     */
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

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value) {
        $this->upload_struct = null;
        $this->mime_type = null;
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

    /**
     * @param null $key
     * @return null
     */
    public function getUploadedFileData($key = null) {
        return $key === null
            ? $this->upload_struct
            : ( isset($this->upload_struct[$key]) ? $this->upload_struct[$key] : null );
    }

    /**
     * @return array
     */
    public function exportArray() {
        $set = parent::exportArray();
        $set['value'] = '';

        return $set;
    }

    public function getMimeType() {
        if (null === $this->mime_type) {
            $this->mime_type = $this->detectMimeType();
        }
        return $this->mime_type;
    }

    protected function detectMimeType() {
        if ($this->value === self::INVALID_VALUE || null === $this->value) {
            return null;
        }
        $finfo = finfo_open(FILEINFO_MIME);
        try {
            $mime = finfo_file($finfo, $this->value);
            $mime = $mime ? trim(strtok($mime, ';')) : null;
        } catch (\Exception $e) {
            $mime = null;
        }
        finfo_close($finfo);
        return $mime;
    }

}
