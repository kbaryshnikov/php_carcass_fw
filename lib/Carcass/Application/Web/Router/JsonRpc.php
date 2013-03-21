<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Corelib;
use Carcass\Http;

/**
 * Class Web_Router_JsonRpc
 * @package Carcass\Application
 */
class Web_Router_JsonRpc implements Web_Router_Interface {
    use Web_Router_StaticTrait;

    const DEFAULT_API_CLASS_TEMPLATE = '%s';

    /** @var string */
    protected $api_url;
    /** @var string */
    protected $api_class_template;
    /** @var callable|null */
    protected $body_provider = null;

    /**
     * @param string $api_url
     * @param string|null $api_class_template
     */
    public function __construct($api_url, $api_class_template = null) {
        $this->setApiUrl($api_url);
        $this->setApiClassTemplate($api_class_template);
    }

    /**
     * @param string $api_url
     * @return $this
     */
    public function setApiUrl($api_url) {
        $this->api_url = $api_url;
        return $this;
    }

    /**
     * @param string|null $api_class_template
     * @return $this
     */
    public function setApiClassTemplate($api_class_template = null) {
        $this->api_class_template = $api_class_template ? : static::DEFAULT_API_CLASS_TEMPLATE;
        return $this;
    }

    /**
     * @param callable|null $fn
     * @return $this
     */
    public function setRequestBodyProvider(callable $fn = null) {
        $this->body_provider = $fn;
        return $this;
    }

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param $route
     * @param array $args
     * @return string
     */
    public function getUrl(Corelib\Request $Request, $route, array $args = []) {
        return $this->buildUrl($args)->getRelative();
    }

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param $route
     * @param array $args
     * @return string
     */
    public function getAbsoluteUrl(Corelib\Request $Request, $route, array $args = []) {
        return $this->buildUrl($args)->getAbsolute($Request->Env->HOST, $Request->Env->get('SCHEME', 'http'));
    }

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param \Carcass\Application\ControllerInterface $Controller
     * @throws \Carcass\Http\JsonRpc_Exception
     * @return void
     */
    public function route(Corelib\Request $Request, ControllerInterface $Controller) {
        $uri = $Request->Env->get('REQUEST_URI');

        if (0 != strncmp($uri, $this->api_url, strlen($this->api_url))) {
            $Controller->dispatchNotFound("Request URI '$uri' does not belong to API route prefix, '{$this->api_url}'");
            return;
        }

        (new Http\JsonRpc_Server(
            function ($method, Corelib\Hash $Args, Http\JsonRpc_Server $Server) use ($Controller) {
                try {
                    if ($Controller instanceof Web_JsonRpcFrontController) {
                        $Controller->dispatchJsonRpc($Server, $this->jsonRpcMethodToRoute($method), $Args);
                    } else {
                        $Controller->dispatch($this->jsonRpcMethodToRoute($method), $Args);
                    }
                } catch (ImplementationNotFoundException $e) {
                    throw Http\JsonRpc_Exception::constructMethodNotFoundException($method);
                }
            }
        ))->dispatchRequestBody($this->body_provider);
    }

    /**
     * @param string $rpc_method group_some-controller.action-name -> Group_SomeController.ActionName -> format with api class template
     * @return string
     */
    protected function jsonRpcMethodToRoute($rpc_method) {
        $controller = join(
            '',
            array_map(
                'ucfirst',
                array_filter(
                    explode(
                        '-',
                        join(
                            '_',
                            array_filter(
                                array_map(
                                    'ucfirst',
                                    explode('_', strtok($rpc_method, '.'))
                                ),
                                'strlen'
                            )
                        )
                    ),
                    'strlen'
                )
            )
        ) ? : 'Default';

        $action = strtok(null);
        if ($action) {
            $action = join(
                '',
                array_map(
                    'ucfirst',
                    explode('-', $action)
                )
            );
        }

        $fq_name = $action ? "{$controller}.{$action}" : $controller;
        return sprintf($this->api_class_template, $fq_name);
    }

    /**
     * @param array $args
     * @return Corelib\Url
     */
    protected function buildUrl(array $args = []) {
        return new Corelib\Url($this->api_url, [], $args);
    }

}