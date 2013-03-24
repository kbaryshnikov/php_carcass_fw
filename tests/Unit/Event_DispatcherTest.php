<?php

use \Carcass\Event;

class Event_DispatcherTest_Handler {

    public static $static_args = null;
    public static $static_call_count = 0;

    public $args = null;
    public $call_count = 0;

    public function handler(array $args) {
        $this->args = $args;
        ++$this->call_count;
    }

    public static function staticHandler(array $args) {
        self::$static_args = $args;
        ++self::$static_call_count;
    }

}

class Event_DispatcherTest extends PHPUnit_Framework_TestCase {

    public function testDispatchEventClosure() {
        $Dispatcher = new Event\Dispatcher;
        $event_args = null;
        $Dispatcher->addEventHandler(
            'foo', function (array $args) use (&$event_args) {
                $event_args = $args;
            }
        );
        $foo_args = ['test' => true];
        $Dispatcher->fireEvent('foo', $foo_args);
        $this->assertEquals($foo_args, $event_args);
    }

    public function testDispatchEventClassMethod() {
        $Dispatcher = new Event\Dispatcher;
        $EventHandler = new Event_DispatcherTest_Handler;
        $Dispatcher->addEventHandler('foo', [$EventHandler, 'handler']);
        $foo_args = ['test' => true];
        $Dispatcher->fireEvent('foo', $foo_args);
        $this->assertEquals($foo_args, $EventHandler->args);
    }

    public function testDispatchEventStaticMethod() {
        $Dispatcher = new Event\Dispatcher;
        $Dispatcher->addEventHandler('foo', ['Event_DispatcherTest_Handler', 'staticHandler']);
        $foo_args = ['test' => true];
        $Dispatcher->fireEvent('foo', $foo_args);
        $this->assertEquals($foo_args, Event_DispatcherTest_Handler::$static_args);
    }

    public function testSameClosureNotCalledTwice() {
        $Dispatcher = new Event\Dispatcher;
        $call_count = 0;
        $handler = function () use (&$call_count) {
            ++$call_count;
        };
        $Dispatcher->addEventHandler('foo', $handler);
        $Dispatcher->addEventHandler('foo', $handler);
        $Dispatcher->fireEvent('foo');
        $this->assertEquals(1, $call_count);
    }

    public function testFireEventArgsModifiedByReference() {
        $Dispatcher = new Event\Dispatcher;
        $Dispatcher->addEventHandler(
            'foo', function (array &$args) {
                $args['modified'] = true;
            }
        );
        $test_args = [];
        $Dispatcher->fireEvent('foo', $test_args);
        $this->assertEquals(['modified' => true], $test_args);
    }

    public function testSameMethodNotCalledTwice() {
        $Dispatcher = new Event\Dispatcher;
        $EventHandler = new Event_DispatcherTest_Handler;
        $Dispatcher->addEventHandler('foo', [$EventHandler, 'Handler']);
        $Dispatcher->addEventHandler('foo', [$EventHandler, 'handler']);
        $Dispatcher->fireEvent('foo');
        $this->assertEquals(1, $EventHandler->call_count);
    }

    public function testSameStaticMethodNotCalledTwice() {
        $Dispatcher = new Event\Dispatcher;
        Event_DispatcherTest_Handler::$static_call_count = 0;
        $Dispatcher->addEventHandler('foo', ['Event_DispatcherTest_Handler', 'statichandler']);
        $Dispatcher->addEventHandler('foo', ['Event_DispatcherTest_Handler', 'staticHandler']);
        $Dispatcher->addEventHandler('foo', ['event_DispatcherTest_Handler', 'staticHandler']);
        $Dispatcher->fireEvent('foo');
        $this->assertEquals(1, Event_DispatcherTest_Handler::$static_call_count);
    }

    public function testHandlersChain() {
        $Dispatcher = new Event\Dispatcher;

        $Dispatcher->addEventHandler(
            'foo', function (array &$args)  {
                $args['first'] = true;
            }
        );
        $Dispatcher->addEventHandler(
            'foo', function (array &$args) {
                $args['second'] = true;
                return false;
            }
        );
        $Dispatcher->addEventHandler(
            'foo', function (array &$args) {
                $event_args['should_not_be_called'] = true;
            }
        );
        $test_args = ['test' => true];
        $Dispatcher->fireEvent('foo', $test_args);
        $expect_args = ['test' => true, 'first' => true, 'second' => true];
        $this->assertEquals($expect_args, $test_args);
    }

}

