<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Routing\Router;
/**
 * User Controller
 *
 * @property \App\Model\Table\UserTable $User
 *
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class UserController extends AppController {

    public $components = array('OAuth', 'Files');

    public function userexists(){
      $response = array( 'error' => 1, 'message' => '', 'data' => array() );
      $getData = $this->request->query();
      if( isset( $getData['email'] ) && $this->User->checkEmailExist( $getData['email'] ) ){
        $response = array( 'error' => 0, 'message' => 'User Exists', 'data' => array( 'exist' => true, 'notExist' => false ) );
      } else {
        $response = array( 'error' => 0, 'message' => 'User Does not Exists', 'data' => array( 'exist' => false, 'notExist' => true ) );
      }
      $this->response = $this->response->withType('application/json')
                                       ->withStringBody( json_encode( $response ) );
      return $this->response;
    }

    /**
     * Signup API
     */
    public function signup() {
        $response = array( 'error' => 1, 'message' => '', 'data' => array() );
        $userData = array(); $continue = false;
        $postKeys = array( 'email', 'password', 'firstName', 'lastName', 'latitude', 'longitude', 'gender', 'phone', 'certificate' );
        $postData = $this->request->input( 'json_decode', true );
        if( empty( $postData ) ){
          $postData = $this->request->data;
        }
        if( isset( $postData['email'] ) && !$this->User->checkEmailExist( $postData['email'] ) ){
          $continue = true;
        }
        if ( !empty( $postData ) && $continue ){
          foreach( $postKeys as $postKey ){
            if( isset( $postData[ $postKey ] ) && !empty( $postData[ $postKey ] ) ){
              $newKey = strtolower( $postKey );
              if( $postKey == 'certificate' ){
                $file = $postData[ $postKey ];
                $filePath = 'img' . DS . 'upload' . DS . $file['name'];
                $fileUrl = WWW_ROOT . $filePath;
                if( move_uploaded_file( $file['tmp_name'], $fileUrl ) ){
                  $userData[ $newKey ] = $filePath;
                }
              } else {
                $userData[ $newKey ] = $postData[ $postKey ];
              }
            }
          }
          if( isset( $postData['birthDate'] ) ){
            $userData['date_of_birth'] = date( 'Y-m-d', strtotime( $postData['birthDate'] ) );
          }
          if( !empty( $userData ) ){
            $returnId = $this->User->add( $userData );
            if( $returnId ){
              $response = array( 'error' => 0, 'message' => 'Registration Successful', 'data' => array() );
            } else {
              $response = array( 'error' => 1, 'message' => 'Registration Failed', 'data' => array() );
            }
          } else {
            $response = array( 'error' => 1, 'message' => 'Invalid Request', 'data' => array() );
          }
        } else {
          $response = array( 'error' => 1, 'message' => 'Registration Failed', 'data' => array() );
        }
        $this->response = $this->response->withType('application/json')
                                         ->withStringBody( json_encode( $response ) );
        return $this->response;
    }

    /**
     * Login API
     */
    public function login() {
        $response = array( 'error' => 0, 'message' => '', 'data' => array() );
        $postData = $this->request->input('json_decode', true);
        if( empty( $postData ) ){
          $postData = $this->request->data;
        }
        if( isset( $postData['username'] ) && isset( $postData['password'] ) ){
          $user = $this->User->find()->where([ 'email' => $postData['username'] ])->toArray();
          if( !empty( $user ) && $this->User->checkPassword( $user[0]->password, $postData['password'] ) ){
            $res = $this->OAuth->getAccessToken( $user[0]->id );
            if( $res['error'] == 0 ){
               $latitude = 0;
               $longitude = 0;
               if( isset( $postData['latitude'] ) && $postData['latitude'] != 0 ){
                 $latitude = $postData['latitude'];
               }
               if( isset( $postData['longitude'] ) && $postData['latitude'] != 0 ){
                 $longitude = $postData['longitude'];
               }
               $userData = array(
                 'user_id'  => $user[0]->id,
                 'latitude' => $latitude,
                 'longitude'=> $longitude
               );
               $ret = $this->User->LoginRecord->saveLog( $userData );
            }
            $response = array( 'error' => $res['error'], 'message' => $res['message'], 'data' => $res['data'] );
          } else {
            $response = array( 'error' => 1, 'message' => 'Invalid Login', 'data' => array() );
          }
        } else {
          $response = array( 'error' => 1, 'message' => 'Invalid Login', 'data' => array() );
        }
        $this->response = $this->response->withType('application/json')
                                         ->withStringBody( json_encode( $response ) );
        return $this->response;
    }

    public function logout() {
      $response = array( 'error' => 1, 'message' => 'Invalid Request' );
      $userId = null;
      if( isset( $_GET['userId'] ) ){
        $userId = $_GET['userId'];
      }
      if( $userId != null ){
        $response = $this->OAuth->removeToken( $userId );
      }
      $this->response = $this->response->withType('application/json')
                                       ->withStringBody( json_encode( $response ) );
      return $this->response;
    }

    public function getuserinfo() {
      $response = array( 'error' => 1, 'message' => 'Invalid Request' );
      $jsonData = $this->request->input('json_decode', true);
      $getData = $this->request->query();
      $postData = $this->request->getData();
      $requestData = array_merge( $getData, $postData );
      if( $jsonData )
        $requestData = array_merge( $requestData, $jsonData );
      if( !isset( $requestData['userId'] ) ){
        $requestData['userId'] = $_POST['userId'];
      }
      if( !isset( $requestData['mcph'] ) ){
        $requestData['mcph'] = $requestData['userId'];
      }
      if( !empty( $requestData ) ){
        $data = $this->User->getUserInfo( array( $requestData['mcph'] ) );
        if( !empty( $data ) ){
          if( $requestData['mcph'] != $requestData['userId'] ){
            $data[ $requestData['mcph'] ]['editable'] = false;
          } else {
            $data[ $requestData['mcph'] ]['editable'] = true;
          }
          $response = array( 'error' => 0, 'message' => 'Success!', 'data' => array_values( $data ) );
        } else {
          $response = array( 'error' => 0, 'message' => 'User Not Found', 'data' => array() );
        }
      }
      $this->response = $this->response->withType('application/json')
                                       ->withStringBody( json_encode( $response ) );
      return $this->response;
    }

    public function updateuserinfo() {
      $response = array( 'error' => 0, 'message' => '', 'data' => array() );
      $postData = $this->request->input('json_decode', true);
      if( !empty( $postData ) ){
        $postData['id'] = $_POST['userId'];
      } else {
        $postData = $this->request->data;
      }
      if( !empty( $postData ) ){
        $updatedUser = $this->User->updateUser( array( $postData ) );
        $userCount = count( $updatedUser );
        if( $userCount > 0 ){
          $response = array( 'error' => 0, 'message' => 'User has been updated.', 'data' => array() );
        } else {
          $response = array( 'error' => 0, 'message' => 'Update Failed.', 'data' => array() );
        }
      }
      $this->response = $this->response->withType('application/json')
                                       ->withStringBody( json_encode( $response ) );
      return $this->response;
    }
}
