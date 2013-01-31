<?php

namespace Carcass\Application;

use Carcass\Corelib;

trait Web_Session_ControllerTrait {

    protected function assembleSession() {
        $SessionConfig = Injector::getConfigReader()->getPath('web.session');
        if ($SessionConfig && $storage_class = $SessionConfig->getPath('storage.class')) {
            $Storage = Corelib\ObjectTools::construct(
                Corelib\ObjectTools::resolveRelativeClassName($storage_class, __NAMESPACE__ . '\Web_Session_'),
                $SessionConfig->exportArrayFrom('storage.args', [])
            );
        } else {
            $Storage = null;
        }
        $this->Request->Session = new Web_Session(
            $this->Request,
            $this->Response,
            $Storage
        );
    }

    protected function getSession() {
        if (!$this->Request->has('Session')) {
            $this->Request->set('Session', $this->assembleSession());
        }
        return $this->Request->Session;
    }

}
