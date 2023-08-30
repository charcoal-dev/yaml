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

use Charcoal\Yaml\Exception\YamlCompilerException;

/**
 * Class Compiler
 * @package Charcoal\Yaml
 */
class Compiler
{
    /**
     * @param int $indent
     * @param string $eolChar
     */
    public function __construct(
        public readonly int    $indent = 2,
        public readonly string $eolChar = PHP_EOL
    )
    {
        if ($this->indent < 2 || $this->indent > 8) {
            throw new \OutOfRangeException('Out of range indentation value');
        }
    }

    /**
     * @param array $data
     * @return string
     * @throws \Charcoal\Yaml\Exception\YamlCompilerException
     */
    public function generate(array $data): string
    {
        $headers[] = "# This YAML file has been compiled using Charcoal YAML component";
        $headers[] = "# https://github.com/charcoal-dev/yaml";

        $compiled = $this->compile($data);
        return implode($this->eolChar, $headers) . str_repeat($this->eolChar, 2) . $compiled;
    }

    /**
     * @param array $input
     * @param string|null $parent
     * @param int $tier
     * @return string
     * @throws \Charcoal\Yaml\Exception\YamlCompilerException
     */
    private function compile(array $input, ?string $parent = null, int $tier = 0): string
    {
        $compiled = "";
        $indent = $this->indent * $tier;

        // Last value type
        // 1: Scalar, 0: Non-scalar
        $lastValueType = 1;

        // Iterate input
        foreach ($input as $key => $value) {
            // All tier-1 keys have to be string
            if ($tier === 1 && !is_string($key)) {
                throw new YamlCompilerException('All tier 1 keys must be string');
            }

            if (is_scalar($value) || is_null($value)) {
                // Value is scalar or NULL
                if ($lastValueType !== 1) {
                    // A blank line is last value type was not scalar
                    $compiled .= $this->eolChar;
                }

                // Current value type
                $lastValueType = 1; // This value is scalar or null

                // Necessary indents
                $compiled .= $this->indent($indent);

                // Set mapping key or sequence
                if (is_string($key)) {
                    $compiled .= sprintf('%s: ', $key);
                } else {
                    $compiled .= "- ";
                }

                // Value
                switch (gettype($value)) {
                    case "boolean":
                        $compiled .= $value === true ? "true" : "false";
                        break;
                    case "NULL":
                        $compiled .= "~";
                        break;
                    case "integer":
                    case "double":
                        $compiled .= $value;
                        break;
                    default:
                        // Definitely a string
                        if (strpos($value, $this->eolChar)) {
                            // String has line-breaks
                            $compiled .= "|" . $this->eolChar;
                            $lines = explode($this->eolChar, $value);
                            $subIndent = $this->indent(($indent + $this->indent));

                            foreach ($lines as $line) {
                                $compiled .= $subIndent;
                                $compiled .= $line . $this->eolChar;
                            }
                        } elseif (strlen($value) > 75) {
                            // Long string
                            $compiled .= ">" . $this->eolChar;
                            $lines = explode($this->eolChar, wordwrap($value, 75, $this->eolChar));
                            $subIndent = $this->indent(($indent + $this->indent));

                            foreach ($lines as $line) {
                                $compiled .= $subIndent;
                                $compiled .= $line . $this->eolChar;
                            }
                        } else {
                            // Simple string
                            $compiled .= $value;
                        }
                }

                $compiled .= $this->eolChar;
            } else {

                // Current value type
                $lastValueType = 0; // This value is Non-scalar

                if (is_object($value)) {
                    // Directly convert to an Array via JSON is the cleanest possible way
                    $value = json_decode(json_encode($value), true);
                }

                // Whether value was Array, or is now Array after conversion from object
                if (is_array($value)) {
                    $compiled .= $this->indent($indent);
                    $compiled .= sprintf('%s:%s', $key, $this->eolChar);
                    $compiled .= $this->compile($value, strval($key), $tier + 1);
                }
            }
        }

        if (!$compiled || ctype_space($compiled)) {
            throw new YamlCompilerException(sprintf('Failed to compile YAML for key "%s"', $parent));
        }

        $compiled .= $this->eolChar;

        return $compiled;
    }

    /**
     * @param int $count
     * @return string
     */
    private function indent(int $count): string
    {
        return str_repeat(" ", $count);
    }
}
