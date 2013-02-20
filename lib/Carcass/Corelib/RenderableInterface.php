<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * RenderableInterface
 * @package Carcass\Corelib
 */
interface RenderableInterface {

    /**
     * @param ResultInterface $View
     * @return $this
     */
    public function renderTo(ResultInterface $View);

}
