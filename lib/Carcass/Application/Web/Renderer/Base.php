<?php

namespace Carcass\Application;

use Carcass\Corelib;

abstract class Web_Renderer_Base implements Web_Renderer_Interface {

    protected $status = 200;
    protected $RenderData = null;
    protected $render_result = null;
    protected $content_type = 'text/html';
    protected $content_charset = 'utf-8';

    public function setStatus($status) {
        $this->status = (int)$status;
        return $this;
    }

    public function set(Corelib\ExportableInterface $RenderData) {
        $this->RenderData = $RenderData;
        return $this;
    }

    public function setContentType($mime, $charset = null) {
        $this->content_type = $mime;
        if (null !== $charset) {
            $this->content_charset = $charset;
        }
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
        $body = $this->render();
        if (strlen($body)) {
            $Response->write($body);
        } else {
            if ($this->status >= 400) {
                $this->displayErrorBodyTo($Response);
            }
        }
        return $this;
    }

    protected function sendHeaders(Web_Response $Response) {
        $Response->setStatus($this->status);
        $Response->sendHeader('Content-Type', $this->content_type . '; charset=' . $this->content_charset);
    }

    /**
     * @return string
     */
    abstract protected function doRender(array $render_data);

    protected function displayErrorBodyTo(Web_Response $Response) {
        $Response->writeHttpError($this->status);
    }

}
