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
 * Base mime rule
 *
 * @package Carcass\Rule
 */
abstract class FileMimeTypeBase extends Base {

    protected $ERROR = 'invalid_file_type';

    /**
     * @param \Carcass\Field\FieldInterface $Field
     * @return bool
     */
    protected function validateFieldValue(Field\FieldInterface $Field) {
        if (!$Field instanceof Field\File) {
            return false;
        }
        return $this->validate($Field->getValue());
    }

    /**
     * @param $uploaded_file
     * @return bool
     */
    public function validate($uploaded_file) {
        if (null === $uploaded_file) {
            return true;
        }
        if (Field\Base::INVALID_VALUE === $uploaded_file) {
            return false;
        }
        return $this->checkMime($uploaded_file);
    }

    /**
     * @param $mime
     * @return bool
     */
    abstract protected function checkMime($mime);

}
