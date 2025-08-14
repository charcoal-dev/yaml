<?php
/**
 * Part of the "charcoal-dev/yaml" package.
 * @link https://github.com/charcoal-dev/yaml
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
