<?php
/*
 * This file is a part of "charcoal-dev/yaml" package.
 * https://github.com/charcoal-dev/yaml
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/yaml/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Charcoal\Tests\Yaml;

use Charcoal\Yaml\Exception\ParseError;
use Charcoal\Yaml\Exception\YamlParseException;
use Charcoal\Yaml\Parser;
use PHPUnit\Framework\TestCase;

/**
 * Class ParserTest
 * @package Charcoal\Tests\Yaml
 */
class ParserTest extends TestCase
{
    /**
     * @return void
     * @throws \Charcoal\Yaml\Exception\YamlParseException
     */
    public function testParseSimple(): void
    {
        $ymlFile = __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "website.yml";

        $parser = new Parser();
        $website1 = $parser->getParsed($ymlFile);
        $this->assertIsArray($website1);
        $this->assertIsString($website1["domain"]);
        $this->assertEquals("domain.com", $website1["domain"]);
        $this->assertIsString($website1["title"]);
        $this->assertEquals("Website Title", $website1["title"]);
        $this->assertIsBool($website1["secure"]);
        $this->assertTrue($website1["secure"]);
        $this->assertNull($website1["email"]);
        unset($website2);

        $parser->evaluateBooleans = false;
        $website2 = $parser->getParsed($ymlFile);
        $this->assertIsNotBool($website2["secure"]);
        $this->assertIsString($website2["secure"]);
        $this->assertEquals("true", $website2["secure"]);
        $this->assertNull($website2["email"]);
        unset($website2);

        $parser->evaluateNulls = false;
        $website3 = $parser->getParsed($ymlFile);
        $this->assertNotNull($website3["email"]);
        $this->assertIsString($website3["email"]);
        $this->assertEquals("~", $website3["email"]);
        $this->assertIsNotBool($website3["secure"]);
        $this->assertIsString($website3["secure"]);
    }

    /**
     * @return void
     * @throws \Charcoal\Yaml\Exception\YamlParseException
     */
    public function testImports(): void
    {
        $parser = new Parser();
        $ymlEntryPoint = __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "config.yml";
        $config = $parser->getParsed($ymlEntryPoint);

        $this->assertIsArray($config);
        $this->assertArrayHasKey("databases", $config);
        $this->assertIsArray($config["databases"]);
        $this->assertEquals("primary", $config["databases"]["primary"]["name"] ?? null);
        $this->assertEquals("api_logs", $config["databases"]["api_logs"]["name"] ?? null);
        $this->assertEquals("10.0.20.3", $config["cache"]["host"] ?? null);
        $this->assertEquals(6379, $config["cache"]["port"] ?? null);
        $this->assertEquals("FirstByte.ae", $config["vendor"] ?? null);
        $this->assertEquals("Charcoal", $config["framework"] ?? null);
        $this->assertEquals("Website Title", $config["title"] ?? null);
        $this->assertTrue($config["secure"] ?? false);
        $this->assertNull($config["email"]);
    }

    /**
     * @return void
     */
    public function testExceptionInImport(): void
    {
        try {
            $parser = new Parser();
            $parser->getParsed(__DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "config2.yml");
            $this->throwException(new \RuntimeException('config2.yml file is bad, should have been failed'));
        } catch (YamlParseException $e) {
            $this->assertEquals(ParseError::BAD_STRING_QUOTES, $e->parseError);
            $this->assertEquals("bad.yml", basename($e->filePath));
            $this->assertEquals(2, $e->parseLine->num);
            $this->assertEquals("config2.yml", basename($e->importedInFilePath));
        }
    }

    /**
     * @return void
     * @throws \Charcoal\Yaml\Exception\YamlParseException
     */
    public function testFileNotFound(): void
    {
        $this->expectExceptionCode(ParseError::FILE_NOT_FOUND->value);
        (new Parser())
            ->getParsed(__DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "404.yml");
    }
}

