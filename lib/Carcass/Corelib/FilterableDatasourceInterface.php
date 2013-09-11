<?php
/**
 * Carcass Framework
 *
 * @author    Anton Terekhov <anton.a.terekhov@gmail.com>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * FilterableDatasourceInterface
 * @package Carcass\Corelib
 */
interface FilterableDatasourceInterface {

    /**
     * @param array $allowed_fields
     * @return array
     */
    public function exportFilteredArray(array $allowed_fields);

    /**
     * @param array $allowed_fields
     * @return Hash
     */
    public function exportFilteredHash(array $allowed_fields);
}
