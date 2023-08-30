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

namespace Charcoal\Yaml\Exception;

use Charcoal\Yaml\Parser\Line;

/**
 * Class YamlParseException
 * @package Charcoal\Yaml\Exception
 */
class YamlParseException extends \Exception
{
    /**
     * @param \Charcoal\Yaml\Exception\ParseError $parseError
     * @param string $message
     * @param \Throwable|null $previous
     * @param string|null $filePath
     * @param \Charcoal\Yaml\Parser\Line|null $parseLine
     * @param string|null $importedInFilePath
     */
    public function __construct(
        public readonly ParseError $parseError,
        string                     $message = "",
        ?\Throwable                $previous = null,
        public readonly ?string    $filePath = null,
        public readonly ?Line      $parseLine = null,
        public readonly ?string    $importedInFilePath = null,
    )
    {
        parent::__construct($message, $this->parseError->value, $previous);
    }
}
