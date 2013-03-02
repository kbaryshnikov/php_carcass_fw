<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Unique object ID trait
 */
trait UniqueObjectIdTrait {

    protected $unique_object_id = null;

    /**
     * @return string
     */
    protected function getUniqueObjectId() {
        if (null === $this->unique_object_id) {
            $this->unique_object_id = UniqueId::generate();
        }
        return $this->unique_object_id;
    }

}