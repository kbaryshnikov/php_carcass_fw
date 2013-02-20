<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Corelib;

/**
 * Web renderer interface. Implementations are used by page controllers to render results.
 * @package Carcass\Application
 */
interface Web_Renderer_Interface {

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @param \Carcass\Corelib\ExportableInterface $RenderData
     * @return $this
     */
    public function set(Corelib\ExportableInterface $RenderData);

    /**
     * @param bool $force_rerender
     * @return string
     */
    public function render($force_rerender = false);

    /**
     * @param Web_Response $Response
     * @return $this
     */
    public function displayTo(Web_Response $Response);

}
