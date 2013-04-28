<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;
use Carcass\Corelib;

class Web_Renderer_Array extends Web_Renderer_Base implements Web_Renderer_Interface, Corelib\ExportableInterface, Corelib\FilterableInterface {

    protected $rendered_result = null;
    protected $render_join_with = "";

    /**
     * @return array
     */
    public function exportArray() {
        if (null === $this->rendered_result) {
            $this->rendered_result = $this->RenderData->exportArray();
        }
        return $this->rendered_result;
    }

    /**
     * @param callable $fn filter function (array) -> array
     * @return $this
     */
    public function filter(callable $fn) {
        $this->rendered_result = $fn($this->exportArray());
        return $this;
    }

    /**
     * @param $string
     * @return $this
     */
    public function onRenderJoinElementsWith($string) {
        $this->render_join_with = $string;
        return $this;
    }

    /**
     * @param array $render_data
     * @return string
     */
    protected function doRender(array $render_data) {
        $this->rendered_result = $render_data;
        return join($this->render_join_with, $render_data);
    }

}