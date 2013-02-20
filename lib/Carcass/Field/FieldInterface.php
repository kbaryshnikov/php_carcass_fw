<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Field;

use Carcass\Corelib;
use Carcass\Rule;
use Carcass\Filter;

/**
 * FieldInterface
 * @package Carcass\Field
 */
interface FieldInterface extends Corelib\ExportableInterface, Corelib\RenderableInterface {

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value);

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @return string
     */
    public function __toString();

    /**
     * @param $rule
     * @return $this
     */
    public function addRule($rule);

    /**
     * @param array $rules
     * @return $this
     */
    public function setRules(array $rules);

    /**
     * @param $filter
     * @return $this
     */
    public function addFilter($filter);

    /**
     * @param array $filters
     * @return $this
     */
    public function setFilters(array $filters);

    /**
     * @return array
     */
    public function getRenderArray();

    /**
     * @return bool
     */
    public function validate();

    /**
     * @return array|null
     */
    public function getError();

    /**
     * @param $error
     * @return $this
     */
    public function setError($error);

}
