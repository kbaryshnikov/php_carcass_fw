<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

use Carcass\Corelib\Punycode;

/**
 * Class IsAbsoluteUrl
 * @package Carcass\Rule
 */
class IsAbsoluteUrl extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'invalid_url';

    protected $allow_utf8_idn = false;

    protected $IdnDecoder = null;

    public function __construct($allow_utf8_idn = false) {
        $this->allow_utf8_idn = (bool)$allow_utf8_idn;
    }

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return (
            null === $value
            || $this->matchesRegexp($value)
            || ($this->allow_utf8_idn && $this->matchesRegexp($this->decodeIdn($value)))
        );
    }

    protected function matchesRegexp($value) {
        return preg_match(
            '[^((https?):\/\/)?(([a-z]|xn--)[a-z0-9\-]*\.)*([a-z]|xn--)([a-z0-9\-]+)(\/([\w\d_\-\.~+\']|%[0-9a-f]{2})*)*(\?([\w\d+_\-\.=\&:;,]|%[0-9a-f])*)?(#.*)?$]iu',
            $value
        );
    }

    protected function decodeIdn($value) {
        if (null === $this->IdnDecoder) {
            $this->IdnDecoder = new Punycode();
        }
        return $this->IdnDecoder->encodeUrl($value);
    }

}
