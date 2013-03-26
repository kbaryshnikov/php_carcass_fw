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
 * Web page controller
 *
 * @package Carcass\Application
 */
abstract class Web_PageController extends Controller {

    /** @var \Carcass\Corelib\Result|null */
    protected $Result = null;
    /** @var Web_Response */
    protected $Response;
    /** @var Web_Router_Interface */
    protected $Router;

    /** @var callable[] */
    protected $finalizers = [];

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param Web_Response $Response
     * @param Web_Router_Interface $Router
     */
    public function __construct(Corelib\Request $Request, Web_Response $Response, Web_Router_Interface $Router) {
        parent::__construct($Request, $Response, $Router);
    }

    /**
     * @param string $action
     * @param \Carcass\Corelib\Hash $Args
     * @throws ImplementationNotFoundException
     * @return mixed
     */
    public function dispatch($action, Corelib\Hash $Args) {
        $method = 'action' . $action;
        if (!method_exists($this, $method)) {
            throw new ImplementationNotFoundException("Action not implemented: '$action'");
        }
        $init_result = $this->initBeforeAction();
        if (null !== $init_result) {
            $result = $init_result;
        } else {
            $result = $this->$method($Args);
        }
        $action_result = $this->handleActionResult($result);
        $this->finalizeAfterAction();
        return $action_result;
    }

    protected function initBeforeAction() {
        $this->initResultObject();
        return null;
    }

    protected function finalizeAfterAction() {
        foreach ($this->finalizers as $finalizer) {
            $finalizer();
        }
    }

    protected function addFinalizer($finalizer) {
        if (is_string($finalizer)) {
            $finalizer = [$this, $finalizer];
        }
        if (!is_callable($finalizer)) {
            throw new \InvalidArgumentException('finalizer is not callable');
        }
        $this->finalizers[] = $finalizer;
    }

    /**
     * @param $result
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function handleActionResult($result) {
        if (is_int($result)) {
            if ($result < 400 || $result >= 600) {
                throw new \InvalidArgumentException('error status must be in range [400, 600)');
            }
            return $this->getRenderer()->setStatus($result);
        }
        return $result;
    }

    /**
     * @return Corelib\Result
     */
    protected function getResult() {
        if (null === $this->Result) {
            $this->initResultObject();
        }
        return $this->Result;
    }

    /**
     * @param null $template_file
     * @return Web_Renderer_Interface
     */
    protected function getRenderer($template_file = null) {
        $Renderer = $this->assembleRenderer($template_file);
        $this->Result and $Renderer->set($this->Result);
        return $Renderer;
    }

    /**
     * @param string|null $template_file
     * @return Web_Renderer_Interface
     * @throws \LogicException
     */
    protected function assembleRenderer($template_file = null) {
        /** @var \Carcass\Config\ItemInterface $RendererCfg */
        $RendererCfg = DI::getConfigReader()->getPath('web.renderer');
        if (!$RendererCfg) {
            throw new \LogicException('web.renderer is not defined in configuration');
        }
        $class_name = Corelib\ObjectTools::resolveRelativeClassName($RendererCfg->get('class'), '\Carcass\Application\Web_Renderer_');
        return new $class_name($RendererCfg->exportArrayFrom('args'), $template_file);
    }

    protected function initResultObject() {
        $this->Result = new Corelib\Result;
    }

    protected function redirectToRoute($route, array $args = [], $status = 302) {
        $url = $this->Router->getAbsoluteUrl($this->Request, $route, $args);
        return $this->redirectToUrl($url, $status);
    }

    /**
     * @param $url
     * @param int $status
     * @return Web_Renderer_Redirect
     */
    protected function redirectToUrl($url, $status = 302) {
        return new Web_Renderer_Redirect($url, $status);
    }

    /**
     * @param $location
     * @return Web_Renderer_Sendfile
     */
    protected function sendFile($location) {
        return new Web_Renderer_Sendfile($location);
    }

    protected function getOwnUrl($action, array $args = []) {
        return $this->Router->getPageUrl($this->Request, $this, $action, $args);
    }

    protected function getOwnAbsoluteUrl($action, array $args = []) {
        return $this->Router->getPageAbsoluteUrl($this->Request, $this, $action, $args);
    }

}
