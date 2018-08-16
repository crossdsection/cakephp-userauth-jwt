<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Utility\Security;
use Cake\Core\Configure;
use Firebase\JWT\JWT;

/**
 * Oauth Model
 *
 * @property \App\Model\Table\UsersTable|\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\ProvidersTable|\Cake\ORM\Association\BelongsTo $Providers
 *
 * @method \App\Model\Entity\Oauth get($primaryKey, $options = [])
 * @method \App\Model\Entity\Oauth newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Oauth[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Oauth|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Oauth|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Oauth patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Oauth[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Oauth findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class OauthTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('oauth');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->addBehavior('HashId', ['field' => array( 'user_id' ) ]);

        $this->belongsTo('User', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER'
        ]);
        // $this->belongsTo('Providers', [
        //     'foreignKey' => 'provider_id',
        //     'joinType' => 'INNER'
        // ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->allowEmpty('id', 'create');

        $validator
            ->integer('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmpty('user_id');

        $validator
            ->scalar('access_token')
            ->maxLength('access_token', 2048)
            ->requirePresence('access_token', 'create')
            ->notEmpty('access_token');

        $validator
            ->requirePresence('expiration_time', 'create')
            ->notEmpty('expiration_time');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules) {
        // $rules->add($rules->existsIn(['user_id'], 'User'));
        // $rules->add($rules->existsIn(['provider_id'], 'Providers'));

        return $rules;
    }

    public function matchAndRetrieve( $access_token ){
        return true;
    }

    public function getUserToken( $userId ){
      $result = array( 'error' => 0, 'data' => array());
      if( $userId != null ){
        $extractedData = $this->find()->where([ 'user_id' => $userId ])->toArray();
        if( !empty( $extractedData ) && strtotime( $extractedData[0]['expiration_time'] ) > time() ){
          $user = $this->User->find()->where( [ 'id' => $userId ] )->toArray();
          $secretKey = Configure::read('jwt_secret_key');
          $issuedAt = time();
          $expire = $issuedAt + 86400;

          $data = [
             'issued_at'  => $issuedAt,         // Issued at: time when the token was generated
             'access_token'  => $extractedData[0]['access_token'],          // Json Token Id: an unique identifier for the token
             'provider_id'  => $extractedData[0]['provider_id'],       // Issuer
             'expiration_time'  => $expire,           // Expire
             'user_id' => $userId
          ];
          $bearerToken = JWT::encode(
            $data,      //Data to be encoded in the JWT
            $secretKey, // The signing key
            'HS512'     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
          );
          $oAuth = TableRegistry::get('Oauth');
          $oEntity = $oAuth->get( $extractedData[0]->id );
          $oEntity = $oAuth->patchEntity( $oEntity, $data );
          if( $oAuth->save( $oEntity ) ){
            $response = array(
              'name' => $user[0]->firstname.' '.$user[0]->lastname,
              'bearerToken' => $bearerToken
            );
            $result['data'] = $response;
          } else {
            $result['error'] = -1;
          }
        } else if( !empty( $extractedData ) ) {
          $result['error'] = -1;
        } else {
          $result['error'] = 1;
        }
      } else {
        $result['error'] = 1;
      }
      return $result;
    }

    public function createUserToken( $userId ){
      $result = array( 'error' => 0, 'data' => array());
      if( $userId != null ){
        $user = $this->User->find()->where( [ 'id' => $userId ] )->toArray();
        $serverName = Configure::read('App.fullBaseUrl');
        $secretKey = Configure::read('jwt_secret_key');
        $tokenId   = base64_encode( Security::randomBytes( 32 ) );
        $issuedAt = time();
        $expire = $issuedAt + 86400;
        $data = [
           'issued_at'  => $issuedAt,         // Issued at: time when the token was generated
           'access_token'  => $tokenId,          // Json Token Id: an unique identifier for the token
           'provider_id'  => $serverName,       // Issuer
           'expiration_time'  => $expire,           // Expire
           'user_id' => $userId
        ];

        $bearerToken = JWT::encode(
          $data,      //Data to be encoded in the JWT
          $secretKey, // The signing key
          'HS512'     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
        );

        $oAuth = TableRegistry::get('Oauth');
        $oEntity = $oAuth->newEntity();
        $oEntity = $oAuth->patchEntity( $oEntity, $data );
        if( $oAuth->save( $oEntity ) ){
          $response = array(
            'name' => $user[0]->firstname.' '.$user[0]->lastname,
            'bearerToken' => $bearerToken
          );
          $result['data'] = $response;
        } else {
          $result['error'] = 1;
        }
      } else {
        $result['error'] = 1;
      }
      return $result;
    }

    public function refreshAccessToken( $userId ){
      $result = array( 'error' => 0, 'data' => array());
      if( $userId != null ){
        $user = $this->User->find()->where( [ 'id' => $userId ] )->toArray();
        $extractedData = $this->find()->where([ 'user_id' => $userId ])->toArray();
        $serverName = Configure::read('App.fullBaseUrl');
        $secretKey = Configure::read('jwt_secret_key');
        $tokenId   = base64_encode( Security::randomBytes( 32 ) );
        $issuedAt = time();
        $expire = $issuedAt + 86400;

        $data = [
           'issued_at'  => $issuedAt,         // Issued at: time when the token was generated
           'access_token'  => $tokenId,          // Json Token Id: an unique identifier for the token
           'provider_id'  => $serverName,       // Issuer
           'expiration_time'  => $expire,           // Expire
           'user_id' => $userId
        ];

        $bearerToken = JWT::encode(
          $data,      //Data to be encoded in the JWT
          $secretKey, // The signing key
          'HS512'     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
        );

        $saveData = $data;
        $saveData['modified'] = $data['issued_at'];

        $oAuth = TableRegistry::get('Oauth');
        $oEntity = $oAuth->get( $extractedData[0]->id );
        $oEntity = $oAuth->patchEntity( $oEntity, $saveData );
        $oEntity = $this->fixEncodings( $oEntity );
        if( $oAuth->save( $oEntity ) ){
          $response = array(
            'name' => $user[0]->firstname.' '.$user[0]->lastname,
            'bearerToken' => $bearerToken
          );
          $result['data'] = $response;
        } else {
          $result['error'] = 1;
        }
      } else {
        $result['error'] = 1;
      }
      return $result;
    }

    public function validateToken( $token ){
      $result = false;
      if( !empty( $token ) && isset( $token->user_id ) && isset( $token->access_token ) ){
        $extractedData = $this->find()->where([ 'user_id' => $token->user_id, 'access_token' => $token->access_token ])->toArray();
        if( !empty( $extractedData ) && $token->expiration_time >= time() ){
          return true;
        }
      }
      return $result;
    }

    public function deleteUserToken( $userId ){
      $result = false;
      if( $userId != null ){
        $extractedData = $this->find()->where([ 'user_id' => $userId ])->toArray();
        $oAuth = TableRegistry::get('Oauth');
        $entity = $oAuth->get( $extractedData[0]->id );
        $result = $oAuth->delete( $entity );
      }
      return $result;
    }
}
