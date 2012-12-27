<?php

namespace Carcass\Application;

interface Web_Session_StorageInterface {

    public function get($session_id);

    public function write($session_id, array $data);

    public function delete($session_id);

}
