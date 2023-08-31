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

namespace Charcoal\Yaml;

use Charcoal\Yaml\Exception\ParseError;
use Charcoal\Yaml\Exception\YamlParseException;
use Charcoal\Yaml\Parser\Block;
use Charcoal\Yaml\Parser\Line;

/**
 * Class Parser
 * @package Charcoal\Yaml
 */
class Parser
{
    /**
     * @param string|null $mbEncoding
     * @param bool $evaluateBooleans
     * @param bool $evaluateNulls
     * @param string $eolChar
     */
    public function __construct(
        public readonly ?string $mbEncoding = null,
        public bool             $evaluateBooleans = true,
        public bool             $evaluateNulls = true,
        public string           $eolChar = PHP_EOL
    )
    {

        if ($this->mbEncoding) {
            if (!in_array($this->mbEncoding, mb_list_encodings())) {
                throw new \OutOfBoundsException('Not a valid multi-byte encoding');
            }
        }
    }

    /**
     * @param string $filePath
     * @return array
     * @throws \Charcoal\Yaml\Exception\YamlParseException
     */
    public function getParsed(string $filePath): array
    {
        $realPath = realpath($filePath);
        if (!$realPath) {
            throw new YamlParseException(ParseError::FILE_NOT_FOUND, filePath: $filePath);
        }

        if (!preg_match('/[\w\-]+\.(yaml|yml)$/', $realPath)) {
            throw new YamlParseException(ParseError::FILE_EXTENSION_ERROR, filePath: $realPath);
        }

        if (!is_readable($realPath)) {
            throw new YamlParseException(ParseError::FILE_NOT_READABLE, filePath: $realPath);
        }

        $lines = file_get_contents($realPath);
        if ($lines === false) {
            throw new YamlParseException(ParseError::FILE_READ_ERROR, filePath: $realPath);
        } elseif (!$lines) {
            throw new YamlParseException(ParseError::FILE_IS_EMPTY, filePath: $realPath);
        }

        try {
            $lines = preg_split("/\r\n|\n|\r/", $lines);
            $buffer = new Block($this, $realPath);
            $num = 1;
            foreach ($lines as $line) {
                $line = new Line($this, $num, $line);
                $buffer->append($line);
                $num++;
            }

            return $buffer->getParsed();
        } catch (YamlParseException $e) {
            throw new YamlParseException(
                $e->parseError,
                $e->getMessage(),
                filePath: $e->filePath ?? $realPath,
                parseLine: $e->parseLine,
                importedInFilePath: $e->importedInFilePath
            );
        }
    }
}
