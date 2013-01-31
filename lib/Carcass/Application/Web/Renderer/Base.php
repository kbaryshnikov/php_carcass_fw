<?php

namespace Carcass\Application;

use Carcass\Corelib;

abstract class Web_Renderer_Base {

    protected $status = 200;
    protected $RenderData = null;
    protected $render_result = null;

    public function setStatus($status) {
        $this->status = (int)$status;
        return $this;
    }

    public function set(Corelib\ExportableInterface $RenderData) {
        $this->RenderData = $RenderData;
        return $this;
    }

    public function render($force_rerender = false) {
        if (null === $this->render_result || $force_rerender) {
            $this->render_result = $this->doRender($this->RenderData ? $this->RenderData->exportArray() : array());
        }
        return $this->render_result;
    }

    public function displayTo(Web_Response $Response) {
        $this->sendHeaders($Response);
        $Response->write($this->render());
        return $this;
    }

    protected function sendHeaders(Web_Response $Response) {
        $Response->setStatus($this->status);
    }

    /**
     * @return string
     */
    abstract protected function doRender(array $render_data);

}
