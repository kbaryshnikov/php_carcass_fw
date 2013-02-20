<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * ResponseInterface
 * @package Carcass\Corelib
 */
interface ResponseInterface {

    /**
     * @return $this
     */
    public function begin();

    /**
     * @return $this
     */
    public function commit();

    /**
     * @return $this
     */
    public function rollback();

    /**
     * @param string $string
     * @return $this
     */
    public function write($string);

    /**
     * @param string $string
     * @return $this
     */
    public function writeLn($string);

    /**
     * @param string $string
     * @return $this
     */
    public function writeError($string);

    /**
     * @param string $string
     * @return $this
     */
    public function writeErrorLn($string);

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return int
     */
    public function getStatus();

}
