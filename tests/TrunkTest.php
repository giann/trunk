<?php

declare(strict_types=1);

use Giann\Trunk\Trunk;
use PHPUnit\Framework\TestCase;

final class Person
{
    public string $name;
    public int $age;

    public function __construct(string $name, int $age = 30)
    {
        $this->name = $name;
        $this->age = $age;
    }
}

final class TrunkTest extends TestCase
{
    public function testSimpleTrunk(): void
    {
        $data = [
            'astring' => 'hello world',
            'anint' => 12,
            'afloat' => 12.12,
            'alist' => [1, 2, 3, 'hello', 'world'],
            'listofints' => [1, 2, 3, 4],
            'listoffloats' => [1.2, 2.3, 4.5],
            'listofbools' => [true, false, true],
            'listofstrings' => ['one', 'two', 'three'],
            'amap' => [
                'hello' => 'world',
                'yo' => 10
            ],
            'anobject' => new Person('joe'),
            'listofobject' => [new Person('joe'), new Person('john')],
            'mapofobject' => [
                'joe' => new Person('joe'),
                'john' => new Person('john'),
            ],
            'transform' => 'joe',
            'transformlist' => ['joe', 'john'],
            'transformmap' => [
                'joe' => 'joe',
                'john' => 'john,'
            ]
        ];

        $trunk = new Trunk($data);

        $this->assertTrue($trunk['astring'] instanceof Trunk);
        $this->assertEquals($trunk['astring']->string(), 'hello world');

        $this->assertEquals($trunk['anint']->int(), 12);

        $this->assertEquals($trunk['alist']->listValue()[3]->string(), 'hello');

        $this->assertEquals($trunk['amap']['hello']->string(), 'world');

        $this->assertEquals($trunk['amap']['hello']['doesnexists']->data, null);

        $this->assertTrue($trunk['anobject']->ofClass(Person::class) instanceof Person);
        $this->assertEquals($trunk['anobject']->ofClass(Person::class)->name, 'joe');

        $this->assertTrue($trunk['listofobject']->listOfClass(Person::class)[0] instanceof Person);
        $this->assertEquals($trunk['listofobject']->listOfClass(Person::class)[0]->name, 'joe');

        $this->assertTrue($trunk['mapofobject']->mapOfClass(Person::class)['joe'] instanceof Person);
        $this->assertEquals($trunk['mapofobject']->mapOfClass(Person::class)['joe']->name, 'joe');

        $this->assertTrue(
            $trunk['transformlist']
                ->listOfClass(
                    Person::class,
                    fn ($el) => new Person($el)
                )[0] instanceof Person
        );

        $this->assertTrue(
            $trunk['transformmap']
                ->mapOfClass(
                    Person::class,
                    fn ($el) => new Person($el)
                )['joe'] instanceof Person
        );

        $this->assertEquals(count($data['listofints']), count($trunk['listofints']->listOfIntValue()));
        $this->assertIsInt($trunk['listofints']->listOfIntValue()[0]);
        $this->assertEquals(count($data['listoffloats']), count($trunk['listoffloats']->listOfFloatValue()));
        $this->assertIsFloat($trunk['listoffloats']->listOfFloatValue()[0]);
        $this->assertEquals(count($data['listofbools']), count($trunk['listofbools']->listOfBoolValue()));
        $this->assertIsBool($trunk['listofbools']->listOfBoolValue()[0]);
        $this->assertEquals(count($data['listofstrings']), count($trunk['listofstrings']->listOfStringValue()));
        $this->assertIsString($trunk['listofstrings']->listOfStringValue()[0]);
    }

    public function testIterator(): void
    {
        $data = [
            1, 2, 3, 4
        ];
        $trunk = new Trunk($data);
        foreach ($trunk as $key => $value) {
            $this->assertTrue($value instanceof Trunk);
            $this->assertEquals($value->int(), $data[$key]);
        };

        $data = new Person('joe', 45);
        $trunk = new Trunk($data);
        $result = [];
        foreach ($trunk as $key => $value) {
            $this->assertTrue($value instanceof Trunk);

            $result[$key] = $value;
        };

        $this->assertEquals($result['name']->string(), 'joe');
        $this->assertEquals($result['age']->int(), 45);
    }
}
