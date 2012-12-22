<?php

require_once __DIR__ . '/carcass-test.php';

use \Carcass\Fs as Fs;

class Fs_LockFileTest extends PHPUnit_Framework_TestCase {

    public function testLockFileIsUnlinkedOnRelease() {
        $F = Fs\LockFile::constructTemporary();
        $filename = $F->getPathname();
        $this->assertTrue($F->lock());
        $F->fwrite("test");
        unset($F);
        $this->assertFalse(file_exists($filename));
    }

    public function testLockFileIsLocked() {
        $F = Fs\LockFile::constructTemporary();
        $filename = $F->getPathname();
        $this->assertTrue($F->lock());
        $F2 = new Fs\LockFile($filename);
        $this->assertFalse($F2->flock(LOCK_EX | LOCK_NB));
        unset($F);
        unset($F2);
        $this->assertFalse(file_exists($filename));
    }

}
