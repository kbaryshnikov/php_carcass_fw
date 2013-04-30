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
 * @package Carcass\Rule
 */
class FileMimeTypeNotIn extends FileMimeTypeBase {

    /**
     * @var string
     */
    protected $ERROR = 'disallowed_file_type';

    /**
     * @var array
     */
    protected $blacklisted_types;

    /**
     * @param $blacklisted_types
     */
    public function __construct($blacklisted_types) {
        $this->blacklisted_types = (array)$blacklisted_types;
    }

    /**
     * @param $mime
     * @return bool
     */
    protected function checkMime($mime) {
        foreach ($this->blacklisted_types as $blacklisted_mime) {
            if (0 == strncasecmp($mime, $blacklisted_mime, strlen($blacklisted_mime))) {
                return false;
            }
        }
        return true;
    }

}
