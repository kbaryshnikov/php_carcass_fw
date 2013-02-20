<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * StringTemplate implementation based on the Blitz extension.
 *
 * @method void load(string $string)
 * @method void clean()
 * @method void cleanGlobals()
 * @method void set(array $set)
 * @method void setGlobals(array $globals)
 * @method string parse(array $set = null)
 * @method void display(array $set = null)
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
