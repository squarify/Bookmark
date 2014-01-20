<?php

/*
 * This file is part of the Squarify package.
 *
 * (c) Pedro Rodrigues <relvas.rodrigues@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Squarify\Component\Bookmark;

use Squarify\Component\Bookmark\Exception\ParseException;

/**
 * Bookmark offers convenience methods to load and dump Netscape Bookmark File Format HTML.
 *
 * @author Pedro Rodrigues <relvas.rodrigues@gmail.com>
 */
class Bookmark
{
    /**
     * Parses Netscape Bookmark File Format into a PHP array.
     *
     * The parse method, when supplied with a HTML stream (Netscape Bookmark File Format string),
     * will do its best to convert it into a PHP array.
     *
     *  Usage:
     *  <code>
     *   $array = Bookmark::parse(file_get_contents($filename));
     *   print_r($array);
     *  </code>
     *
     * @param string $input                       A Netscape Bookmark File Format HTML string
     * @param bool   $ignorePersonalToolbarFolder If we should ignore the personal toolbar bookmark folder
     * @param bool   $includeFolderTags           If we should include folter tags
     * @param bool   $useDateTimeObjects          If we should return \DateTime objects
     *
     * @return array The HTML converted to a PHP array
     *
     * @throws ParseException If the HTML is not valid
     */
    public static function parse($input, $ignorePersonalToolbarFolder = true, $includeFolderTags = true, $useDateObjects = true)
    {
        $bookmark = new Parser();

        try {
            return $bookmark->parse($input, $ignorePersonalToolbarFolder, $includeFolderTags, $useDateObjects);
        } catch (ParseException $e) {
            throw $e;
        }
    }
}
