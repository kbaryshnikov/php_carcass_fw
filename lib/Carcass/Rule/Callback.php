<?php

namespace Carcass\Rule;

use Carcass\Field;

class Callback extends Base {

    protected $kwargs   = null;
    protected $Callback = null;

    public function __construct(Callable $Callback, $kwargs = []) {
        $this->Callback = $Callback;
        $this->kwargs   = $kwargs;
    }

    public function validateField(Field\FieldInterface $Field) {
        $this->kwargs['Field'] = $Field;
        return parent::validateField($Field);
    }

    public function validate($value) {
        return call_user_func_array($this->Callback, [$this, $value, $this->kwargs]);
    }

    public function setError($value) {
        $this->ERROR = $value;
    }
}
