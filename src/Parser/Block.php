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

namespace Charcoal\Yaml\Parser;

use Charcoal\Yaml\Exception\ParseError;
use Charcoal\Yaml\Exception\YamlParseException;
use Charcoal\Yaml\Parser;

/**
 * Class Block
 * @package Charcoal\Yaml\Parser
 */
class Block
{
    /** @var array */
    public array $lines = [];

    /**
     * @param \Charcoal\Yaml\Parser $parser
     * @param string $filePath
     * @param int $indent
     * @param string|null $key
     * @param string|null $type
     */
    public function __construct(
        private readonly Parser $parser,
        public readonly string  $filePath,
        public readonly int     $indent = 0,
        public readonly ?string $key = null,
        public readonly ?string $type = null
    )
    {
        if ($type && !in_array($type, [">", "|"])) {
            throw new \InvalidArgumentException('Invalid buffer type');
        }
    }

    /**
     * @param Line $line
     * @return Block
     */
    public function append(Line $line): static
    {
        $this->lines[] = $line;
        return $this;
    }

    /**
     * @param int $indent
     * @param string|null $key
     * @param string|null $type
     * @return Block
     */
    private function createSubBuffer(int $indent = 0, ?string $key = null, ?string $type = null): static
    {
        return new static($this->parser, $this->filePath, $indent, $key, $type);
    }

    /**
     * @return array|null
     * @throws \Charcoal\Yaml\Exception\YamlParseException
     */
    public function getParsed(): ?array
    {
        $parsed = [];
        /** @var null|Block $subBuffer */
        $subBuffer = null;

        /** @var Line $line */
        foreach ($this->lines as $line) {
            if (isset($subBuffer)) {
                if (!$line->key && !$line->value) {
                    $subBuffer->append($line);
                    continue;
                }

                if ($line->indent > $subBuffer->indent) {
                    $subBuffer->append($line);
                    continue;
                }

                $parsed[$subBuffer->key] = $subBuffer->getParsed();
                unset($subBuffer);
            }

            // No key, no value
            if (!$line->key && !$line->value) {
                continue; // Ignore empty line
            }

            // Has key but no value = assoc array
            if ($line->key && !$line->value) {
                $subBuffer = $this->createSubBuffer($line->indent, $line->key);
                continue;
            }

            // Has both key and a value
            if ($line->key && $line->value) {
                // Long string buffer
                if (in_array($line->value, [">", "|"])) {
                    $subBuffer = $this->createSubBuffer($line->indent, $line->key, $line->value);
                    continue;
                }

                // Set key/value pair
                $parsed[$line->key] = $this->getLineValue($line);
                continue;
            }

            // Has value but no key
            if (!$line->key && $line->value) {
                // Long strings buffer
                if (in_array($this->type, [">", "|"])) {
                    $parsed[] = $line->value;
                    continue;
                }

                // Sequences
                if ($line->value[0] === "-") {
                    $line->value = trim(substr($line->value, 1));
                    $value = $this->getLineValue($line);
                    if ($this->key === "imports") {
                        if (!is_string($value)) {
                            throw new YamlParseException(ParseError::BAD_IMPORTS_SEQUENCE, parseLine: $line);
                        }

                        $importPath = dirname($this->filePath) . DIRECTORY_SEPARATOR . trim($value, DIRECTORY_SEPARATOR);

                        try {
                            $value = $this->parser->getParsed($importPath);
                        } catch (YamlParseException $e) {
                            throw new YamlParseException(
                                $e->parseError,
                                $e->getMessage(),
                                null,
                                $e->filePath,
                                $e->parseLine,
                                $this->filePath
                            );
                        }
                    }

                    $parsed[] = $value;
                }
            }
        }

        // Check for any sub buffer at end of lines
        if (isset($subBuffer)) {
            $parsed[$subBuffer->key] = $subBuffer->getParsed();
        }

        // Empty arrays will return null
        if (!count($parsed)) {
            $parsed = null;
        }

        // Long string buffers
        if (is_array($parsed) && in_array($this->type, [">", "|"])) {
            $glue = $this->type === ">" ? " " : $this->parser->eolChar;
            $parsed = implode($glue, $parsed);
        }

        // Result cannot be empty if no-parent
        if (!$parsed && !$this->key) {
            throw new YamlParseException(ParseError::FILE_BAD_FORMAT);
        }

        // Merge imports
        $imports = $parsed["imports"] ?? null;
        if (is_array($imports)) {
            unset($parsed["imports"]);
            $imports[] = $parsed;
            $parsed = call_user_func_array("array_replace_recursive", $imports);
        }

        return $parsed;
    }

    /**
     * @param \Charcoal\Yaml\Parser\Line $line
     * @return int|float|string|bool|null
     * @throws \Charcoal\Yaml\Exception\YamlParseException
     */
    private function getLineValue(Line $line): int|float|string|bool|null
    {
        if (!$line->value) {
            return null;
        }

        $isQuoted = false;
        $value = $line->value;

        // Is quoted string?
        if (in_array($value[0], ["'", '"'])) {
            if (substr($value, -1) !== $value[0]) {
                throw new YamlParseException(ParseError::BAD_STRING_QUOTES, parseLine: $line);
            }

            $isQuoted = true;
            $value = substr($value, 1, -1);
        }

        // Is not quoted string, evaluate boolean or NULL values?
        if (!$isQuoted) {
            $lowercaseValue = strtolower($value);
            // Null Types
            if ($this->parser->evaluateNulls && in_array($lowercaseValue, ["~", "null"])) {
                return null;
            }

            // Evaluate Booleans?
            if ($this->parser->evaluateBooleans) {
                if (in_array($lowercaseValue, ["true", "false", "on", "off", "yes", "no"])) {
                    return in_array($lowercaseValue, ["true", "on", "yes"]);
                }
            }

            // Integers
            if (preg_match('/^(0|-?[1-9][0-9]*)$/', $value)) {
                return intval($value);
            }

            // Floats
            if (preg_match('/^(-?0\.[0-9]+|-?[1-9][0-9]*\.[0-9]+)$/', $value)) {
                return floatval($value);
            }
        }

        return $value;
    }
}
