<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Memcached;

use Carcass\Corelib;

/**
 * Tagged memcached cacher
 * @package Carcass\Memcached
 */
class TaggedCache {

    const
        TAG_NAMESPACE = '&',
        TAG_HARD = 0,
        TAG_SOFT = 1,
        SUBKEY_TAGS = 't',
        SUBKEY_DATA = 'd';

    protected
        $tags = [self::TAG_HARD => [], self::TAG_SOFT => []],
        $key_options = [],
        $expiration = null;

    /**
     * @var Connection
     */
    protected $Connection;

    /**
     * @param Connection $Connection
     * @param array $tags
     * @param array $key_options
     */
    public function __construct(Connection $Connection, array $tags = null, array $key_options = []) {
        $this->setConnection($Connection);
        $tags and $this->setTags($tags, $key_options);
    }

    /**
     * @return Connection
     */
    public function getConnection() {
        return $this->Connection;
    }

    /**
     * @param Connection $Connection
     * @return $this
     */
    public function setConnection(Connection $Connection) {
        $this->Connection = $Connection;
        return $this;
    }

    /**
     * setTags 
     *
     * sets tag keys for current context.
     * The first tag is "hard": if it was flushed, the key is considered expired.
     * Second and later tags are "soft": the key itself does not get expired if tags are flushed
     * (and does not carry versions of soft tags), but flush is applied to all tags including 'soft'.
     * To specify multiple hard tags, use arrag as the first item.
     * 
     * @param array $tags 
     * @param array $key_options
     * @return self
     */
    public function setTags(array $tags, array $key_options = []) {
        $hard_tags = array_shift($tags);
        if (empty($hard_tags)) {
            $hard_tags = [];
        } elseif (!is_array($hard_tags)) {
            $hard_tags = [$hard_tags];
        }
        $this->key_options = $key_options;
        $this->tags = $this->createKeys([
            self::TAG_HARD     => $hard_tags,
            self::TAG_SOFT   => $tags,
        ]);
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

    /**
     * @param $key_template
     * @param array $args
     * @return bool|mixed false on miss
     */
    public function get($key_template, array $args = []) {
        $result = $this->getMulti([$key_template], $args);
        return !empty($result) ? reset($result) : false;
    }

    /**
     * @param array $key_templates
     * @param array $args
     * @return array|bool
     */
    public function getMulti(array $key_templates, array $args = []) {
        return $this->dispatchGet($this->createKeys($key_templates), $args);
    }

    /**
     * @param callable $Key
     * @param array $args
     * @return bool|mixed false on miss
     */
    public function getKey(\Closure $Key, array $args = []) {
        $result = $this->dispatchGet([$Key], $args);
        return !empty($result) ? reset($result) : false;
    }

    /**
     * @param array $Keys
     * @param array $args
     * @return array|bool
     */
    public function getKeys(array $Keys, array $args = []) {
        return $this->dispatchGet($Keys, $args);
    }

    /**
     * @param $key_template
     * @param $value
     * @param array $args
     * @return $this
     */
    public function set($key_template, $value, array $args = []) {
        return $this->setKey($this->createKey($key_template), $value, $args);
    }

    /**
     * @param array $key_template_value_map
     * @param array $args
     * @return $this
     */
    public function setMulti(array $key_template_value_map, array $args) {
        return $this->setKeys(
            $this->createKeys(array_keys($key_template_value_map)),
            array_values($key_template_value_map),
            $args
        );
    }

    /**
     * @param callable $Key
     * @param $value
     * @param array $args
     * @return $this
     */
    public function setKey(\Closure $Key, $value, array $args = []) {
        return $this->setKeys([$Key], [$value], $args);
    }

    /**
     * @param array $Keys
     * @param array $values
     * @param array $args
     * @return $this
     */
    public function setKeys(array $Keys, array $values, array $args = []) {
        $this->dispatchSet(array_values($Keys), array_values($values), $args);
        return $this;
    }

    /**
     * @param array $args
     * @param array $keys
     * @return $this
     */
    public function flush(array $args, array $keys = []) {
        foreach ($this->getAllTagKeys($args, true) as $tag) {
            $this->Connection->delete($tag);
        }
        if ($keys) {
            foreach ($this->buildKeys($this->createKeys($keys), $args) as $Key) {
                $this->Connection->delete($Key);
            }
        }
        return $this;
    }

    /**
     * @param array $Keys
     * @param array $args
     * @return array|bool
     */
    protected function dispatchGet(array $Keys, array $args) {
        $tag_keys  = $this->getHardTagKeys($args);
        $data_keys = $this->buildKeys($Keys, $args);

        $mc_result = $this->Connection->get(array_merge(array_values($tag_keys), array_values($data_keys)));

        if (empty($mc_result)) {
            return false;
        }

        $tag_values = [];
        foreach ($tag_keys as $tag_key) {
            $tag_values[$tag_key] = empty($mc_result[$tag_key]) ? null : $mc_result[$tag_key];
        }

        $result = [];
        foreach ($data_keys as $idx => $key) {
            if (empty($mc_result[$key])) {
                continue;
            }
            $mc_item = $mc_result[$key];
            if (!is_array($mc_item)) {
                Corelib\DIContainer::getLogger()->logEvent('Notice', "Malformed data in cache: '$key' is not an array");
                continue;
            }
            if (!isset($mc_item[self::SUBKEY_TAGS], $mc_item[self::SUBKEY_DATA]) || !is_array($mc_item[self::SUBKEY_TAGS])) {
                Corelib\DIContainer::getLogger()->logEvent('Notice', "Malformed data in cache: '$key' has broken structure");
            }
            $key_tag_values = $mc_item[self::SUBKEY_TAGS];
            foreach ($tag_values as $tag_key => $tag_value) {
                if (empty($key_tag_values[$tag_key]) || $key_tag_values[$tag_key] != $tag_value) {
                    continue 2;
                }
            }
            $result[$idx] = $mc_item[self::SUBKEY_DATA];
        }

        return $result;
    }

    /**
     * @param array $Keys
     * @param array $values
     * @param array $args
     * @throws \LogicException
     */
    protected function dispatchSet(array $Keys, array $values, array $args) {
        if (count($Keys) != count($values)) {
            throw new \LogicException("Keys and values count mismatch");
        }

        $tag_value = $this->Connection->getTransactionId() ?: Corelib\UniqueId::generate();
        $tag_keys = $this->getAllTagKeys($args);

        $tags = [];
        $mc_set = [];

        foreach ([self::TAG_HARD, self::TAG_SOFT] as $importance) {
            foreach ($tag_keys[$importance] as $tag) {
                if ($importance == self::TAG_HARD) {
                    $tags[$tag] = $tag_value;
                }
                $mc_set[$tag] = $tag_value;
            }
        }

        foreach ($this->buildKeys($Keys, $args) as $idx => $key) {
            $mc_set[$key] = [
                self::SUBKEY_DATA => $values[$idx],
                self::SUBKEY_TAGS => $tags,
            ];
        }

        foreach ($mc_set as $key => $value) {
            $this->Connection->set($key, $value, null, $this->expiration);
        }
    }

    /**
     * @param array $args
     * @return mixed
     */
    protected function getHardTagKeys(array $args) {
        return $this->getTagKeys([self::TAG_HARD], $args)[self::TAG_HARD];
    }

    /**
     * @param array $args
     * @param bool $flat
     * @return array
     */
    protected function getAllTagKeys(array $args, $flat = false) {
        $result = $this->getTagKeys([self::TAG_HARD, self::TAG_SOFT], $args);
        if ($flat) {
            $flat_result = [];
            foreach ($result as $tags) {
                $flat_result = array_merge($flat_result, $tags);
            }
            return $flat_result;
        }
        return $result;
    }

    /**
     * @param array $importance_types
     * @param array $args
     * @return array
     */
    protected function getTagKeys(array $importance_types, array $args) {
        $result = [];
        foreach ($importance_types as $importance) {
            if (empty($this->tags[$importance])) {
                $result[$importance] = [];
            } else {
                foreach ($this->tags[$importance] as $tag_template => $TagKey) {
                    $result[$importance][$tag_template] = self::TAG_NAMESPACE . $TagKey($args);
                }
            }
        }
        return $result;
    }

    /**
     * @param array $Keys
     * @param array $args
     * @return array
     */
    protected function buildKeys(array $Keys, array $args) {
        $result = [];
        foreach ($Keys as $idx => $Key) {
            $result[$idx] = $Key($args);
        }
        return $result;
    }

    /**
     * @param array $key_templates
     * @return array
     */
    protected function createKeys(array $key_templates) {
        return Key::createMulti($key_templates, $this->key_options);
    }

    /**
     * @param $key_template
     * @return callable
     */
    protected function createKey($key_template) {
        return Key::create($key_template, $this->key_options);
    }

}
