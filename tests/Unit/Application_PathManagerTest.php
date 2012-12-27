<?php

require_once __DIR__ . '/carcass-test.php';

use \Carcass\Application;

class Application_PathManagerTest extends PHPUnit_Framework_TestCase {
    
    public function testPathBuilder() {
        $PathManager = new Application\PathManager('/root', [
            'var' => 'vars',
            'extras' => 'extras/',
            'abs' => '/abs'
        ]);
        $this->assertEquals('/root/', $PathManager->getPathTo('application'));
        $this->assertEquals('/root/filename.ext', $PathManager->getPathTo('application', 'filename.ext'));
        $this->assertEquals('/root/', $PathManager->getAppRoot());
        $this->assertEquals('/root/vars/', $PathManager->getPathTo('var'));
        $this->assertEquals('/root/pages/', $PathManager->getPathTo('pages'));
        $this->assertEquals('/root/pages/filename.ext', $PathManager->getPathTo('pages', 'filename.ext'));
        $this->assertEquals('/root/pages/filename.ext', $PathManager->getPathTo('pages', '/filename.ext'));
        $this->assertEquals('/root/pages/dirname/', $PathManager->getPathTo('pages', 'dirname/'));
        $this->assertEquals('/root/extras/', $PathManager->getPathTo('extras'));
        $this->assertEquals('/abs/', $PathManager->getPathTo('abs'));
        $this->assertEquals('/abs/subdir/', $PathManager->getPathTo('abs', 'subdir/'));
        $this->setExpectedException('RuntimeException');
        $PathManager->getPathTo('nope');
    }

    public function testGetPathToPhpFile() {
        $PathManager = new Application\PathManager('/root');
        $this->assertEquals('/root/scripts/Script.php', $PathManager->getPathToPhpFile('scripts', 'Script'));
        $this->assertEquals('/root/scripts/Some/Script.php', $PathManager->getPathToPhpFile('scripts', 'Some_Script'));
        $this->assertEquals('/root/scripts/Some/Script.php', $PathManager->getPathToPhpFile('scripts', 'Some\\Script'));
    }

}
