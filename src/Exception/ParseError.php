<?php
/**
 * Part of the "charcoal-dev/yaml" package.
 * @link https://github.com/charcoal-dev/yaml
 */

declare(strict_types=1);

namespace Charcoal\Yaml\Exception;

/**
 * Class ParseError
 * @package Charcoal\Yaml\Exception
 */
enum ParseError: int
{
    case FILE_NOT_FOUND = 0x64;
    case FILE_EXTENSION_ERROR = 0xc8;
    case FILE_NOT_READABLE = 0x12c;
    case FILE_READ_ERROR = 0x190;
    case FILE_IS_EMPTY = 0x1f4;
    case FILE_BAD_FORMAT = 0x258;
    case LINE_INDENTED_WITH_TABS = 0x44c;
    case BAD_IMPORTS_SEQUENCE = 0x4b0;
    case BAD_STRING_QUOTES = 0x514;
}
