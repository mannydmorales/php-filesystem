<?php

namespace mannydmorales\FileSystem;

/**
 * File System Object Class
 *
 * @package mannydmorales\FileSystem
 * @file FSObject.php
 * @author Manny Morales <mannydmorales@gmail.com>
 * @copyright Manny D. Morales
 */
class FSObject {

    /** @var int Object is a File */
    public const OBJ_FILE    = 1;
    /** @var int Object is a Directory */
    public const OBJ_DIR     = 2;

    /** @var string Name of object */
    public string $name;

    /** @var string Path of Object */
    public string $path;

    /** @var int Object type */
    public int $type;

}