<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Config;

use Carcass\Corelib;

/**
 * Configuration item interface. Recursive configuration datasource.
 * @package Carcass\Config
 */
interface ItemInterface extends Corelib\DatasourceInterface {

    /**
     * @param string $path dot-separated
     * @param mixed $default_value
     * @return ItemInterface|string|null
     */
    public function getPath($path, $default_value = null);

    /**
     * @param string $path dot-separated
     * @param mixed $default_value
     * @return array
     */
    public function exportArrayFrom($path, $default_value = []);

    /**
     * @param string $path dot-separated
     * @param mixed $default_value
     * @return \Carcass\Corelib\Hash
     */
    public function exportHashFrom($path, $default_value = []);

}
