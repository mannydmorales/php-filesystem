<?php

namespace mannydmorales\FileSystem;

/**
 * File Object Class
 *
 * @package mannydmorales\FileSystem
 * @file File.php
 * @author Manny Morales <mannydmorales@gmail.com>
 * @copyright Manny D. Morales
 */
class File {

    public const TYPE_TEMP      = 0;
    public const TYPE_FILE      = 1;
    public const TYPE_STREAM    = 2;

    public const MODE_READ          = 'r';
    public const MODE_WRITE         = 'w';
    public const MODE_READWRITE     = 'r+';
    public const MODE_APPEND        = 'a';
    public const MODE_READAPPEND    = 'a+';

    public const LOCK_READ      = LOCK_SH;
    public const LOCK_WRITE     = LOCK_EX;
    public const LOCK_NOBLOCK   = LOCK_EX | LOCK_NB;

    protected ?string $path     = null;
    protected int $type         = self::TYPE_FILE;
    protected bool $autoOpen    = false;
    protected string $openMode  = self::MODE_READ;

    /** @var resource|null $fs File stream resource */
    protected $fs = null;

    /**
     * Construct
     * 
     * @param ?string the full file path (/opt/app/data.log)
     * @param bool Auto open file stream. Defaults to true.
     * @param string Auto open mode. Defaults to read
     */
    public function __construct(?string $path = null, bool $autoOpen = true, string $autoOpenMode = self::MODE_READ) {
        $this->path = $path;
        $this->autoOpen = $autoOpen;
        if($this->path !== null && $this->autoOpen === true) {
            $this->openMode = $autoOpenMode;
            return  $this->open();
        }
    }

    /**
     * Get the full file path (/opt/app/data.log)
     * 
     * @return ?string Returns NULL if not set
     */
    public function getPath() : ?string {
        return $this->path;
    }

    /**
     * Get the name of the File
     * 
     * @return ?string
     */
    public function getName() : ?string {
        return basename($this->path);
    }

    /**
     * Get a file size
     * 
     * @param string $file The file to get the size of
     * @param bool $formatted Return the value as a human readable format if true or the bytes if false. Defaults to false
     * @param int $decimals The number of decimal places if formatted is set to true. Defaults to 2
     * @return int|string
     */
    public static function fileSize(string $file, bool $formatted = false, int $decimals = 2) : int|string {
        $filesize = filesize($file);
        if($formatted === false) {
            return $filesize;
        }
        $sz = 'BKMGTP';
        $factor = floor((strlen($filesize) - 1) / 3);
        return sprintf("%.{$decimals}f", $filesize / pow(1024, $factor)) . @$sz[$factor];
    }

    /**
     * Get the file size of this file
     * 
     * @param bool $formatted Return the value as a human readable format if true or the bytes if false. Defaults to false
     * @param int $decimals The number of decimal places if formatted is set to true. Defaults to 2
     * @return int|string
     */
    public function getFileSize(bool $formatted = false, int $decimals = 2) : int|string {
        return self::fileSize($this->path, $formatted, $decimals);
    }

    /**
     * Generate a temp file. If auto open is enabled will open the file stream
     * 
     * @param ?string $tmpDir The temp dir to store the file. Defaults to the system temp dir.
     * @param string $prefix The prefix of the file name generated. Defaults to mdm
     * @return self
     */
    public function createTempFile(?string $tmpDir = null, string $prefix = 'mdm') : self {
        if($tmpDir === null) {
            $tmpDir = \sys_get_temp_dir();
        }
        $this->path = \tempnam($tmpDir, $prefix);
        $this->openMode = self::MODE_APPEND;
        if($this->autoOpen === true) {
            return $this->open();
        }
        return $this;
    }

    /**
     * Opens a file stream
     * 
     * @see fopen()
     * @return self
     */
    public function open() : self {
        if($this->isOpen()) {
            return $this;
        }
        $this->fs = fopen($this->path, $this->openMode);
        return $this;
    }

    /**
     * Checks if a file stream is open
     * 
     * @return bool
     */
    public function isOpen() : bool {
        if($this->fs === null || $this->fs === false) {
            return false;
        }
        return true;
    }
    
    /**
     * Closes file stream
     * 
     * @see fclose()
     * @return self
     */
    public function close() : self {
        if(!$this->isOpen()) {
            return $this;
        }
        fclose($this->fs);
        $this->fs = null;
        return $this;
    }

    /**
     * Sets a file lock.
     * 
     * @param int $operation Set the lock type
     * @param bool $throw If true, on error throw an exception instead of false
     * @return bool
     * @throws FileSystemException If file is not open or if $throw is set and cannot lock file.
     */
    public function lock(int $operation = self::LOCK_WRITE, bool $throw = false) : bool {
        if(!$this->isOpen()) {
            throw new FileSystemException("File not open", $this->path);
        }
        if(flock($this->fs, $operation)) {
            return true;
        }
        if($throw === true) {
            throw new FileSystemException("Could not lock file", $this->path);
        }
        return false;
    }

    /**
     * Clears file lock
     * 
     * @param bool $throw If true, on error throw an exception instead of false
     * @return bool
     * @throws FileSystemException If file is not open or if $throw is set and cannot lock file.
     */
    public function unlock(bool $throw = false) : bool {
        if(!$this->isOpen()) {
            throw new FileSystemException("File not open", $this->path);
        }
        if(flock($this->fs, LOCK_UN)) {
            return true;
        }
        if($throw === true) {
            throw new FileSystemException("Could not release lock on file", $this->path);
        }
        return false;
    }

    /**
     * Get the contents of the file. Returns file_get_contents of the active file
     * 
     * @see file_get_contents()
     * @return string|false 
     */
    public function getContents() : string|false {
        return file_get_contents($this->path);
    }

    /**
     * Reads the file stream to the length provided. Return fread of the active file
     * 
     * @see fread()
     * @param int $length
     * @return string|false
     */
    public function read(int $length) : string|false {
        if(!$this->isOpen()) {
            return false;
        }
        return fread($this->fs, $length);
    }

    /**
     * Writes the contents of data to the file stream
     * 
     * @param string $data The string that is to be written
     * @param ?int $length If length is an int, writing will stop after length bytes have been written or the end of data is reached, whichever comes first.
     * @return int|false Returns the number of bytes written or false on failure
     * @throws FileSystemException If file is not open
     */
    public function write(string $data, ?int $length = null) : int|false {
        if(!$this->isOpen()) {
            throw new FileSystemException("File not open for writing", $this->path);
        }
        return fwrite($this->fs, $data, $length);
    }

    /**
     * Gets the permissions of a file in the unix style of drw-r--r--
     * 
     * @param string $file 
     * @return string
     */
    public static function displayPermissions(string $file) : string {
        $perms = fileperms($file);
        $out = "";
        switch($perms) {
            case 0xC000: // socket
                $out = 's';
                break;
            case 0xA000: // symbolic link
                $out = 'l';
                break;
            case 0x8000: // regular
                $out = 'r';
                break;
            case 0x6000: // block special
                $out = 'b';
                break;
            case 0x4000: // directory
                $out = 'd';
                break;
            case 0x2000: // character special
                $out = 'c';
                break;
            case 0x1000: // FIFO pipe
                $out = 'p';
                break;
            default: // unknown
                $out = 'u';
        }
        // Owner
        $out .= (($perms & 0x0100) ? 'r' : '-');
        $out .= (($perms & 0x0080) ? 'w' : '-');
        $out .= (($perms & 0x0040) ?
                    (($perms & 0x0800) ? 's' : 'x' ) :
                    (($perms & 0x0800) ? 'S' : '-'));

        // Group
        $out .= (($perms & 0x0020) ? 'r' : '-');
        $out .= (($perms & 0x0010) ? 'w' : '-');
        $out .= (($perms & 0x0008) ?
                    (($perms & 0x0400) ? 's' : 'x' ) :
                    (($perms & 0x0400) ? 'S' : '-'));

        // World
        $out .= (($perms & 0x0004) ? 'r' : '-');
        $out .= (($perms & 0x0002) ? 'w' : '-');
        $out .= (($perms & 0x0001) ?
                    (($perms & 0x0200) ? 't' : 'x' ) :
                    (($perms & 0x0200) ? 'T' : '-'));
        return $out;
    }

    /** 
     * Get the permission of a file
     * 
     * @param bool $formatted If false will return the octal format (0777). If true will return unix string format (drwxrwxrwx). Defaults to False.
     * @return string
     */
    public function permissions($formatted = false) : string {
        if($formatted === true) {
            return self::displayPermissions($this->path);
        }
        return substr(sprintf('%o', fileperms($this->path)), -4);
    }

    /**
     * Get the last access time as a unix timestamp
     * 
     * @return int|false Unix timestamp or false on failure
     */
    public function lastAccessTime() : int|false {
        return fileatime($this->path);
    }

    /**
     * Get the last modified time as a unix timestamp
     * 
     * @return int|false Unix timestamp or false on failure
     */
    public function lastModifiedTime() : int|false {
        return filemtime($this->path);
    }

    /**
     * Delete this file
     * 
     * @return void
     */
    public function delete() : void {
        if($this->isOpen()) {
            $this->close();
        }
        unlink($this->path);
    }

    /**
     * Copy this File
     * 
     * @param string $destination
     * 
     * @return self|bool
     */
    public function copy(string $destination) : self|bool {
        return System::copy($this, $destination);
    }

    /**
     * Change the mode of the file
     * 
     * @param int $mode The new mode (e.g., 0755)
     * @return bool
     */
    public function chmod(int $mode) : bool {
        return \chmod($this->path, $mode);
    }

    /**
     * Change the owner and group of the file
     * 
     * @param string $user The new owner user
     * @param ?string $group The new group. If null only changes the user
     * @return bool
     */
    public function chown(string $user, ?string $group = null) : bool {
        if($group === null) {
            return \chown($this->path, $user);
        }
        return \chown($this->path, $user) && $this->chgrp($group);
    }

    /**
     * Change the group of the file
     * 
     * @param string $group The new group
     * @return bool
     */
    public function chgrp(string $group) : bool {
        return \chgrp($this->path, $group);
    }

}