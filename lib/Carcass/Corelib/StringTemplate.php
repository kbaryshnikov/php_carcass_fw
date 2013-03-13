<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

if (!extension_loaded('blitz')) {
    // load the Blitz emulator if no blitz extension is loaded
    require_once __DIR__ . '/BlitzLiteEmulator.php';
}

/**
 * StringTemplate implementation based on the Blitz extension.
 *
 * @package Carcass\Corelib
 */
class StringTemplate extends \Blitz {

    /**
     * @param string $file
     * @return $this
     */
    public static function constructFromFile($file) {
        return new static($file);
    }

    /**
     * @param string $string
     * @return $this
     */
    public static function constructFromString($string) {
        /** @var StringTemplate $self  */
        $self = new static;
        $self->load($string);
        return $self;
    }

    /**
     * @param string $string
     * @param array $args
     * @return mixed
     */
    public static function parseString($string, array $args = []) {
        return static::constructFromString($string)->parse($args);
    }

    public function cleanAll() {
        $this->clean();
        $this->cleanGlobals();
    }

}
