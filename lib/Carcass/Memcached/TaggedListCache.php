<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Memcached;

use Carcass\Corelib;

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

    public function __construct(TaggedCache $TaggedCache, $key, $chunk_size = null) {
        $this->TaggedCache  = $TaggedCache;
        $this->key_template = $key;
        $chunk_size and $this->setChunkSize($chunk_size);
    }

    public function setCount($count) {
        $this->count = $count;
        return $this;
    }

    public function setChunkSize($size) {
        $this->chunk_size = $size;
        return $this;
    }

    public function getCount() {
        if (null === $this->count) {
            throw new \LogicException('Count is undefined');
        }
        return $this->count;
    }

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

    public function isIncomplete() {
        if ($this->is_incomplete === null) {
            throw new \LogicException('get() was not called before');
        }
        return $this->is_incomplete;
    }

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
            return false;
        }

        if ($incomplete_slices) {
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
            $mset_data[$this->getChunkKeyTemplate($idx)] = $data;
        }

        $this->TaggedCache->setMulti($mset_data, $args);

        return true;
    }

    public function delete(array $args) {
        $this->TaggedCache->set($this->getCountKeyTemplate(), false, $args);
        return $this;
    }

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
