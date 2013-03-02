<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

use Carcass\Field;

/**
 * Class FileIsUploaded
 * @package Carcass\Rule
 */
class FileIsUploaded extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'not_uploaded';

    /**
     * @param \Carcass\Field\FieldInterface $Field
     * @return bool
     */
    protected function validateFieldValue(Field\FieldInterface $Field) {
        if (!$Field instanceof Field\File) {
            return false;
        }
        return $this->validate(['value' => $Field->getValue(), 'data' => $Field->getUploadedFileData()]);
    }

    /**
     * @param $values
     * @return bool
     */
    public function validate($values) {
        $value = $values['value'];
        $data = $values['data'];
        if (null === $value) {
            return true;
        }
        return file_exists($data['tmp_name']) && empty($data['error']);
    }
}
