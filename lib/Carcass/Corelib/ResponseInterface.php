<?php

namespace Carcass\Corelib;

interface ResponseInterface {

    public function begin();

    public function commit();

    public function rollback();

    public function write($string);

    public function writeLn($string);

    public function writeError($string);

    public function writeErrorLn($string);

    public function setStatus($status);

    public function getStatus();

}
