<?php

namespace Carcass\Connection;

use Carcass\Corelib;

class DsnPool extends Corelib\Hash {

    protected $type = null;

    protected $string_id = null;

    public function __construct($items = null) {
        $items and $this->addItems($items);
    }

    public function getType() {
        return $this->type;
    }

    public function addItems($items) {
        if (!Corelib\ArrayTools::isTraversable($items)) {
            throw new \InvalidArgumentException('$items must be traversable');
        }
        foreach ($items as $item) {
            $this->addItem($item);
        }
        return $this;
    }

    public function addItem($item) {
        if (! $item instanceof Dsn) {
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

    public function __toString() {
        if (null === $this->string_id) {
            $ids = [];
            foreach ($this as $Item) {
                $ids[] = (string)$Item;
            }
            $this->string_id = 'pool://' . md5(join("\t", $ids));
        }
        return $this->string_id;
    }

}
