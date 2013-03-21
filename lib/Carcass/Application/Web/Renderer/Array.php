<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;
use Carcass\Corelib;

class Web_Renderer_Array extends Web_Renderer_Base implements Web_Renderer_Interface, Corelib\ExportableInterface {

    protected $rendered_array = null;

    public function exportArray() {
        if (null === $this->rendered_array) {
            $this->rendered_array = $this->RenderData->exportArray();
        }
        return $this->rendered_array;
    }

    /**
     * @param array $render_data
     * @return string
     */
    protected function doRender(array $render_data) {
        exit("!!!!!!!!!!!");
        $this->rendered_array = $render_data;
        return print_r($render_data, true);
    }
}