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
 * Class FileMimeTypeMatchesRegexp
 * @package Carcass\Rule
 */
class FileMimeTypeMatchesRegexp extends FileMimeTypeBase {

    /**
     * @var string
     */
    protected $ERROR = 'invalid_file_type';
    /**
     * @var array
     */
    protected $regexps;

    /**
     * @param $match_regexp
     */
    public function __construct($match_regexp) {
        $this->regexps = (array)$match_regexp;
    }

    /**
     * @param $mime
     * @return bool
     */
    protected function checkMime($mime) {
        foreach ($this->regexps as $regexp) {
            if (preg_match($regexp, $mime)) {
                return true;
            }
        }
        return false;
    }

}
