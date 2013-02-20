<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

use Carcass\Field;
use Carcass\Corelib;

/**
 * Base rule implementation
 * @package Carcass\Rule
 */
class Base implements RuleInterface {

    /**
     * @var string  Redefine in descendants with error code
     */
    protected $ERROR = '@undefined@';

    /**
     * @param array $args (type [, constructor args...])
     * @return RuleInterface
     * @throws \InvalidArgumentException
     */
    public static function factory(array $args) {
        $type = (string)array_shift($args);
        if (!$type) {
            throw new \InvalidArgumentException("Missing rule type");
        }
        $class = substr($type, 0, 1) == '\\' ? $type : __NAMESPACE__ . '\\' . ucfirst($type);
        return Corelib\ObjectTools::construct($class, $args);
    }

    /**
     * Override on descendants to implement real validation
     *
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return false;
    }

    /**
     * @param \Carcass\Field\FieldInterface $Field
     * @return bool
     */
    public function validateField(Field\FieldInterface $Field) {
        $result = $this->validateFieldValue($Field);
        if ($result === false) {
            $Field->setError($this->getErrorName());
        }
        return $result;
    }

    /**
     * @param $value
     * @return bool
     */
    public function validateValue($value) {
        return false;
    }

    /**
     * @return string
     */
    public function getErrorName() {
        return $this->ERROR;
    }

    /**
     * @param \Carcass\Field\FieldInterface $Field
     * @return bool
     */
    protected function validateFieldValue(Field\FieldInterface $Field) {
        return $this->validate($Field->getValue());
    }

}

