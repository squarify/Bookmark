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
 * Bookmark parser
 *
 * @author Pedro Rodrigues <relvas.rodrigues@gmail.com>
 */
class Parser
{
    /**
     * Netscape Bookmark File Format DOCTYPE
     */
    const DOCTYPE = 'NETSCAPE-Bookmark-file-1';

    /**
     * DOMXPath
     *
     * @var \DOMXPath
     */
    private $xpath;

    /**
     * An array of bookmarks
     *
     * @var array
     */
    private $bookmarks = array();

    /**
     * The parent bookmark folder
     *
     * @var array
     */
    private $parentFolder = array();

    /**
     * The current folder
     *
     * @var array
     */
    private $currentFolder = array();

    /**
     * Folder depth
     *
     * @var array
     */
    private $folderDepth = array();

    /**
     * If we should use \DateTime objects
     *
     * @var bool
     */
    private $useDateTimeObjects = true;

    /**
     * If the Personal Toolbar Folder should be ignored
     *
     * @var bool
     */
    private $ignorePersonalToolbarFolder = true;

    /**
     * If tags should be included
     *
     * @var bool
     */
    private $includeFolderTags = true;

    /**
     * Constructor
     *
     * @param bool $useInternalErrors Use internal errors
     */
    public function __construct($useInternalErrors = true)
    {
        libxml_use_internal_errors($useInternalErrors);
    }

    /**
     * Check if doctype file is valid for parsing
     *
     * @param string $doctype Document Doctype
     *
     * @return boolean
     */
    public static function isValid($doctype)
    {
        return self::DOCTYPE === $doctype;
    }

    /**
     * Parses a Netscape Bookmark File Format HTML string to a PHP value.
     *
     * @param string $input                       A Netscape Bookmark File Format HTML string
     * @param bool   $ignorePersonalToolbarFolder If we should ignore the personal toolbar bookmark folder
     * @param bool   $includeFolderTags           If we should include folter tags
     * @param bool   $useDateTimeObjects          If we should return \DateTime objects
     *
     * @return mixed  A PHP value
     *
     * @throws ParseException If the HTML is not valid
     */
    public function parse($input, $ignorePersonalToolbarFolder = true, $includeFolderTags = true, $useDateTimeObjects = true)
    {
        $document = new \DOMDocument();
        $document->preserveWhiteSpace = false;

        if (false === $document->loadHTML($input)) {
            throw new ParseException('The HTML value does not appear to be valid Netscape Bookmark File Format HTML.');
        }

        if (false === self::isValid($document->doctype->name)) {
            throw new ParseException('The DOCTYPE does not appear to be a valid Netscape Bookmark File Format DOCTYPE.');
        }

        $this->xpath = new \DOMXPath($document);

        $this->ignorePersonalToolbarFolder = $ignorePersonalToolbarFolder;
        $this->includeFolderTags = $includeFolderTags;
        $this->useDateTimeObjects = $useDateTimeObjects;

        $this->traverse();

        return empty($this->bookmarks) ? null : $this->bookmarks;
    }

    /**
     * Traverses a DOMNode
     *
     * @param \DOMNode $node
     */
    private function traverse(\DOMNode $node = null)
    {
        $query = './*';

        $entries = $this->xpath->query($query, $node ?: null);

        foreach ($entries as $entry) {
            switch ($entry->nodeName) {
                case 'dl':
                    //echo '* Entering folder!!' . "\n";
                    $this->traverse($entry);
                    $this->closeFolder();
                    break;
                case 'a':
                    //echo '* This is a bookmark! ' . $entry->nodeValue . "\n";
                    $this->addBookmark($entry);
                    break;
                case 'h3':
                    //echo '* Adding folder! ' . $entry->nodeValue . "\n";
                    $this->addFolder($entry);
                    break;
                default:
                    if ($entry->hasChildNodes()) {
                        $this->traverse($entry);
                    }
            }
        }
    }

    /**
     * Add a folder from a \DOMNode
     *
     * @param \DOMNode $node
     */
    private function addFolder(\DOMNode $node)
    {
        $folder = array(
            'name' => $node->nodeValue,
        );

        $folder = array_merge($folder, $this->getAttributes($node));

        if (isset($folder['personal_toolbar_folder']) && $this->ignorePersonalToolbarFolder) {
            return;
        }

        $this->parentFolder = end($this->folderDepth);

        if (!empty($this->currentFolder)) {
            $folder['parent'] = $this->currentFolder;
        }

        array_push($this->folderDepth, $folder);

        $this->currentFolder = end($this->folderDepth);
    }

    /**
     * Close current folder
     */
    private function closeFolder()
    {
        if (1 >= count($this->folderDepth)) {
            $this->folderDepth = array();
            $this->currentFolder = $this->ignorePersonalToolbarFolder ? array() : $this->parentFolder;
        } else {
            unset($this->folderDepth[count($this->folderDepth) - 1]);

            $this->folderDepth = array_values($this->folderDepth);
            $this->currentFolder = end($this->folderDepth);
        }
    }

    /**
     * Add a bookmark from a \DOMNode
     *
     * @param \DOMNode $node
     */
    private function addBookmark(\DOMNode $node)
    {
        $bookmark = array(
            'title' => $node->nodeValue,
        );

        if (!empty($this->currentFolder)) {
            $bookmark['folder'] = $this->currentFolder;

            if ($this->includeFolderTags) {
                $tags = $this->getCurrentFolderTags($this->currentFolder);

                if (!empty($tags)) {
                    $bookmark['tags'] = $tags;
                }
            }
        }

        $bookmark = array_merge($bookmark, $this->getAttributes($node));

        $this->bookmarks[] = $bookmark;
    }

    /**
     * Get attributes of a \DOMNode
     *
     * @param \DOMNode $node
     * @return array
     */
    private function getAttributes(\DOMNode $node)
    {
        $attributes = array();

        $length = $node->attributes->length;

        for ($i = 0; $i < $length; ++$i) {
            $attributes[$node->attributes->item($i)->name] = $node->attributes->item($i)->value;
        }

        if ($this->useDateTimeObjects) {
            if (isset($attributes['add_date'])) {
                $addDate = new \DateTime();
                $addDate->setTimestamp($attributes['add_date']);

                $attributes['add_date'] = $addDate;
            }

            if (isset($attributes['last_modified'])) {
                $lastModified = new \DateTime();
                $lastModified->setTimestamp($attributes['last_modified']);

                $attributes['last_modified'] = $lastModified;
            }
        }

        return $attributes;
    }

    /**
     * Get current folder tags
     *
     * @return array
     */
    private function getCurrentFolderTags()
    {
        $tags = array();

        array_walk_recursive($this->currentFolder, function ($tag, $key) use (&$tags) {
            if ('name' === $key) {
                $tags[] = $tag;
            }
        });

        return $tags;
    }
}
