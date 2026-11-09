<?php

namespace mannydmorales\FileSystem;

/**
 * File System (within the operating system) Operations Class
 *
 * @package mannydmorales\FileSystem
 * @file System.php
 * @author Manny Morales <mannydmorales@gmail.com>
 * @copyright Manny D. Morales
 */
class System {

    public const LINKTYPE_HARD  = 0;
    public const LINKTYPE_SYM   = 1;

    /**
     * Rename a File or Directory (using the system rename function)
     * 
     * @param File|Directory $original The original file or directory object
     * @param string $to The new name or path
     * @return File|Directory|false Returns the new File or Directory object on success or false on failure
     */
    public static function rename(File|Directory $original, $to) : File|Directory|false {
        $open = $original->isOpen();
        $original->close();
        if(\rename($original->getPath(), $to)) {
            if($original instanceof Directory) {
                return new Directory($to, $open);
            } else {
                return new File($to, $open);
            }
        }
        return false;
    }

    /**
     * Move a File or Directory (using the system rename function)
     * 
     * @param File|Directory $source The source file or directory object
     * @param string $destination The new name or path
     * @return File|Directory|false Returns the new File or Directory object on success or false on failure
     * @see System::rename()
     */
    public static function move(File|Directory $source, $destination) : File|Directory|false {
        return self::rename($source, $destination);
    }

    /**
     * Delete a File or Directory
     * 
     * @param File|Directory $source The source file or directory object
     * @return void
     */
    public static function delete(File|Directory $source) : void {
        if($source instanceof Directory) {
            $source->open()->delete();
        } elseif($source instanceof File) {
            $source->delete();
        }
    }

    /**
     * Copy a File or Directory
     * 
     * @param File|Directory $original The original file or directory object
     * @param string $destination The destination path or name
     * @return File|Directory|false Returns the new File or Directory object on success or false on failure
     */
    public static function copy(File|Directory $original, $destination) : File|Directory|false {
        if($original instanceof File) {
            if(\copy($original->getPath(), $destination)) {
                return new File($destination, false);
            }
            return false;
        } elseif($original instanceof Directory) {
            if(\mkdir($destination)) {
                $dest = new Directory($destination, false);
                foreach($original->open()->getContents(true) as $conts) {
                    if($conts instanceof Directory) {
                        self::copy($conts, $dest."/".$conts->getName());
                    } elseif($conts instanceof File) {
                        \copy($conts->getPath(), $dest."/".$conts->getName());
                    }
                }
                return new Directory($destination, false);
            }
            return false;
        }
        return false;
    }

    /**
     * Create a link (hard or symbolic) to a File or Directory (using the system link or symlink function)
     * 
     * @param File|Directory $target The target file or directory object
     * @param string $link The name of the link to create
     * @param int $type The type of link to create (System::LINKTYPE_HARD or System::LINKTYPE_SYM)
     * @return bool Returns true on success or false on failure
     */
    public static function link(File|Directory $target, string $link, int $type) : bool {
        switch($type) {
            case self::LINKTYPE_HARD:
                return \link($target->getPath(), $link);
                break;
            case self::LINKTYPE_SYM:
                return \symlink($target->getPath(), $link);
                break;
        }
        return false;
    }

    /**
     * Create a hard link to a File or Directory
     * 
     * @param File|Directory $target The target file or directory object
     * @param string $link The name of the link to create
     * @return bool Returns true on success or false on failure
     * @see System::link()
     */
    public static function hardlink(File|Directory $target, $link) {
        return self::link($target, $link, self::LINKTYPE_HARD);
    }

    /**
     * Create a symbolic link to a File or Directory
     * 
     * @param File|Directory $target The target file or directory object
     * @param string $link The name of the link to create
     * @return bool Returns true on success or false on failure
     * @see System::link()
     */
    public static function symlink(File|Directory $target, $link) {
        return self::link($target, $link, self::LINKTYPE_SYM);
    }

}