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
 * JSON renderer. Supports access policy management
 * @package Carcass\Application
 */
class Web_Renderer_Json extends Web_Renderer_Base {

    const CROSS_ORIGIN_POLICY_KEY = 'cross_site_policy';
    const ACCESS_POLICY_HEADER_PREFIX = 'Allow-Origin-';

    protected static $access_policy_headers = [
        'Allow-Origin'  => true,
        'Expose-Headers' => true,
        'Max-Age' => true,
        'Allow-Credentials' => true,
        'Allow-Methods' => true,
        'Allow-Headers' => true,
    ];

    protected $access_policy = [];

    protected $content_type = 'application/json';

    /**
     * @param string $origin origin value
     * @param array $methods array of allowed methods
     * @return $this
     */
    public function allowOrigin($origin, $methods = ['GET', 'POST']) {
        $this->access_policy['Allow-Origin'] = $origin;
        $this->access_policy['Allow-Methods'] = array_map('strtoupper', $methods);
        return $this;
    }

    /**
     * @param array $policy array of policy header key => value
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setAccessPolicy(array $policy) {
        foreach ($policy as $key => $value) {
            if (!isset(self::$access_policy_headers[$key])) {
                throw new \InvalidArgumentException("Unknown policy key: '$key'. Known keys: " . join(', ', self::$access_policy_headers));
            }
        }
        $this->access_policy = $policy;
        return $this;
    }

    protected function sendHeaders(Web_Response $Response) {
        parent::sendHeaders($Response);
        foreach ($this->access_policy as $key => $value) {
            $Response->sendHeader(self::ACCESS_POLICY_HEADER_PREFIX . $key, is_array($value) ? join(',', $value) : $value);
        }
    }

    protected function doRender(array $render_data) {
        if (!empty($render_data[self::CROSS_ORIGIN_POLICY_KEY])) {
            if (is_array($render_data[self::CROSS_ORIGIN_POLICY_KEY])) {
                $this->setAccessPolicy($render_data[self::CROSS_ORIGIN_POLICY_KEY]);
            } else {
                $this->allowOrigin((string)$render_data[self::CROSS_ORIGIN_POLICY_KEY]);
            }
        }
        unset($render_data[self::CROSS_ORIGIN_POLICY_KEY]);
        return Corelib\ArrayTools::jsonEncode($render_data);
    }

    protected function displayErrorBodyTo(Web_Response $Response) {
        $Response->write('{"error":' . intval($this->status) . '}');
    }

}
