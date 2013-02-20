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
 * This trait contains the implementation of session related methods
 * for session-aware controllers.
 * @package Carcass\Application
 */
trait Web_Session_ControllerTrait {

    /**
     * @var \Carcass\Corelib\Request
     */
    protected $Request;
    /**
     * @var \Carcass\Application\Web_Response
     */
    protected $Response;

    /**
     * @return \Carcass\Application\Web_Session
     */
    protected function assembleSession() {
        /**
         * @var \Carcass\Application\Web_Session_StorageInterface $Storage
         */
        $Storage = null;
        $SessionConfig = Injector::getConfigReader()->getPath('web.session');
        if ($SessionConfig && $storage_class = $SessionConfig->getPath('storage.class')) {
            $Storage = Corelib\ObjectTools::construct(
                Corelib\ObjectTools::resolveRelativeClassName($storage_class, __NAMESPACE__ . '\Web_Session_'),
                $SessionConfig->exportArrayFrom('storage.args', [])
            );
        }
        return new Web_Session($this->Request, $this->Response, $Storage);
    }

    /**
     * @return \Carcass\Application\Web_Session
     */
    protected function getSession() {
        if (!$this->Request->has('Session')) {
            $this->Request->set('Session', $this->assembleSession());
        }
        return $this->Request->Session;
    }

}
