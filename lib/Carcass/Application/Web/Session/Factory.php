<?php

namespace Carcass\Application;

use Carcass\Corelib;

class Web_Session_Factory {

    const DEFAULT_STORAGE = 'filesystem';

    public static function assembleByConfig(Corelib\Hash $Config, Corelib\Request $Request, Web_Response $Response) {
        $Storage = static::assembleStorageByConfig($Config);
        $Session = new Web_Session($Request, $Response, $Storage);
        if ($Config->has('Cookie')) {
            static::setupCookieSettings($Session, $Config->Cookie);
        }
        return $Session;
    }

    protected static function setupCookieSettings($Session, $Config) {
        if (is_string($Config)) {
            $Session->setCookieName($Config);
            return;
        }
        if ($Config->has('name')) {
            $Session->setCookieName($Config->name);
        }
        if ($Config->has('expire')) {
            $Session->setCookieExpire($Config->expire);
        }
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
            return $type(Injector::getInstance(), $Args);
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
        $MemcachedConnection = new \Carcass\Memcached\Connection(new \Carcass\Connection\Dsn($Args->get('dsn')));
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
