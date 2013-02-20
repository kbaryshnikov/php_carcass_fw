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
 * Base web renderer
 * @package Carcass\Application
 */
abstract class Web_Renderer_Base implements Web_Renderer_Interface {

    /**
     * @var int
     */
    protected $status = 200;
    /**
     * @var Corelib\ExportableInterface|null
     */
    protected $RenderData = null;
    /**
     * @var string|null
     */
    protected $render_result = null;
    /**
     * @var string
     */
    protected $content_type = 'text/html';
    /**
     * @var string
     */
    protected $content_charset = 'utf-8';

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status) {
        $this->status = (int)$status;
        return $this;
    }

    /**
     * @param \Carcass\Corelib\ExportableInterface $RenderData
     * @return $this
     */
    public function set(Corelib\ExportableInterface $RenderData) {
        $this->RenderData = $RenderData;
        return $this;
    }

    /**
     * @param string $mime
     * @param string|null $charset
     * @return $this
     */
    public function setContentType($mime, $charset = null) {
        $this->content_type = $mime;
        if (null !== $charset) {
            $this->content_charset = $charset;
        }
        return $this;
    }

    /**
     * @param bool $force_rerender
     * @return string
     */
    public function render($force_rerender = false) {
        if (null === $this->render_result || $force_rerender) {
            $this->render_result = $this->doRender($this->RenderData ? $this->RenderData->exportArray() : array());
        }
        return $this->render_result;
    }

    /**
     * @param Web_Response $Response
     * @return $this
     */
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
     * @param array $render_data
     * @return string
     */
    abstract protected function doRender(array $render_data);

    protected function displayErrorBodyTo(Web_Response $Response) {
        $Response->writeHttpError($this->status);
    }

}
