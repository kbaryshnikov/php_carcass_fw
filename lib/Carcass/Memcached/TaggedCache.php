<?php

namespace Carcass\Memcached;

use Carcass\Corelib;

class TaggedCache {

    const
        TAG_IMPORTANT = 0,
        TAG_UNIMPORTANT = 1,
        SUBKEY_TAGS = 't',
        SUBKEY_DATA = 'd';

    protected
        $tags = [self::TAG_IMPORTANT => [], self::TAG_UNIMPORTANT => []],
        $tag_keys = null,
        $expiration = null,
        $Connection;

    public function __construct(Connection $Connection, array $tags = null) {
        $this->setConnection($Connection);
        $tags and $this->setTags($tags);
    }

    public function getConnection() {
        return $this->Connection;
    }

    public function setConnection(Connection $Connection) {
        $this->Connection = $Connection;
        return $this;
    }

    /**
     * setTags 
     *
     * sets tag keys for current context.
     * The first tag is "important": if it was flushed, the key is considered expired.
     * Second and later tags are "non-important": the key itself does not get expired if tags are flushed, but
     * flush is applied to all tags.
     * If several tags are important, the first item should be an array.
     * 
     * @param array $tags 
     * @return self
     */
    public function setTags(array $tags) {
        $important_tags = array_shift($tags);
        if (empty($important_tags)) {
            $important_tags = [];
        } elseif (!is_array($important_tags)) {
            $important_tags = [$important_tags];
        }
        $this->tags = [
            self::TAG_IMPORTANT     => $important_tags,
            self::TAG_UNIMPORTANT   => $tags,
        ];
        $this->tag_keys = null;
        return $this;
    }

    /**
     * expireIn 
     *
     * Sets expiration time for all keys
     * 
     * @param int $seconds expiration time, null for default
     * @return self
     */
    public function expireIn($seconds) {
        $this->expiration = intval($seconds) ?: null;
        return $this;
    }

    public function get($key) {
        $result = $this->dispatchGetCommand([$key]);
        return isset($result, $result[$key]) ? $result[$key] : false;
    }

    public function getMulti(array $keys) {
        return $this->dispatchGetCommand($keys) ?: [];
    }

    public function set($key, $value) {
        return $this->dispatchSetCommand([$key => $value]);
    }

    public function setMulti(array $kwargs) {
        return $this->dispatchSetCommand($kwargs);
    }

    public function getKey(\Closure $Key, array $args = []) {
        return $this->get($Key($args));
    }

    public function getKeys(array $Keys) {
        return $this->getMulti($this->parseKeys($Keys));
    }

    public function setKey(\Closure $Key, array $args = [], $value) {
        return $this->set($Key($args), $value);
    }

    public function setKeys(array $Keys) {
        return $this->setMulti($this->parseKeys($Keys));
    }

    /**
     * flush 
     * 
     * @param array $tags to flush, or null to flush all tags
     * @return self
     */
    public function flush(array $tags = null) {
        foreach ($this->getAllTagKeys() as $tag_name => $tag_key) {
            if ($tags === null || in_array($tag_name, $tags)) {
                $this->Connection->delete($tag_key);
            }
        }
        return $this;
    }

    protected function dispatchGetCommand(array $keys) {
        $fetch_keys = array_values(array_merge($keys, $tag_keys = $this->getImportantTagKeys()));

        $mc_result = $this->Connection->get($fetch_keys);

        if (empty($mc_result)) {
            return false;
        }

        $tag_values = [];
        foreach ($tag_keys as $tag_name => $tag_key) {
            $tag_value = empty($mc_result[$tag_key]) ? null : $mc_result[$tag_key];
            if (null === $tag_value) {
                return false;
            }
            $tag_values[$tag_name] = $tag_value;
        }

        $result = [];
        foreach ($keys as $key) {
            if (empty($mc_result[$key])) {
                continue;
            }
            $mc_item = $mc_result[$key];
            if (!is_array($mc_item)) {
                Corelib\Injector::getLogger()->logEvent('Notice', "Malformed data in cache: '$key' is not an array");
                continue;
            }
            foreach ([self::SUBKEY_TAGS, self::SUBKEY_DATA] as $k) {
                if (!isset($mc_item[$k])) {
                    Corelib\Injector::getLogger()->logEvent('Notice', "Malformed data in cache: '$key'[$k] is undefined");
                    continue 2;
                }
            }
            foreach ($tag_values as $tag_name => $tag_value) {
                if (empty($mc_item[self::SUBKEY_TAGS][$tag_name]) || $mc_item[self::SUBKEY_TAGS][$tag_name] != $tag_value) {
                    continue 2;
                }
            }
            $result[$key] = $mc_item[self::SUBKEY_DATA];
        }

        return $result;
    }

    protected function dispatchSetCommand(array $kwargs) {
        if (empty($kwargs)) {
            throw new LogicException("kwargs must not be empty");
        }

        $items = [];
        $tags_data = [];

        $this->buildTagKeys();

        foreach ([self::TAG_IMPORTANT, self::TAG_UNIMPORTANT] as $importance) {
            foreach ($this->tag_keys[$importance] as $tag_name => $tag_key) {
                $tag_value = $this->generateTagValue();
                if ($importance == self::TAG_IMPORTANT) {
                    $tags_data[$tag_name] = $tag_value;
                }
                $items[$tag_key] = $tag_value;
            }
        }

        foreach ($kwargs as $key => $value) {
            $items[$key] = [
                self::SUBKEY_DATA   => $value,
                self::SUBKEY_TAGS   => $tags_data,
            ];
        }

        foreach ($items as $key => $value) {
            $this->Connection->set($key, $value, null, $this->expiration);
        }
    }

    protected function getImportantTagKeys() {
        $this->buildTagKeys();
        return $this->tag_keys[self::TAG_IMPORTANT];
    }

    protected function getAllTagKeys() {
        $this->buildTagKeys();
        return array_merge($this->tag_keys[self::TAG_IMPORTANT], $this->tag_keys[self::TAG_UNIMPORTANT]);
    }

    protected function buildTagKeys() {
        if (null === $this->tag_keys) {
            $this->tag_keys = [];
            foreach ([self::TAG_IMPORTANT, self::TAG_UNIMPORTANT] as $importance) {
                if (empty($this->tags[$importance])) {
                    $this->tag_keys[$importance] = [];
                } else {
                    foreach ($this->tags[$importance] as $tag) {
                        $this->tag_keys[$importance][$tag] = '&' . $tag;
                    }
                }
            }
        }
    }

    protected function generateTagValue() {
        return Corelib\Crypter::getRandomNumber(0, PHP_INT_MAX) . '.' .  microtime(true);
    }

    protected function parseKeys(array $Keys) {
        $keys = [];
        foreach ($Keys as $key_data) {
            $Key = reset($key_data);
            if (!$Key instanceof \Closure) {
                throw new \InvalidArgumentException("first item of Keys array must be a key template function");
            }
            $keys[] = $Key((array)(next($key_data) ?: []));
        }
        return $keys;
    }

}
