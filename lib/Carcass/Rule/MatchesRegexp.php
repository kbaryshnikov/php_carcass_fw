<?php

namespace Carcass\Rule;

class MatchesRegexp extends Base {

    protected $regexp;
    protected $ERROR = 'does_not_match_regexp';

    public function __construct($regexp) {
        $this->regexp = $regexp;
    }

    public function validate($value) {
        return (null === $value || preg_match($this->regexp, $value));
    }
}
