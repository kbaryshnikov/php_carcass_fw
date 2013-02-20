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
 * Class FileIsNotLonger
 * @package Carcass\Rule
 */
class FileIsNotLonger extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'size_too_large';
    /**
     * @var int
     */
    protected $max_size;

    /**
     * @param int $max_size
     */
    public function __construct($max_size) {
        $this->max_size = $max_size;
    }

    /**
     * @param \Carcass\Field\FieldInterface $Field
     * @return bool
     */
    protected function validateFieldValue(Field\FieldInterface $Field) {
        if (!$Field instanceof Field\File) {
            return false;
        }
        return $this->validate($Field->getUploadedFileData()['size']);
    }

    /**
     * @param int $size
     * @return bool
     */
    public function validate($size) {
        return ($size <= $this->max_size);
    }
}
