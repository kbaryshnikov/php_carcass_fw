<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

use Carcass\Field;

/**
 * Callback rule: validate via bool callback($Rule, $value, $kwargs)
 *
 * @package Carcass\Rule
 */
class Callback extends Base {

    /**
     * @var array|null
     */
    protected $kwargs   = null;
    /**
     * @var callable|null
     */
    protected $Callback = null;

    /**
     * @param callable $Callback
     * @param array $kwargs
     */
    public function __construct(Callable $Callback, $kwargs = []) {
        $this->Callback = $Callback;
        $this->kwargs   = $kwargs;
    }

    /**
     * @param \Carcass\Field\FieldInterface $Field
     * @return bool
     */
    public function validateField(Field\FieldInterface $Field) {
        $this->kwargs['Field'] = $Field;
        return parent::validateField($Field);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function validate($value) {
        return call_user_func_array($this->Callback, [$this, $value, $this->kwargs]);
    }

    /**
     * @param $value
     */
    public function setError($value) {
        $this->ERROR = $value;
    }
}
