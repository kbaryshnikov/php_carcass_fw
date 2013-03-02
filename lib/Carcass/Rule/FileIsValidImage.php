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
 * Class FileIsValidImage
 * @package Carcass\Rule
 */
class FileIsValidImage extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'invalid_image_file';
    /**
     * @var array|null
     */
    protected $allowed_formats = null;
    /**
     * @var int|null
     */
    protected $max_dim = null;

    /**
     * @param array $allowed_formats
     * @param null $max_dim
     */
    public function __construct(array $allowed_formats = null, $max_dim = null) {
        $this->allowed_formats = $allowed_formats;
        $this->max_dim = intval($max_dim) ?: null;
    }

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
        if (empty($data['tmp_name'])) {
            return false;
        }
        try {
            return $this->isValidImage($data['tmp_name']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $filename
     * @return bool
     */
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
