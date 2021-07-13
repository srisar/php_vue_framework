<?php


namespace App\Core\Http;


use App\Core\Resources\KeyGenerator;
use Exception;

class Uploader
{

    private array $supportedMimes;
    private bool $checkMime;
    private int $maxFileSize;
    private string $baseDir;
    private string $subDir;
    private string $absolutePath;
    private string $relativePath;
    private array $fileObject;


    /**
     * Creates an uploader instance with file object and optional parameters.
     * @param $file_object
     * @param int $max_file_size - when 0 is passed, max file size validation is ignored
     * @param string $sub_dir - directory inside the root upload directory
     * @param array $supported_mimes - when empty array is passed, mime validation is ignored
     * @throws Exception
     */
    public function __construct( $file_object, int $max_file_size = 0, string $sub_dir = "", array $supported_mimes = [] )
    {
        $this->baseDir = Storage::getUploadDir();
        $this->subDir = $sub_dir;
        $this->fileObject = $file_object;
        $this->maxFileSize = $max_file_size;

        $this->supportedMimes = $supported_mimes;
        if ( empty( $supported_mimes ) ) $this->checkMime = false;
        else $this->checkMime = true;

        if ( !$this->validateFileObject() ) throw new Exception( "Invalid file object given" );
        if ( !$this->validateMime() ) throw new Exception( "Invalid file type uploaded" );
        if ( !$this->validateUploadFileSize() ) throw new Exception( "File size exceeded the limit" );

    }

    /**
     * Move the uploaded file from temp location and store it with given name,
     * to avoid name conflicts, a unique id is appended to the file while saving.
     * @param string $newFileName
     * @param string $ext - without leading dot (Eg: jpg, png)
     * @return string
     * @throws Exception
     */
    public function storeUploadFile( string $newFileName, string $ext = "" ): string
    {

        if ( !$this->validateUploadPath() ) throw new Exception( "Failed to generate upload path" );

        $extension = $this->validateExtension( $ext );

        $uid = KeyGenerator::generateUID( prepend: $newFileName . "_" );

        $this->relativePath = $this->subDir . "/" . $uid . "." . $extension;
        $this->absolutePath = $this->baseDir . "/" . $this->relativePath;

        $result = move_uploaded_file( $this->fileObject["tmp_name"], $this->absolutePath );

        if ( $result ) return $this->relativePath;
        throw new Exception( "Failed to upload the file" );

    }

    /**
     * Checks if there is an extension in the uploaded file,
     * if user doesnt provide one while saving the uploaded file.
     * @throws Exception
     */
    private function validateExtension( $ext )
    {

        /* check if user passed extension is not empty, then return it */
        if ( !empty( $ext ) ) return $ext;

        /* try to get extension from uploaded file */
        $extArray = explode( ".", $this->fileObject["name"] );
        $ext = end( $extArray );
        if ( empty( $ext ) ) throw new Exception( "Invalid extension" );

        return $ext;
    }

    /**
     * Checks if uploaded file falls within the specified mime types.
     * If no supported mimes are provided, mime check will be ignored,
     * and true will be returned.
     * @return bool
     */
    private function validateMime(): bool
    {
        if ( !$this->checkMime ) return true;

        $fileMime = $this->fileObject["type"];

        return in_array( $fileMime, $this->supportedMimes );
    }

    /**
     * Checks if given file object is indeed a file object from
     * $_FILES array.
     * @return bool
     */
    private function validateFileObject(): bool
    {
        if (
            array_key_exists( "name", $this->fileObject ) ||
            array_key_exists( "tmp_name", $this->fileObject ) ||
            array_key_exists( "type", $this->fileObject ) ||
            array_key_exists( "size", $this->fileObject )
        ) return true;
        return false;
    }

    /**
     * Check if given path exist before moving the uploaded
     * file from temp location. If upload path is not available,
     * this method will attempt to create one.
     * @return bool
     */
    private function validateUploadPath(): bool
    {
        $path = $this->baseDir . "/" . $this->subDir;
        if ( file_exists( $path ) ) return true;
        return mkdir( $path );
    }

    /**
     * Returns absolute file path for the uploaded file.
     * This is the system path originating from the root partition
     * @return string
     */
    public function getAbsolutePath(): string
    {
        return $this->absolutePath;
    }

    /**
     * Returns the path relative to the uploads directory set by
     * Storage::setUploadDir() method.
     * @return string
     */
    public function getRelativePath(): string
    {
        return $this->relativePath;
    }

    /**
     * Check if uploaded file size is less than the given max size.
     * @throws Exception
     */
    public function validateUploadFileSize(): bool
    {
        if ( $this->maxFileSize == 0 ) {
            if ( $this->fileObject["size"] < self::getSupportedMaxUploadSize() ) return true;
            return false;
        }
        if ( $this->fileObject["size"] < $this->maxFileSize ) return true;
        return false;
    }


    /**
     * Returns the maximum upload size determined by the php
     * configuration
     * @return int
     */
    public static function getSupportedMaxUploadSize(): int
    {

        $max_upload_size = 1;
        $max_post_size = 1;

        $max_upload_string = ini_get( 'upload_max_filesize' );
        $max_post_string = ini_get( 'post_max_size' );

        $max_upload_unit = substr( $max_upload_string, -1 );
        $max_post_unit = substr( $max_post_string, -1 );

        if ( $max_upload_unit == "G" ) {
            $max_upload_size = (int)( $max_upload_string ) * 1024 * 1024 * 1024;
        } elseif ( $max_upload_unit == "M" ) {
            $max_upload_size = (int)( $max_upload_string ) * 1024 * 1024;
        }

        if ( $max_post_unit == "G" ) {
            $max_post_size = (int)( $max_post_string ) * 1024 * 1024 * 1024;
        } elseif ( $max_post_unit == "M" ) {
            $max_post_size = (int)( $max_post_string ) * 1024 * 1024;
        }

        return min( $max_upload_size, $max_post_size );

    }


}
