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
 * This trait contains the static url building impementation used by all web routers.
 *
 * @package Carcass\Application
 */
trait Web_Router_StaticTrait {

    protected $static_url_prefix = '/';
    protected $static_host = null;
    protected $static_scheme = null;

    /**
     * Configures the builder.
     *
     * @param string $url_prefix
     * @param string|null $host
     * @param string|null $scheme
     * @return $this
     */
    public function setStatic($url_prefix, $host = null, $scheme = null) {
        $this->static_url_prefix = rtrim($url_prefix, '/') . '/';
        $this->static_host = $host;
        $this->static_scheme = $scheme;
        return $this;
    }

    /**
     * Builds an url.
     *
     * @param \Carcass\Corelib\Request $Request
     * @param $url
     * @param string|bool $host string for exact hostname, true to use the default static host, false for no hostname
     * @param string|null $scheme
     * @return string
     */
    public function getStaticUrl(Corelib\Request $Request, $url, $host = false, $scheme = null) {
        $Url = new Corelib\Url($this->static_url_prefix . ltrim($url, '/'));
        $Url->addQueryString(['rev' => (int)$Request->Env->get('revision')]);
        if ($host) {
            if ($host === true) {
                $host = $this->static_host;
            }
            return $Url->getAbsolute($host ?: $Request->Env->HOST, $scheme ?: $Request->Env->get('SCHEME', ''));
        }
        return $Url->getRelative();
    }

}
