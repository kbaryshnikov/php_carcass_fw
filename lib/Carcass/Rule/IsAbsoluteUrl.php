<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class IsAbsoluteUrl
 * @package Carcass\Rule
 */
class IsAbsoluteUrl extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'invalid_url';

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return null === $value || preg_match(
            '[^((https?):\/\/)?([a-z]([a-z0-9\-]*\.)+([a-z]{2,6}))(\/[a-z0-9_\-\.~]*)*(\?[a-z0-9+_\-\.%=\&:;,]*)?(#[a-z0-9_-]*)?$]i', 
            $value
        );
    }
}
