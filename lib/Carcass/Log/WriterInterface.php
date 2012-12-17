<?php

namespace Carcass\Log;

interface WriterInterface {

    public function log(Message $Message);

}
