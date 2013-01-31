<?php

namespace Carcass\Application;

use Carcass\Corelib;

trait Web_Router_StaticTrait {

    protected $static_url = '/', $static_host = null, $static_scheme = null;

    public function setStatic($url, $host = null, $scheme = null) {
        $this->static_url = rtrim($url, '/') . '/';
        $this->static_host = $host;
        $this->static_scheme = $scheme;
        return $this;
    }

    public function getStaticUrl(Corelib\Request $Request, $url, $host = false, $scheme = null) {
        $Url = new Corelib\Url($this->static_url . ltrim($url, '/'));
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
