<?php

class ConfigReaderStub extends Carcass\Config\Reader {

    public $files = [];

    protected function readConfigFile($config_file) {
        if (!isset($this->files[$config_file])) {
            throw new Exception("Should never get here");
        }
        return $this->files[$config_file];
    }

    protected function configFileExists($config_file) {
        return isset($this->files[$config_file]);
    }

}

class Config_ReaderTest extends PHPUnit_Framework_TestCase {

    public function testConfigReaderPriorities() {
        $ConfigReader = new ConfigReaderStub(['/app/config', '/lib/config', '/app/config/platform', '/lib/config/platform']);
        $ConfigReader->files = [
            '/app/config/test.config.php' => [ 'hostname' => 'app.config', 'aliases' => [ 'first' => 1, 'second' => 1 ] ],
            '/lib/config/test.config.php' => [ 'hostname' => 'lib.config' ],
            '/app/config/platform/test.config.php' => [ 'hostname' => 'expected.value', 'aliases' => [ 'second' => 2 ] ],
        ];
        $this->assertEquals('expected.value', $ConfigReader->getPath('test.hostname'));
        $this->assertEquals(1, $ConfigReader->getPath('test.aliases.first'));
        $this->assertEquals(2, $ConfigReader->getPath('test.aliases.second'));
    }

    public function testConfigReaderObjectAccess() {
        $ConfigReader = new ConfigReaderStub(['/app/config', '/lib/config', '/app/config/platform', '/lib/config/platform']);
        $ConfigReader->files = [
            '/app/config/test.config.php' => [ 'hostname' => 'app.config', 'aliases' => [ 'first' => 1, 'second' => 1 ] ],
            '/lib/config/test.config.php' => [ 'hostname' => 'lib.config' ],
            '/app/config/platform/test.config.php' => [ 'hostname' => 'expected.value', 'aliases' => [ 'second' => 2 ] ],
        ];
        $this->assertEquals('expected.value', $ConfigReader->test->hostname);
        $this->assertEquals(1, $ConfigReader->test->aliases->first);
    }

    public function testConfigReaderObjectAccessExceptionFirstLevel() {
        $this->setExpectedException('OutOfBoundsException');
        $ConfigReader = new ConfigReaderStub(['/app/config', '/lib/config', '/app/config/platform', '/lib/config/platform']);
        $ConfigReader->files = [
            '/app/config/test.config.php' => [ 'hostname' => 'app.config', 'aliases' => [ 'first' => 1, 'second' => 1 ] ],
            '/lib/config/test.config.php' => [ 'hostname' => 'lib.config' ],
            '/app/config/platform/test.config.php' => [ 'hostname' => 'expected.value', 'aliases' => [ 'second' => 2 ] ],
        ];
        $ConfigReader->nope;
    }

    public function testConfigReaderObjectAccessExceptionSecondLevel() {
        $this->setExpectedException('OutOfBoundsException');
        $ConfigReader = new ConfigReaderStub(['/app/config', '/lib/config', '/app/config/platform', '/lib/config/platform']);
        $ConfigReader->files = [
            '/app/config/test.config.php' => [ 'hostname' => 'app.config', 'aliases' => [ 'first' => 1, 'second' => 1 ] ],
            '/lib/config/test.config.php' => [ 'hostname' => 'lib.config' ],
            '/app/config/platform/test.config.php' => [ 'hostname' => 'expected.value', 'aliases' => [ 'second' => 2 ] ],
        ];
        $ConfigReader->aliases->nope;
    }

    public function testConfigReaderExportArrayFirstLevel() {
        $ConfigReader = new ConfigReaderStub(['/app/config', '/lib/config', '/app/config/platform', '/lib/config/platform']);
        $ConfigReader->files = [
            '/app/config/test.config.php' => [ 'hostname' => 'app.config', 'aliases' => [ 'first' => 1, 'second' => 1 ] ],
            '/lib/config/test.config.php' => [ 'hostname' => 'lib.config' ],
            '/app/config/platform/test.config.php' => [ 'hostname' => 'expected.value', 'aliases' => [ 'second' => 2 ] ],
        ];
        $this->assertEquals(['hostname' => 'expected.value', 'aliases' => ['first' => 1, 'second' => 2]], $ConfigReader->test->exportArray());
    }

    public function testConfigReaderExportArraySecondLevel() {
        $ConfigReader = new ConfigReaderStub(['/app/config', '/lib/config', '/app/config/platform', '/lib/config/platform']);
        $ConfigReader->files = [
            '/app/config/test.config.php' => [ 'hostname' => 'app.config', 'aliases' => [ 'first' => 1, 'second' => 1 ] ],
            '/lib/config/test.config.php' => [ 'hostname' => 'lib.config' ],
            '/app/config/platform/test.config.php' => [ 'hostname' => 'expected.value', 'aliases' => [ 'second' => 2 ] ],
        ];
        $this->assertEquals(['first' => 1, 'second' => 2], $ConfigReader->test->aliases->exportArray());
    }

    public function testConfigReaderGetPathDefaultValue() {
        $ConfigReader = new ConfigReaderStub(['/app/config', '/lib/config', '/app/config/platform', '/lib/config/platform']);
        $ConfigReader->files = [
            '/app/config/test.config.php' => [ 'hostname' => 'app.config', 'aliases' => [ 'first' => 1, 'second' => 1 ] ],
            '/lib/config/test.config.php' => [ 'hostname' => 'lib.config' ],
            '/app/config/platform/test.config.php' => [ 'hostname' => 'expected.value', 'aliases' => [ 'second' => 2 ] ],
        ];
        $this->assertEquals(['expected'], $ConfigReader->getPath('nope', ['expected']));
        $this->assertEquals(['expected'], $ConfigReader->getPath('test.nope', ['expected']));
        $this->assertEquals(['expected'], $ConfigReader->getPath('test.hostname.nope', ['expected']));
    }

    public function testConfigReaderExportArrayFrom() {
        $ConfigReader = new ConfigReaderStub(['/app/config', '/lib/config', '/app/config/platform', '/lib/config/platform']);
        $array = [ 'hostname' => 'expected.value', 'aliases' => [ 'first' => 1, 'second' => 2 ] ];
        $ConfigReader->files = [
            '/app/config/platform/test.config.php' => $array
        ];
        $this->assertEquals($array, $ConfigReader->exportArrayFrom('test'));
        $this->assertEquals($array['aliases'], $ConfigReader->exportArrayFrom('test.aliases'));
        $this->assertEquals([], $ConfigReader->exportArrayFrom('nope'));
        $this->assertEquals([], $ConfigReader->exportArrayFrom('test.nope'));
        $this->assertEquals([], $ConfigReader->exportArrayFrom('test.hostname'));
        $this->assertEquals(['expected'], $ConfigReader->exportArrayFrom('nope', ['expected']));
        $this->assertEquals(['expected'], $ConfigReader->exportArrayFrom('test.nope', ['expected']));
        $this->assertEquals(['expected'], $ConfigReader->exportArrayFrom('test.hostname', ['expected']));
    }

}
