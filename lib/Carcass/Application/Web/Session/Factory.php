<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Corelib;
use Carcass\Memcached;
use Carcass\Connection;

class Web_Session_Factory {

    const DEFAULT_STORAGE = 'filesystem';

    public static function assembleByConfig(Corelib\Hash $Config, Corelib\Request $Request, Web_Response $Response) {
        $Storage = static::assembleStorageByConfig($Config);
        $Session = new Web_Session($Request, $Response, $Storage);
        if ($Config->has('Cookie')) {
            $cookie = $Config->Cookie;
            if (is_string($cookie)) {
                $cookie_name = $cookie;
            } else {
                $cookie_name = $cookie->name;
                $Session->setCookieExpire($cookie->expire);
            }
            $Session->setCookieName($cookie_name);
        }
        return $Session;
    }

    protected static function assembleStorageByConfig(Corelib\Hash $Config) {
        $StorageConfig = $Config->get('storage', new Corelib\Hash([
            'type' => static::DEFAULT_STORAGE,
            'args' => []
        ]));

        if (empty($StorageConfig)) {
            return null;
        }

        $type = $StorageConfig->get('type');
        $Args = $StorageConfig->get('args', new Corelib\Hash);

        if ($type instanceof \Closure) {
            return $type(DI::getInstance(), $Args);
        }

        $method = 'assemble' . $type . 'Storage';
        if (!method_exists(get_called_class(), $method)) {
            throw new \LogicException("No factory method for '$type' session storage exists");
        }
        return static::$method($Args);
    }

    protected static function assembleFilesystemStorage(Corelib\Hash $Args) {
        return new Web_Session_FilesystemStorage($Args->get('directory'), $Args->get('file_template'));
    }

    protected static function assembleMemcachedStorage(Corelib\Hash $Args) {
        if (!$Args->get('dsn')) {
            throw new \LogicException("dsn is not configured for memcached session storage");
        }
        $MemcachedConnection = new Memcached\Connection(Connection\Dsn::factory($Args->get('dsn')));
        if ($Args->get('use_cas')) {
            $Storage = new Web_Session_MemcachedCasStorage($MemcachedConnection);
        } else {
            $Storage = new Web_Session_MemcachedStorage($MemcachedConnection);
        }
        if ($Args->get('key_template')) {
            $Storage->setMcKey($Args->key_template);
        }
        if ($Args->get('expiration')) {
            $Storage->setMcExpiration($Args->expiration);
        }
        return $Storage;
    }

}
