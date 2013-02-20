<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * ResultInterface implementations are intended to collect action results and to be returned
 * from actions for rendering into templates or otherwise parsed and sent to Response.
 *
 * @package Carcass\Corelib
 */
interface ResultInterface extends ExportableInterface {

    /**
     * @param mixed $value
     * @return $this
     */
    public function assign($value);

    /**
     * @param RenderableInterface $Object
     * @return $this
     */
    public function bind(RenderableInterface $Object);

    /**
     * @param ResponseInterface $Response
     * @return $this
     */
    public function displayTo(ResponseInterface $Response);

}
