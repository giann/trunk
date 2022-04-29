<?php

declare(strict_types=1);

use Giann\Trunk\Trunk;
use PHPUnit\Framework\TestCase;

final class Person
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
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
    }
}
