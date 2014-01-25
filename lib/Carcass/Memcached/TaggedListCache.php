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
 * TaggedListCache: caches lists via TaggedCache by splitting list data into to chunks.
 *
 * @package Carcass\Memcached
 */
class TaggedListCache {

    const
        DEFAULT_CHUNK_SIZE = 10,
        CHUNK_KEY_FORMAT   = '|{{ key_template }}|{{ idx }}',
        COUNT_KEY_FORMAT   = '|{{ key_template }}|#';

    /** @var TaggedCache */
    protected $TaggedCache;

    protected
        $is_incomplete = null,
        $key_template = null,
        $count = null,
        $chunk_size = self::DEFAULT_CHUNK_SIZE;

    /**
     * @param TaggedCache $TaggedCache
     * @param string $key_template
     * @param int|null $chunk_size defaults to DEFAULT_CHUNK_SIZE
     */
    public function __construct(TaggedCache $TaggedCache, $key_template, $chunk_size = null) {
        $this->TaggedCache  = $TaggedCache;
        $this->key_template = $key_template;
        $chunk_size and $this->setChunkSize($chunk_size);
    }

    /**
     * Set total count of items in list
     *
     * @param int $count
     * @return $this
     */
    public function setCount($count) {
        $this->count = (int)$count;
        return $this;
    }

    /**
     * Sets chunk size
     *
     * @param int $size
     * @return $this
     */
    public function setChunkSize($size) {
        $this->chunk_size = $size;
        return $this;
    }

    /**
     * Returns count of items in list
     *
     * @return int
     * @throws \LogicException
     */
    public function getCount() {
        if (null === $this->count) {
            throw new \LogicException('Count is undefined');
        }
        return $this->count;
    }

    /**
     * Returns a part of the list delimited with $offset and $limit.
     *
     * @param array $args key template arguments
     * @param int $offset
     * @param int $limit
     * @param bool $return_incomplete If true, will return incomplete lists when some chunks are missing from cache. Otherwise will return false for incomplete lists.
     * @return array|bool false
     */
    public function get(array $args, $offset, $limit, $return_incomplete = false) {
        $this->is_incomplete = false;

        $slice_keys = $this->getKeysForRange($offset, $limit);

        $count_key  = $this->getCountKeyTemplate();

        $mc_result = $this->TaggedCache->getMulti($slice_keys + ['count' => $count_key], $args);

        if (empty($mc_result) || !isset($mc_result[$count_key]) || false === $mc_result[$count_key]) {
            $this->is_incomplete = true;
            $this->count         = null;
            return false;
        }

        $first_key = reset($slice_keys);
        if (isset($mc_result[$first_key]) && is_array($mc_result[$first_key])) {
            $mc_result[$first_key] = Corelib\ArrayTools::filterAssoc(
                $mc_result[$first_key],
                function ($key) use ($offset) {
                    return $key >= $offset;
                }
            );
        }

        $count = $mc_result[$count_key];

        $result = [];
        foreach ($slice_keys as $slice_key) {
            if (isset($mc_result[$slice_key]) && is_array($mc_result[$slice_key])) {
                $result += $mc_result[$slice_key];
            }
        }

        $required_count = min($offset + $limit, $count) - $offset;

        if (count($result) > $required_count) {
            $result = array_slice($result, 0, $required_count, true);
        }

        $this->is_incomplete = count($result) < $required_count;

        $this->count = $count;

        if ($this->is_incomplete && !$return_incomplete) {
            return false;
        }

        return $result;
    }

    /**
     * @return bool is the last get() result incomplete. Makes sense with get(return_incomplete => true).
     * @throws \LogicException
     */
    public function isIncomplete() {
        if ($this->is_incomplete === null) {
            throw new \LogicException('get() was not called before');
        }
        return $this->is_incomplete;
    }

    /**
     * Sets a part of the list, or a whole list if offset is null.
     * When setting a part of the list, count of items must be defined: directly by setCount() call, or non-directly by previous get() call.
     * In case of chunk intersection, merges with data existing in cache.
     *
     * @param array $args key template arguments
     * @param array $values array of (index => value)
     * @param int|null $offset If null, $values array is treated as full list contents, and count is defined by array size. Otherwise, $values array is a part of (offset ... offset+count($values))
     * @return bool
     */
    public function set(array $args, array $values, $offset = null) {

        if ($offset === null) {
            $offset = 0;
            if (null === $this->count) {
                $this->setCount(count($values));
            }
        }

        $incomplete_slices = [];

        $slices = $this->splitIntoSlices($values, $offset, $incomplete_slices);

        if (empty($slices)) {
            $slices = [];
        }

        if ($slices && $incomplete_slices) {
            $completion_keys = $this->getKeys($incomplete_slices);
            $cached_data     = $this->TaggedCache->getMulti($completion_keys, $args);
            if ($cached_data) {
                foreach ($completion_keys as $idx => $key) {
                    if (isset($cached_data[$key]) && is_array($cached_data[$key])) {
                        $slices[$idx] += $cached_data[$key];
                    }
                }
            }
        }

        $mset_data = [$this->getCountKeyTemplate() => $this->getCount()];
        foreach ($slices as $idx => $data) {
            ksort($data);
            $mset_data[$this->getChunkKeyTemplate($idx)] = $data;
        }

        $this->TaggedCache->setMulti($mset_data, $args);

        return true;
    }

    /**
     * Forces expiration of list without expiring tags
     *
     * @param array $args key template arguments
     * @return $this
     */
    public function delete(array $args) {
        $this->TaggedCache->set($this->getCountKeyTemplate(), false, $args);
        return $this;
    }

    /**
     * Flushes TaggedCache
     *
     * @param array $args key template arguments
     * @return $this
     */
    public function flush(array $args) {
        $this->TaggedCache->flush($args);
        return $this;
    }

    protected function splitIntoSlices(array $values, $offset, array &$incomplete_slices = []) {
        $length = count($values);

        if ($length + $offset > $this->getCount()) {
            $length = $this->getCount() - $offset;
            $values = array_slice($values, 0, $length);
        }

        $first_chunk_idx = $offset - ($mod = $offset % $this->chunk_size);
        $first_slice_len = $this->chunk_size - $mod;

        if ($mod > 0) {
            $incomplete_slices[] = $first_chunk_idx;
        }

        $slice  = array_slice($values, 0, $first_slice_len);
        $result = empty($slice) ? [] : [$first_chunk_idx => array_combine(range($offset, $offset + count($slice) - 1), $slice)];

        $chunk_idx = $first_chunk_idx;

        if ($length > $first_slice_len) {
            for ($idx = $first_slice_len; $idx < $length; $idx += $this->chunk_size) {
                $chunk_idx += $this->chunk_size;
                $slice              = array_slice($values, $idx, $this->chunk_size);
                $result[$chunk_idx] = array_combine(range($chunk_idx, $chunk_idx + count($slice) - 1), $slice);
            }
        }

        if ($chunk_idx > $first_chunk_idx) {
            $last_slice_size = count($result[$chunk_idx]);
            if ($last_slice_size < $this->chunk_size && $chunk_idx + $last_slice_size < $this->getCount()) {
                $incomplete_slices[] = $chunk_idx;
            }
        }

        return $result;
    }

    protected function getKeys(array $idxes) {
        $result = [];
        foreach ($idxes as $idx) {
            $result[$idx] = $this->getChunkKeyTemplate($idx);
        }
        return $result;
    }

    protected function getKeysForRange($offset, $limit) {
        $start_offset = $offset - ($offset % $this->chunk_size);
        $max_offset   = $offset + $limit - 1;
        $end_offset   = $max_offset - ($max_offset % $this->chunk_size);

        $result = [];
        for ($i = $start_offset; $i <= $end_offset; $i += $this->chunk_size) {
            $result[$i] = $this->getChunkKeyTemplate($i);
        }
        return $result;
    }

    protected function getCountKeyTemplate() {
        return static::parseListKeyOuterTemplate(self::COUNT_KEY_FORMAT, ['key_template' => $this->key_template]);
    }

    protected function getChunkKeyTemplate($idx) {
        return static::parseListKeyOuterTemplate(self::CHUNK_KEY_FORMAT, ['key_template' => $this->key_template, 'idx' => (int)$idx]);
    }

    protected static function parseListKeyOuterTemplate($tpl, array $args) {
        return Corelib\StringTemplate::parseString($tpl, $args);
    }

}
