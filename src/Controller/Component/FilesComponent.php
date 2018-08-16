<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;

/**
 * Files component
 */
class FilesComponent extends Component
{

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    function saveFile( $file = null ){
      $retval = false;
      if( $file != null ){
        $filePath = 'img' . DS . 'upload' . DS . $file['name'];
        $fileUrl = WWW_ROOT . DS . $filePath;
        $localFileUrl = 'webroot' . DS . $filePath;
        $fileArr = array(
          'fileurl' => $filePath,
          'filetype' => $file['type'],
          'size' => $file['size']
        );
        if( move_uploaded_file( $file['tmp_name'], $fileUrl ) ){
          $retval = array( 'filepath' => $localFileUrl, 'filetype' => $file['type'] );
        }
      }
      return $retval;
    }
}
