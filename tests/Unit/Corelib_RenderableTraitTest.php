<?php

use \Carcass\Corelib;

class RenderableTraitUser implements Corelib\RenderableInterface {
    use Corelib\RenderableTrait;

    public function __construct(array $data = []) {
        $this->data = $data;
    }

    public function renderListTo(Corelib\ResultInterface $Result) {
        /** @noinspection PhpUndefinedFieldInspection */ // PHPStorm bug
        $Result->count->assign(count($this->data));
        /** @noinspection PhpUndefinedFieldInspection */
        $Result->list->assign($this->data);
    }

    protected function getRenderArray() {
        return $this->data;
    }

}

class RenderableTraitWithDefaultRendererUser extends RenderableTraitUser {

    protected function getDefaultRenderer() {
        return 'foo';
    }

    public function renderFooTo(Corelib\ResultInterface $Result) {
        $Result->assign('foo');
    }
}

class Corelib_RenderableTraitTest extends PHPUnit_Framework_TestCase {

    public function testDefaultRenderer() {
        $Result = $this->getMock('\Carcass\Corelib\ResultInterface');
        $Result->expects($this->once())
            ->method('assign')
            ->with($data = ['a' => 1, 'b' => 'bb']);

        /** @var $Result Corelib\ResultInterface */
        (new RenderableTraitUser($data))->renderTo($Result);
    }

    public function testNamedMethodRenderer() {
        $Result         = $this->getMock('\Carcass\Corelib\ResultInterface');
        $CountSubresult = $this->getMock('\Carcass\Corelib\ResultInterface');
        $ListSubresult  = $this->getMock('\Carcass\Corelib\ResultInterface');

        $Result->expects($this->at(0))
            ->method('__get')
            ->with('count')
            ->will($this->returnValue($CountSubresult));
        $Result->expects($this->at(1))
            ->method('__get')
            ->with('list')
            ->will($this->returnValue($ListSubresult));

        $CountSubresult->expects($this->once())
            ->method('assign')
            ->with(2);

        $ListSubresult->expects($this->once())
            ->method('assign')
            ->with($data = ['a' => 1, 'b' => 'bb']);

        /** @var $Result Corelib\ResultInterface */
        (new RenderableTraitUser($data))->setRenderer('list')->renderTo($Result);
    }

    public function testCallableRenderer() {
        $Result         = $this->getMock('\Carcass\Corelib\ResultInterface');
        $CountSubresult = $this->getMock('\Carcass\Corelib\ResultInterface');
        $ListSubresult  = $this->getMock('\Carcass\Corelib\ResultInterface');

        $Result->expects($this->at(0))
            ->method('__get')
            ->with('count')
            ->will($this->returnValue($CountSubresult));
        $Result->expects($this->at(1))
            ->method('__get')
            ->with('list')
            ->will($this->returnValue($ListSubresult));

        $CountSubresult->expects($this->once())
            ->method('assign')
            ->with(2);

        $ListSubresult->expects($this->once())
            ->method('assign')
            ->with($data = ['a' => 1, 'b' => 'bb']);

        $RU = new RenderableTraitUser($data);
        /** @var $Result Corelib\ResultInterface */
        $RU->setRenderer([$RU, 'renderListTo'])->renderTo($Result);
    }

    public function testClosureRenderer() {
        $ResultMock = $this->getMock('\Carcass\Corelib\ResultInterface');

        $ResultMock->expects($this->once())
            ->method('assign')
            ->with($data = ['a' => 1, 'b' => 'bb']);

        $RU = new RenderableTraitUser($data);

        $Test = $this;

        $fn = function (Corelib\ResultInterface $Result) use ($ResultMock, $data, $Test, $RU) {
            $Test->assertSame($Result, $ResultMock);
            $Test->assertInstanceOf('RenderableTraitUser', $this);
            $Result->assign($data);
        };
        /** @var $ResultMock Corelib\ResultInterface */
        $RU->setRenderer($fn)->renderTo($ResultMock);
    }

    public function testRedefinedDefaultRenderer() {
        $Result = $this->getMock('\Carcass\Corelib\ResultInterface');

        $Result->expects($this->once())
            ->method('assign')
            ->with('foo');

        /** @var $Result Corelib\ResultInterface */
        (new RenderableTraitWithDefaultRendererUser([]))->renderTo($Result);
    }

    public function testExceptionIsThrownForUndefinedRendererMethod() {
        $this->setExpectedException('\InvalidArgumentException');
        (new RenderableTraitUser([]))->setRenderer('ffuuuu');
    }
}

