<?php

namespace mannydmorales\FileSystem;

/**
 * Local Storage Class
 *
 * @package mannydmorales\FileSystem
 * @file LocalStorage.php
 * @author Manny Morales <mannydmorales@gmail.com>
 * @copyright Manny D. Morales
 */
class LocalStorage implements Storage {

    protected string $currentDirectory;

    /**
     * Create a new Local Storage Object
     * 
     * @param string $initialDirectory The initial directory to start in
     */
    public function __construct(string $initialDirectory) {
        $this->currentDirectory = $initialDirectory;
    }

    /**
     * Print Working Directory. Gets the current path
     * 
     * @return string|false Returns the path on success or false on failure
     */
    public function pwd() : string|false {
        if($this->currentDirectory === null) {
            return false;
        }
        return $this->currentDirectory;
    }

    /**
     * Change Directory
     * 
     * @param string $path The name or path to change to
     * @return bool
     */
    public function cd(string $path) : bool {
        $path = trim($path);
        if(strlen($path) < 1) {
            return false;
        }
        if(substr($path, 0, 1) == "/") {
            $this->currentDirectory = $path;
            return true;
        }
        $epath = explode("/", $path);
        $cpath = explode("/", $this->currentDirectory);
        foreach($epath as $p) {
            if($p == ".") {
                continue;
            }
            if($p == "..") {
                array_pop($cpath);
                continue;
            }
            $cpath[] = $p;
        }
        $this->currentDirectory = implode("/", $cpath);
        return true;
    }

    /**
     * List Directory Contents
     * 
     * @param ?int $filterType Filter by type. Use FSObject::OBJ_FILE or FSObject::OBJ_DIR. Null for no filter
     * @param int $sortBy
     * @return array<string,FSObject>|false Returns an array of FSObject on success or false on failure
     */
    public function ls(?int $filterType = null, int $sortBy = self::SORT_NA) : array|false {
        $contents = array();
        $d = scandir($this->currentDirectory, $sortBy);
        foreach($d as $di) {
            if($di == "." || $di == "..") {
                continue;
            }
            $obj = new FSObject();
            $obj->name = $di;
            $obj->path = $this->currentDirectory."/".$di;
            if(is_dir($obj->path)) {
                $obj->type = FSObject::OBJ_DIR;
            } else {
                $obj->type = FSObject::OBJ_FILE;
            }
            if(!is_null($filterType) && ($obj->type !== $filterType)) {
                continue;
            }
            $contents[$obj->name] = $obj;
        }
        return $contents;
    }

    /**
     * Alias of ls
     * 
     * @see ls()
     */
    public function list(?int $filterType = null, int $sortBy = self::SORT_NA) : array|false {
        return $this->ls($filterType, $sortBy);
    }

    /**
     * Make Directory in the current working directory
     * 
     * @param string $name
     * @return bool
     */
    public function mkdir(string $name): bool {
        return \mkdir($this->currentDirectory."/".$name);
    }

    /**
     * Remove directory in the current working directory
     * 
     * @param string $name
     * @param bool $recursive If true deletes all files and subfolders recusively
     * @return bool
     */
    public function rmdir(string $name, bool $recursive = false): bool {
        if($recursive === false) {
            return \rmdir($this->currentDirectory."/".$name);
        }
        try {
            $dir = new Directory($this->currentDirectory.'/'.$name);
            $dir->open();
            $dir->delete();
            return true;
        } catch(FileSystemException $de) {
            return false;
        }
    }

    /**
     * Adds a file to the current working directory
     * 
     * @param string $name The name of the file to create
     * @param mixed $contents The contents of the file
     * @return bool Returns true on success or false on failure
     */
    public function addFile(string $name, mixed $contents) : bool {
        return file_put_contents($this->currentDirectory."/".$name, $contents) !== false;
    }

    /**
     * Deletes a file in the current working directory
     * 
     * @param string $name The name of the file to delete
     * @return bool Returns true on success or false on failure
     */
    public function rm(string $name) : bool {
        return unlink($this->currentDirectory."/".$name);
    }

    /**
     * Get the file contents from a file in the current working directory
     * 
     * @param string $name The name of the file to get the contents from
     * @param int $bytes Gets this many bytes. 0 means all bytes.
     * @param int $offset Gets the bytes starting from this offset
     * @return string|false Returns the content or FALSE on failure
     */
    public function getFileContents(string $name, int $bytes = 0, int $offset = 0) : string|false {
        return file_get_contents($this->currentDirectory."/".$name, false, null, $offset, $bytes > 0 ? $bytes : null);
    }

    /**
     * Create a link in the current working directory
     * 
     * @param string $target The target of the link
     * @param string $name The name of the link to create
     * @param int $linkType The type of link to create. Use Storage::LINK_HARD or Storage::LINK_SYM
     * @return bool Returns true on success or false on failure
     */
    public function ln(string $target, string $name, int $linkType = self::LINK_HARD): bool {
        switch($linkType) {
            case self::LINK_HARD:
                return link($target, $this->currentDirectory."/".$name);
            case self::LINK_SYM:
                return symlink($target, $this->currentDirectory."/".$name);
        }
        return false;
    }

}