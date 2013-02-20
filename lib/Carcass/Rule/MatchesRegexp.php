<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class MatchesRegexp
 * @package Carcass\Rule
 */
class MatchesRegexp extends Base {

    /**
     * @var string
     */
    protected $regexp;
    /**
     * @var string
     */
    protected $ERROR = 'does_not_match_regexp';

    /**
     * @param string $regexp
     */
    public function __construct($regexp) {
        $this->regexp = $regexp;
    }

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return (null === $value || preg_match($this->regexp, $value));
    }
}
