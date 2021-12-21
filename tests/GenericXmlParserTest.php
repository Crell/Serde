<?php

declare(strict_types=1);

namespace Crell\Serde;

use PHPUnit\Framework\TestCase;

class GenericXmlParserTest extends TestCase
{
    /**
     * @test
     * @dataProvider tagExamples()
     */
    public function tag_handling(string $xml, callable $test): void
    {
        $d = new GenericXmlParser();

        $root = $d->parseXml($xml);

        $test($root);
    }

    public function tagExamples(): iterable
    {
        yield 'point' => [
            'xml' => '<Point a="A"><x b="B" c="C">1</x><y>2</y><z>3</z></Point>',
            'test'=> static function (XmlElement $root): void {
                self::assertEquals('Point', $root->name);
                self::assertEquals('', $root->namespace);
                self::assertCount(1, $root->attributes);
                self::assertCount(3, $root->children);
                self::assertCount(2, $root->children[0]->attributes);
                self::assertEquals(1, $root->children[0]->content);
            }
        ];

        yield 'point_namespace' => [
            'xml' => '<beep:Point a="A"><boop:x b="B" c="C">1</boop:x><y>2</y><z>3</z></beep:Point>',
            'test'=> static function (XmlElement $root): void {
                self::assertEquals('Point', $root->name);
                self::assertEquals('beep', $root->namespace);
                self::assertCount(1, $root->attributes);
                self::assertCount(3, $root->children);
                self::assertCount(2, $root->children[0]->attributes);
                self::assertEquals(1, $root->children[0]->content);
                self::assertEquals('boop', $root->children[0]->namespace);
            }
        ];
    }
}
