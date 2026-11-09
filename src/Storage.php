<?php

namespace mannydmorales\FileSystem;

/**
 * Storage Interface
 *
 * @package mannydmorales\FileSystem
 * @file Storage.php
 * @author Manny Morales <mannydmorales@gmail.com>
 * @copyright Manny D. Morales
 */
interface Storage {

    /** @var int Default Sort (no particular order) */
    public const SORT_NA    = SCANDIR_SORT_NONE;
    /** @var int Sort in Ascending Order */
    public const SORT_ASC   = SCANDIR_SORT_ASCENDING;
    /** @var int Sort in Descending Order */
    public const SORT_DESC  = SCANDIR_SORT_DESCENDING;

    /** @var int Hard Link */
    public const LINK_HARD  = 1;
    /** @var int Symlink */
    public const LINK_SYM   = 2;

    /**
     * Change Directory
     * 
     * @param string $path The name or path to change to
     * @return bool
     */
    public function cd(string $path) : bool;

    /**
     * Print Working Directory. Gets the current path
     * 
     * @return string|false Returns the path on success or false on failure
     */
    public function pwd() : string|false;

    /**
     * Make Directory in the current working directory
     * 
     * @param string $name
     * @return bool
     */
    public function mkdir(string $name) : bool;

    /**
     * Remove directory in the current working directory
     * 
     * @param string $name
     * @param bool $recursive If true deletes all files and subfolders recusively
     * @return bool
     */
    public function rmdir(string $name, bool $recursive = false) : bool;

    /**
     * List Directory Contents
     * 
     * @param ?int $filterType
     * @param int $sortBy
     */
    public function ls(?int $filterType = null, int $sortBy = self::SORT_NA) : array|false;

    /**
     * Add File to the current working directory
     * 
     * @param string $name
     * @param mixed $contents
     * @return bool 
     */
    public function addFile(string $name, mixed $contents) : bool;

    /**
     * Delete file in the current working directory
     * 
     * @param string $name
     * @return bool
     */
    public function rm(string $name) : bool;

    /**
     * Get the file contents from a file in the current working directory
     * 
     * @param string $name
     * @param int $bytes The bytes to get. 0 will return the entire file
     * @param int $offset Gets the bytes starting from this offset
     * @return string|false Returns the content or FALSE on failure
     */
    public function getFileContents(string $name, int $bytes = 0, int $offset = 0) : string|false;

    /**
     * Create a link in the current working directory
     * 
     * @param string $target The target of the link
     * @param string $name The name of the link 
     * @param int $linkType The type of link to make (Hard Link or Symlink)
     * @return bool
     */
    public function ln(string $target, string $name, int $linkType = self::LINK_HARD) : bool;

}