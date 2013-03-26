<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Connection;

use Carcass\Corelib;

/**
 * Represents a DSN pool for connections that support pools (e.g. memcached)
 *
 * @package Carcass\Connection
 */
class DsnPool extends Corelib\Hash implements DsnInterface {

    /**
     * @var string|null
     */
    protected $type = null;

    /**
     * @var string|null
     */
    protected $string_id = null;

    /**
     * @param mixed $items array or traversable object
     */
    public function __construct($items = null) {
        $items and $this->addItems($items);
    }

    /**
     * @return string|null
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param mixed $items array or traversable object
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addItems($items) {
        if (!Corelib\ArrayTools::isTraversable($items)) {
            throw new \InvalidArgumentException('$items must be traversable');
        }
        foreach ($items as $item) {
            $this->addItem($item);
        }
        return $this;
    }

    /**
     * @param Dsn|string $item
     * @return $this
     * @throws \LogicException
     */
    public function addItem($item) {
        if (!$item instanceof Dsn) {
            $item = new Dsn((string)$item);
        }
        if (null === $this->type) {
            $this->type = $item->getType();
        } else {
            if ($this->type !== $item->getType()) {
                throw new \LogicException("Type mismatch: cannot add a '{$item->getType()}' item to a '{$this->type}' pool");
            }
        }

        $this[] = $item;

        $this->string_id = null;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString() {
        if (null === $this->string_id) {
            $ids = [];
            foreach ($this as $Item) {
                $ids[] = (string)$Item;
            }
            $this->string_id = 'pool://' . count($ids) . '@' . $this->getType() . '?' . join(';', $ids);
        }
        return $this->string_id;
    }

}
