<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Filter;

/**
 * @package Carcass\Filter
 */
class UrlNormalize implements FilterInterface {

    /**
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public function filter(&$value) {
        try {
            $normalizer = new URL\Normalizer($value);
            $value = $normalizer->normalize();
        } catch (\Exception $e) {
            throw new \InvalidArgumentException($e->getMessage());
        }
    }

}
