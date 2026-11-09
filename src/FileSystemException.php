<?php

namespace mannydmorales\FileSystem;

use Throwable;

/**
 * File System Exception Class
 *
 * @package mannydmorales\FileSystem
 * @file FileSystemException.php
 * @author Manny Morales <mannydmorales@gmail.com>
 * @copyright Manny D. Morales
 */
class FileSystemException extends \Exception {

    public ?string $path = null;
	
    /**
     * FileSystemException constructor.
     * 
     * @param string $message 
     * @param mixed $path 
     * @param int $code 
     * @param null|Throwable $previous 
     * @return void 
     */
	public function __construct ($message = '', $path = null, $code = 0, ?\Throwable $previous = null ) {
		$this->path = $path;
		parent::__construct($message, $code, $previous );
	}

    /**
     * Get the path associated with the exception.
     * 
     * @return string|null
     */
    public function getPath() {
        return $this->path;
    }

}