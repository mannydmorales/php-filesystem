<?php

namespace mannydmorales\FileSystem;

/**
 * Directory Object Class
 *
 * @package mannydmorales\FileSystem
 * @file Directory.php
 * @author Manny Morales <mannydmorales@gmail.com>
 * @copyright Manny D. Morales
 */
class Directory {

    protected ?string $path = null;
    protected bool $autoload = true;

    /** @var array<File|Directory> $contents Array of File and Directory objects */
    protected array $contents = array();

    /** @var resource|null $ds Directory stream resource */
    protected $ds = null;

    /**
     * Construct
     * 
     * @param ?string $path The directory path
     * @param bool $autoload Autoload the directory. Defaults to true
     * 
     * @throws FileSystemException
     */
    public function __construct(?string $path, bool $autoload = true) {
        $this->autoload = $autoload;
        if($path !== null) {
            if(is_dir($path)) {
                $this->path = $path;
                if($autoload === true) {
                    $this->loadContents();
                }
                return;
            }
            throw new FileSystemException("Path is not a directory.", $path);
        }
    }

    /**
     * Get the full path (/opt/app/)
     * 
     * @return ?string Returns NULL if not set
     */
    public function getPath() : ?string {
        return $this->path;
    }

    /**
     * Get the name of the Directory
     * 
     * @return ?string
     */
    public function getName() : ?string {
        return \basename($this->path);
    }

    /**
     * Check if autoload is enabled and execute it
     * 
     * @return void
     */
    public function doAutoload() : void {
        if($this->autoload === true) {
            $this->loadContents();
        }
    }

    /**
     * Checks if the directory stream is open
     * 
     * @return bool
     */
    public function isOpen() : bool {
        if($this->ds === null || $this->ds === false) {
            return false;
        }
        return true;
    }

    /**
     * Opens a directory stream
     * 
     * @return self
     * @throws FileSystemException
     */
    public function open() : self {
        if($this->isOpen()) {
            return $this;
        }
        $this->ds = \opendir($this->path);
        if($this->ds === false) {
            throw new FileSystemException("Could not open directory", $this->path);
        }
        return $this;
    }

    /**
     * Closes directory stream
     * 
     * @return self
     */
    public function close() : self {
        if(!$this->isOpen()) {
            return $this;
        }
        \closedir($this->ds);
        $this->ds = null;
        return $this;
    }

    /**
     * Load the contents of the directory
     * 
     * @return void
     */
    public function loadContents() : void {
        $contents = array();
        $d = \scandir($this->path);
        foreach($d as $di) {
            if($di == "." || $di == "..") {
                continue;
            }
            if(is_dir($this->path."/".$di)) {
                $contents[] = new self($this->path.'/'.$di);
            } else {
                $contents[] = new File($this->path.'/'.$di, false);
            }
        }
        $this->contents = $contents;
    }

    /**
     * Get the contents of the directory
     * 
     * @param bool $forceReload Runs loadContents() before returning. Defaults to false
     * 
     * @return array
     */
    public function getContents(bool $forceReload = false) : array {
        if($forceReload === true) {
            $this->loadContents();
        }
        return $this->contents;
    }

    /**
     * Creates a new directory. If autoload is enabled the contents will be updated.
     * 
     * @param string $name The name of the new directory
     * @param bool $returnObject If true a new Directory object will be created and returned instead of a boolean. Defaults to false.
     * @param int $mode The permissions mode. Default is 0777
     * @param bool $recursive If true will create nested directories specified in $name
     * 
     * @return self|bool true (or new Directory object) on success or false on failure. 
     */
    public function create($name, $returnObject = false, int $mode = 0777, bool $recursive = false) : self|bool {
        if(mkdir($this->path."/".$name, $mode, $recursive)) {
            $this->doAutoload();
            if($returnObject === true) {
                return new self($this->path."/".$name);
            }
            return true;
        }
        return false;
    }

    /**
     * Deletes this Directory and all of it's contents.
     * 
     * @return void
     * @throws FileSystemException
     */
    public function delete() : void {
        if(!$this->isOpen()) {
            $this->open();
        }
        foreach($this->contents as $item) {
            if($item instanceof Directory) {
                $item->open()->delete();
            } elseif($item instanceof File) {
                $item->delete();
            } else {
                continue;
            }
        }
        $this->close();
        \rmdir($this->path);
    }

    /**
     * Copy This Directory
     * 
     * @param string $destination
     * 
     * @return self|false
     * @see System::copy()
     */
    public function copy(string $destination) : self|false {
        return System::copy($this, $destination);
    }

    /**
     * Change the mode of the directory
     * 
     * @param int $mode The new mode (e.g., 0755)
     * @return bool
     */
    public function chmod(int $mode) : bool {
        # TODO: Add recursive option
        return \chmod($this->path, $mode);
    }

    /**
     * Change the owner and group of the directory
     * 
     * @param string $user The new owner user
     * @param ?string $group The new group. If null only changes the user
     * @return bool
     */
    public function chown(string $user, ?string $group = null) : bool {
        # TODO: Add recursive option
        if($group === null) {
            return \chown($this->path, $user);
        }
        return \chown($this->path, $user) && $this->chgrp($group);
    }

    /**
     * Change the group of the directory
     * 
     * @param string $group The new group
     * @return bool
     */
    public function chgrp(string $group) : bool {
        # TODO: Add recursive option
        return \chgrp($this->path, $group);
    }

    /**
     * Destructor
     * 
     * @return void 
     */
    public function __destruct() {
        if($this->isOpen()) {
            $this->close();
        }
    }
}