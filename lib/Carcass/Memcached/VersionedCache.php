<?php

namespace Carcass\Memcached;

class VersionedCache {

    const 
        IDX_DATA_KEY     = 0,
        IDX_VERSION_KEY  = 1,
        SEPARATOR       = '::',
        VERSION_SUFFIX  = ':version';
    
    protected
        $Connection,
        $namespace,
        $key_opts,
        $version_key,
        $last_version = null;

    public function __construct(Connection $Connection) {
        $this->Connection = $Connection;
        $this->setNamespace('');
    }

    public function setNamespace($namespace) {
        $this->namespace   = $namespace;
        $this->key_opts    = ['prefix' => $namespace . self::SEPARATOR];
        $this->version_key = $namespace . self::VERSION_SUFFIX;
        return $this;
    }

    public function setNamespaceByKey(\Closure $NsKey, array $args = []) {
        return $this->setNamespace($NsKey($args));
    }

    /**
     * Get value with version check
     * 
     * @param Closure $Key      key template function
     * @param array $args       key template arguments
     * @return mixed            false if not found or expired
     */
    public function get(\Closure $Key, array $args = []) {
        $data_key    = $Key($args, $this->key_opts);
        $mc_result   = $this->Connection->get([$data_key, $this->version_key]);

        $version = !empty($mc_result[$this->version_key]) ? $mc_result[$this->version_key] : false;

        if (false === $version || empty($mc_result[$data_key])) {
            return false;
        }

        $data_arr = !empty($mc_result[$data_key]) ? $mc_result[$data_key] : false;

        return $this->getValueWithVersionCheck($data_arr, $version);
    }

    /**
     * Get values with version check
     * 
     * @param array $get_keys array of ( Closure $Key [, array $args] ), identical to get() arguments
     * @param bool $truncate_namespace_prefix_from_result_keys  default true
     * @return array of ( key => value ), missing or expired keys are absent; namespace prefix is not included 
     *               unless false===$truncate_namespace_prefix_from_result_keys 
     */
    public function getMulti(array $get_keys, $truncate_namespace_prefix_from_result_keys = true) {
        $keys = [];
        foreach ($get_keys as $key_data) {
            reset($key_data);
            $Key = current($key_data);
            if (!$Key instanceof \Closure) {
                throw new \InvalidArgumentException("First elements of \$get_keys array items must be key functions");
            }
            $keys[] = $Key((array)(next($key_data) ?: []), $this->key_opts);
        }
        $keys[]      = $this->version_key;
        $mc_result   = $this->Connection->get($keys);
        
        if (empty($mc_result)) {
            return [];
        }

        $version = !empty($mc_result[$this->version_key]) ? $mc_result[$this->version_key] : false;

        if (false === $version) {
            return [];
        }

        unset($mc_result[$this->version_key]);

        $result = [];
        $ns_prefix_len = $truncate_namespace_prefix_from_result_keys ? ( strlen($this->namespace) + strlen(self::SEPARATOR) ) : 0;
        foreach ($mc_result as $key => $data) {
            if (!empty($data)) {
                $value = $this->getValueWithVersionCheck($data, $version);
                if (false !== $value) {
                    $result[substr($key, $ns_prefix_len)] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * set value; update version
     * 
     * @param Closure $Key     key template function
     * @param array $args      key template args
     * @param mixed $value     value
     * @param int|null $expire      expiration, null for connection default
     * @return self
     */
    public function set(\Closure $Key, array $args = null, $value, $expire = null) {
        $this->last_version = $this->getNextVersionNumber();
        $this->setKey($this->last_version, $Key, $args, $value, $expire);
        return $this;
    }

    /**
     * set multiple values; update version
     * 
     * @param array $keys  array of ( Closure $Key, array $args, mixed $value, int $expire = null)
     *                     items are same as set() arguments
     * @return self
     */
    public function setMulti(array $keys) {
        $this->last_version = $this->getNextVersionNumber();
        foreach ($keys as $item) {
            array_unshift($item, $this->last_version);
            call_user_func_array([$this, 'setKey'], $item);
        }
        return $this;
    }

    /**
     * Deletes version; when a version is deleted all items in the current namespace become expired
     * 
     * @return self
     */
    public function flush() {
        $this->Connection->callRaw('delete', $this->version_key);
        return $this;
    }

    /**
     * Deletes version of specified namespace; all items in that namespace become expired
     * 
     * @param string $namespace
     * @return self
     */
    public function flushNamespace($namespace) {
        (new static($this->Connection))->setNamespace($namespace)->flush();
        return $this;
    }

    /**
     * Deletes version of specified namespace; all items in that namespace become expired
     * 
     * @param Closure $NsKey 
     * @param array $args 
     * @return self
     */
    public function flushNamespaceByKey(\Closure $NsKey, array $args = []) {
        (new static($this->Connection))->setNamespaceByKey($NsKey, $args)->flush();
        return $this;
    }

    public function getLastVersion() {
        return $this->last_version;
    }

    protected function getNextVersionNumber() {
        $version = $this->Connection->callRaw('increment', $this->version_key);
        if (false === $version) {
            $version = $this->generateVersionNumber();
            $this->Connection->callRawRequired('set', $this->version_key, $version, 0, 0);
        }
        return $version;
    }

    protected function setKey($version, \Closure $Key, array $args, $value, $expire = null) {
        $complex_value = [self::IDX_DATA_KEY => $value, self::IDX_VERSION_KEY => $version];
        $key = $Key($args, $this->key_opts);
        if (null === $expire) {
            $this->Connection->set($key, $complex_value);
        } else {
            $this->Connection->set($key, $complex_value, $expire);
        }
    }

    protected function generateVersionNumber() {
        return ((intval(microtime(true) * 1000)) << 16) | (int)mt_rand(0, 0xFFFF);
    }

    protected function getValueWithVersionCheck($data, $version) {
        if (empty($data) || !is_array($data) || empty($data[self::IDX_VERSION_KEY])) {
            return false;
        }

        $key_version = $data[self::IDX_VERSION_KEY];

        if ($version != $key_version) {
            return false;
        }

        return isset($data[self::IDX_DATA_KEY]) ? $data[self::IDX_DATA_KEY] : false;
    }

}
