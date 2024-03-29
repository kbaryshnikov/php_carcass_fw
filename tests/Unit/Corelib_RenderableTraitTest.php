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

    public function getRenderArray() {
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

    public function testSubrender() {
        $Result = new Corelib\Result;
        $Result->bind(new Corelib_RenderableTraitTest_Outer);
        $expected = [
            'first' => 1,
            'second' => 2,
        ];
        $actual = $Result->exportArray();
        $this->assertEquals($expected, $actual);
    }

    public function testBindMerge() {
        $Result = new Corelib\Result;

        $Result->bind(new Corelib_RenderableTraitTest_Inner(['first' => 1]));
        $Result->bindMerge(new Corelib_RenderableTraitTest_Inner(['second' => 2]));

        $expected = [
            'first' => 1,
            'second' => 2,
        ];

        $actual = $Result->exportArray();
        $this->assertEquals($expected, $actual);
    }

    public function testInnerBindMerge() {
        $Result = new Corelib\Result;

        $Result->inner->bind(new Corelib_RenderableTraitTest_Inner(['inner_first' => 1]));
        $Result->inner->bindMerge(new Corelib_RenderableTraitTest_Inner(['inner_second' => 2]));

        $expected = [
            'inner' => [
                'inner_first' => 1,
                'inner_second' => 2,
            ]
        ];

        $actual = $Result->exportArray();
        $this->assertEquals($expected, $actual);
    }

    public function testBindArray() {
        $expected = [
            'inner' => $inner = [
                'inner_first' => 1,
                'inner_second' => 2,
            ]
        ];

        $inner_merge = ['inner_third' => 3];
        $expected['inner'] += $inner_merge;

        $Result = new Corelib\Result;
        $Result->inner->bind($inner);
        $Result->inner->bindMerge($inner_merge);

        $this->assertEquals($expected, $Result->exportArray());
    }

    public function testBindSubitems() {
        $Result = new Corelib\Result;
        $Result->bind(new Corelib_RenderableTraitTest_Inner(['x' => 1]));
        $Result->inner->bind(new Corelib_RenderableTraitTest_Inner(['y' => 1]));
        $expected = [
            'x' => 1,
            'inner' => [
                'y' => 1,
            ],
        ];
        $this->assertEquals($expected, $Result->exportArray());
    }

    public function testBindSubitemsMerge() {
        $Result = new Corelib\Result;
        $Result->bind(new Corelib_RenderableTraitTest_Inner(['x' => 1, 'inner' => ['x' => 1]]));
        $Result->inner->bind(new Corelib_RenderableTraitTest_Inner(['y' => 1]));
        $expected = [
            'x' => 1,
            'inner' => [
                'x' => 1,
                'y' => 1,
            ],
        ];
        $this->assertEquals($expected, $Result->exportArray());
    }

    public function testBindSubitemsScalarPriority() {
        $Result = new Corelib\Result;
        $Result->bind(new Corelib_RenderableTraitTest_Inner(['x' => 1, 'inner' => ['y' => 'test']]));
        $Result->inner->bind(new Corelib_RenderableTraitTest_Inner(['y' => 1]));
        $expected = [
            'x' => 1,
            'inner' => [
                'y' => 1,
            ],
        ];
        $this->assertEquals($expected, $Result->exportArray());
    }

    public function testBindSubitemsBindMerge() {
        $Result = new Corelib\Result;
        $Result->bind(new Corelib_RenderableTraitTest_Inner(['x' => 1, 'inner' => ['x' => 1]]));
        $Result->inner->bind(new Corelib_RenderableTraitTest_Inner(['y' => 1]));
        $Result->inner->bindMerge(new Corelib_RenderableTraitTest_Inner(['z' => 1]));
        $expected = [
            'x' => 1,
            'inner' => [
                'x' => 1,
                'y' => 1,
                'z' => 1,
            ],
        ];
        $this->assertEquals($expected, $Result->exportArray());
    }

}

class Corelib_RenderableTraitTest_Outer implements Corelib\RenderableInterface {

    public function renderTo(Corelib\ResultInterface $Result) {
        $Result->first->bind(new Corelib_RenderableTraitTest_Inner(1));
        $Result->second->bind(new Corelib_RenderableTraitTest_Inner(2));
        return $this;
    }

}

class Corelib_RenderableTraitTest_Inner implements Corelib\RenderableInterface {
    
    protected $id;

    public function __construct($id) {
        $this->id = $id;
    }

    public function renderTo(Corelib\ResultInterface $Result) {
        $Result->assign($this->id);
        return $this;
    }

}
