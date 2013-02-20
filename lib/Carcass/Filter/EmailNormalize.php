<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Filter;

/**
 * EmailNormalize filter - lowercases the email domain
 * @package Carcass\Filter
 */
class EmailNormalize implements FilterInterface {

    /**
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public function filter(&$value) {
        $tokens = explode('@', trim($value));
        if (count($tokens) != 2 || !$tokens[0] || !$tokens[1]) {
            throw new \InvalidArgumentException("Argument does not look like a e-mail address");
        }
        $tokens[1] = strtolower(trim($tokens[1], '.'));
        $value = join('@', $tokens);
    }

}
