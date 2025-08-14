<?php
/**
 * Part of the "charcoal-dev/yaml" package.
 * @link https://github.com/charcoal-dev/yaml
 */

declare(strict_types=1);

namespace Charcoal\Yaml\Parser;

use Charcoal\Yaml\Exception\ParseError;
use Charcoal\Yaml\Exception\YamlParseException;
use Charcoal\Yaml\Parser;

/**
 * Class Line
 * @package Charcoal\Yaml\Parser
 */
class Line
{
    public readonly int $len;
    public readonly int $indent;
    public ?string $key = null;
    public ?string $value = null;

    /**
     * @throws YamlParseException
     */
    public function __construct(
        Parser                 $parser,
        public readonly int    $num,
        public readonly string $raw
    )
    {
        if ($this->raw && $this->raw[0] === "\t") {
            throw new YamlParseException(ParseError::LINE_INDENTED_WITH_TABS, parseLine: $this);
        }

        $this->len = $parser->mbEncoding ? mb_strlen($this->raw, $parser->mbEncoding) : strlen($this->raw);
        $trimmedLen = $parser->mbEncoding ? mb_strlen(ltrim($this->raw), $parser->mbEncoding) : strlen(ltrim($this->raw));
        $this->indent = $this->len - $trimmedLen;

        if (!$this->raw || preg_match('/^\s*$/', $this->raw)) {
            return; // Blank line
        } elseif (preg_match('/^\s*#/', $this->raw)) {
            return; // Full line comment
        }

        // Clear any inline comment
        $line = trim(preg_split("/(#)(?=(?:[^\"']|[\"'][^\"']*[\"'])*$)/", $this->raw, 2)[0]);
        if ($line) {
            // Check if line has a key
            if (preg_match('/^\s*[\w\-.]+:/', $line)) {
                // Key exists, split into a key/value pair
                $line = explode(":", $line, 2);
                $this->key = trim($line[0]);
                /** @noinspection PhpCastIsUnnecessaryInspection */
                $this->value = trim(strval($line[1] ?? ""));
            } else {
                // Key doesn't exist, set entire line as value
                $this->key = null;
                $this->value = trim($line);
            }
        } else {
            $this->key = null;
            $this->value = null;
        }
    }
}
