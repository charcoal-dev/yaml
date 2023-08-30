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
