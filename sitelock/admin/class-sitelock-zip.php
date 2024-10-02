<?php
/**
 * @package     sitelocksecurity
 * @copyright   Copyright (C) 2019 SiteLock, LLC
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

/**
 * Zip functions - copied and adapted from SL WP plugin
 *
 * @package    Sitelock
 * @since      0.0.26
 */
class Sitelock_Zip {

    const ZIP_FOLDER = 'smart_zips';

    //path to zip file
    public $zip_location;
    
    //Encryption key for zip files
    public $encrypt_key;
    
    //openssl cipher method 
    public $cipher;
    
    //openssl mode
    public $mode;
    
    //openssl iv
    public $iv;
    
    /**
     * Initialize the class and set its properties.
     *
     * @since 0.0.26
     */
    public function __construct() {

        $this->zip_location = str_replace( '/admin', '', plugin_dir_path( __FILE__ ) ) . self::ZIP_FOLDER;

        $this->encrypt_key    = '';                 # updated after post_smart_single_scan_init
        $this->cipher         = '';                 # updated after post_smart_single_scan_init
        $this->mode           = OPENSSL_RAW_DATA;
        $this->iv             = '';                 # updated after post_smart_single_scan_init
        $this->new_zip_size   = 0;
        $this->num_files      = 0;
        
    }
    
    
    /**
     * Get the file size of the new zip created
     *
     * @since   0.0.26
     * @param   string  $file   The full directory path to the file
     */
    public function get_zip_size( $file ) {
        $this->new_zip_size = filesize( $file );
    }
    
    
    /**
     * Archive Files
     *
     * @since   0.0.26
     * @param   array     $files
     * @param   string    $target
     * @param   string    $root
     * @param   int       $cap
     */
    public function archive_files( array $files, &$target, $root = null, $cap = 0 ) {
        
        if ( !function_exists( 'openssl_encrypt' ) ) {
            return false;
        }
        
        $target = $this->zip_location . $target;
        // server root path to docuemnts directory
        if ( ! $root ) {
            $root = sanitize_url( $_SERVER[ 'DOCUMENT_ROOT'  ] );
        }
        
        // check for existing file in archive path
        if ( file_exists( $target ) ) {
            
            $microtime = microtime( true );
            $nt = !is_dir( $target ) && !unlink( $target ) ? $target . '_' . $microtime . '.zip' : $target . '/' . $microtime . '.zip';
            
            if ( file_exists( $nt ) ) {
                
                if ( $cap > 20 ) {
                    return false;
                }
                
                return $this->archive_files( $files, $target, $root, ++$cap );
            
            }
            
            $target = $nt;
            
        }    
        
        if ( class_exists( 'ZipArchive', false ) ) {
            return $this->archive_files_ZA( $files, $target, $root );
        }
        
        return false;
    
    }


    /**
     * List of file extensions exluded from submission to scan
     *
     * @since 1.0.1
     */
    public static function get_excluded_extensions()
    {
        return array( 
            'avi', 'doc', 'docx', 'eps', 'flv', 'gif', 'gz', 'jpa', 'jpeg', 'jpg', 'm4v', 'mov', 'mp3', 'mp4', 'mpeg', 'mpg', 
            'omf', 'pdf', 'png', 'ppt', 'pptx', 'rar', 'sql', 'tar', 'tgz', 'tif', 'wav', 'wmf', 'zip'
        );
    }
    
    
    /**
     * Zip Files
     *
     * @since   0.0.26
     * @access  private
     * @param   array     $files
     * @param   string    $target
     * @param   string    $root
     */
    private function archive_files_ZA( array $files, $target, $root ) {
        
        $zip = new ZipArchive;
        
        if ( $zip->open( $target, ZipArchive::CREATE ) !== true ) {
            return false;
        }
        
        foreach ( $files as $file ) {
            
            if ( empty( $file ) || !( $contents = file_get_contents( $file ) ) ) {
                continue;
            }
            
            // encode the content
            $ec = openssl_encrypt( $contents, $this->cipher, $this->encrypt_key, $this->mode, $this->iv );
            
            if ( $ec ) {
                $contents = $ec;
            }
            
            $result = $zip->addFromString( $this->mb_htmlentities( $file ), $contents );
        }
        
        $zip->close();
        
        $this->get_zip_size( $target );
        
        return ++$zip->numFiles;
    }
    

    private function mb_htmlentities($string, $hex = true, $encoding = 'UTF-8') {
        return preg_replace_callback('/[\x{80}-\x{10FFFF}]/u', function ($match) use ($hex) {
            return sprintf($hex ? '&#x%X;' : '&#%d;', mb_ord($match[0]));
        }, $string);
    }

}

