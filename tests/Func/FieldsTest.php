<?php

use Carcass\Field;
use Carcass\Rule;
use Carcass\Filter;

class FieldsFieldsTest extends PHPUnit_Framework_TestCase {

    public function testFieldsBaseGetSet() {
        $Id = new Field\Id(1);
        $Text = new Field\Scalar('test');
        $C = new Field\Set([
            'id'   => $Id,
            'text' => $Text,
        ]);
        $this->assertSame($Id, $C->get('id'));
        $this->assertSame($Text, $C->get('text'));
        $this->assertNull($C->get('null'));
        
        $this->assertEquals(1, $C->id);
        $this->assertEquals('test', $C->text);

        $this->assertEquals('test', $C->getFieldValue('text'));

        $this->assertEquals('default', $C->getFieldValue('null', 'default'));

        $C->text = 'x';
        $this->assertEquals('x', $C->text);

        $C->merge(['id' => 2]);
        $this->assertEquals(2, $C->id);
        $this->assertEquals('x', $C->text);

        $this->assertEquals(['id' => 2, 'text' => 'x'], $C->exportArray());

        $this->setExpectedException('InvalidArgumentException');
        $C->null;
    }

    public function testFieldsAddFieldsFactory() {
        $C = new Field\Set([
            'id'    => 'id',
            'id2'   => ['id', 2],
        ]);
        $C->addFields(['id3' => ['id', 3]]);
        $this->assertInstanceOf('\Carcass\Field\Id', $C->get('id'));
        $this->assertInstanceOf('\Carcass\Field\Id', $C->get('id2'));
        $this->assertInstanceOf('\Carcass\Field\Id', $C->get('id3'));
        $this->assertNull($C->id);
        $this->assertEquals(2, $C->id2);
        $this->assertEquals(3, $C->id3);
    }

    public function testInnerFieldss() {
        $Id = new Field\Id(1);
        $Text = new Field\Scalar('test');
        $C = new Field\Set([
            'id'   => $Id,
            'inner' => $Inner = new Field\Set([
                'text' => $Text
            ]),
        ]);
        $this->assertSame($Inner, $C->get('inner'));
        $this->assertEquals('test', $C->inner->text);

        $C->inner->text = 'new';
        $this->assertEquals('new', $C->inner->text);

        $C->merge([
            'inner' => ['text' => 'merged']
        ]);
        $this->assertEquals('merged', $C->inner->text);
    }

    public function testFilters() {
        $C = new Field\Set;
        $C->addFields([
            'i'    => 'scalar',
            's'    => 'scalar',
            'n'    => 'scalar',
        ]);
        $C->setFilters([
            'i'     => 'intval',
            's'     => new Filter\Trim,
            'n'     => ['trim', 'nullifyEmpty'],
        ]);
        $C->i = null;
        $C->s = ' foo ';
        $C->n = '  ';
        $this->assertSame(0, $C->i);
        $this->assertEquals('foo', $C->s);
        $this->assertNull($C->n);
    }

    public function testRules() {
        $C = new Field\Set([
            'i' => ['scalar', 1],
            'x' => ['scalar', -1],
            's' => ['scalar', ''],
            'e' => ['scalar', 'foo'],
        ]);
        $C->setRules([
            'i' => new Rule\IsValidId,
            'x' => [ 'isNotEmpty', ['isNotLess', 1] ],
            's' => 'isNotEmpty',
            'e' => ['isNotEmpty', 'isValidEmail']
        ]);

        $this->assertEquals(1, $C->getField('i')->getValue());
        $this->assertEquals(-1, $C->getField('x')->getValue());
        $this->assertEquals('', $C->getField('s')->getValue());
        $this->assertEquals('foo', $C->getField('e')->getValue());

        $this->assertFalse($C->validate());

        $errors = $C->getError();
        $this->assertEquals((new Rule\IsNotLess(0))->getErrorName(), $errors['x']);
        $this->assertEquals((new Rule\IsNotEmpty)->getErrorName(), $errors['s']);
        $this->assertEquals((new Rule\IsValidEmail)->getErrorName(), $errors['e']);
    }

    public function testNonDynamicFormDisallowsUndefinedAccess() {
        $C = new Field\Set;
        $this->setExpectedException('LogicException');
        $C->x;
    }

    public function testNonDynamicFormDisallowsRulesForUndefinedFields() {
        $C = new Field\Set;
        $this->setExpectedException('LogicException');
        $C->setRules(['x' => 'isNotEmpty']);
    }

    public function testNonDynamicFormDisallowsFiltersForUndefinedFields() {
        $C = new Field\Set;
        $this->setExpectedException('LogicException');
        $C->setFilters(['x' => 'isNotEmpty']);
    }

    public function testDynamicForm() {
        $C = Field\Set::constructDynamic();
        $C->setRules(['x' => 'isNotEmpty']);
        $C->setFilters(['y' => 'nullifyEmpty']);
        /** @noinspection PhpUndefinedFieldInspection */
        $C->z = 1;
        $C->import(['a' => 1]);
        $this->assertTrue($C->has('x'));
        $this->assertTrue($C->has('y'));
        $this->assertTrue($C->has('z'));
        $this->assertTrue($C->has('a'));
        $this->assertFalse($C->has('nope'));
        foreach ($C as $Field) {
            $this->assertInstanceOf('\Carcass\Field\Variant', $Field);
        }
    }

    public function testFormTemporaryDynamicFn() {
        $C = new Field\Set;
        $C->dynamic(function($C) {
            $C->x = 1;
        });
        $this->assertTrue($C->has('x'));
        $this->setExpectedException('LogicException');
        $C->y;
    }

    public function testCast() {
        $C = new Field\Set([
            'i' => ['scalar', 1],
        ]);
        $C->cast('i', 'variant');
        $this->assertInstanceOf('\Carcass\Field\Variant', $C->getField('i'));
        $C->cast('i', new Field\Id);
        $this->assertInstanceOf('\Carcass\Field\Id', $C->getField('i'));
    }

    public function testCastMulti() {
        $C = new Field\Set([
            'i' => ['scalar', 1],
            'j' => ['scalar', 1],
        ]);
        $C->castMulti([
            'i' => 'variant',
            'j' => new Field\Id
        ]);
        $this->assertInstanceOf('\Carcass\Field\Variant', $C->getField('i'));
        $this->assertInstanceOf('\Carcass\Field\Id', $C->getField('j'));
    }

}
