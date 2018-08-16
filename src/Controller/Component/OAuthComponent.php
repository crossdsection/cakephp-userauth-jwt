<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\ORM\TableRegistry;

/**
 * OAuth component
 */
class OAuthComponent extends Component
{

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    public function getAccessToken( $userId = null ){
      $response = array( 'error' => 0, 'message' => '', 'data' => array() );
      if( $userId != null ){
        $this->Oauth = TableRegistry::get('Oauth');
        $result = $this->Oauth->getUserToken( $userId );
        if( $result['error'] == -1 ){
          $result = $this->Oauth->refreshAccessToken( $userId );
        } else if( $result['error'] == 1 ){
          $result = $this->Oauth->createUserToken( $userId );
        }
        if( $result['error'] == 0 ){
          $response['message'] = 'Access Token Generated';
          $response['data'] = $result['data'];
        } else {
          $response['error'] = 1;
          $response['message'] = 'Failed! Please Try Again.';
        }
      }
      return $response;
    }

    public function removeToken( $userId = null ){
      $response = array( 'error' => 0, 'message' => 'Invalid Request', 'data' => array() );
      if( $userId != null ){
        $this->Oauth = TableRegistry::get('Oauth');
        $result = $this->Oauth->deleteUserToken( $userId );
        if( $result )
          $response = array( 'error' => 0, 'message' => 'Logout Successful', 'data' => array() );
        else
          $response = array( 'error' => 1, 'message' => 'Logout Failed', 'data' => array() );
      }
      return $response;
    }
}
