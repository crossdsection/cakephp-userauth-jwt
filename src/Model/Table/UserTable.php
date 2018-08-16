<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
/**
 * User Model
 *
 *
 * @method \App\Model\Entity\User get($primaryKey, $options = [])
 * @method \App\Model\Entity\User newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\User[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\User|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\User[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\User findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UserTable extends Table
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

        $this->setTable('user');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('HashId', [ 'field' => array( 'default_location_id', 'department_id', 'country_id', 'state_id', 'city_id' ) ]);

        $this->hasOne('LoginRecord');
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
            ->scalar('firstname')
            ->maxLength('firstname', 256)
            ->requirePresence('firstname', 'create')
            ->notEmpty('firstname');

        $validator
            ->scalar('lastname')
            ->maxLength('lastname', 256)
            ->requirePresence('lastname', 'create')
            ->notEmpty('lastname');

        $validator
            ->scalar('gender')
            ->maxLength('gender', 20)
            ->allowEmpty('gender');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmpty('email')
            ->add('email', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('password')
            ->maxLength('password', 256)
            ->requirePresence('password', 'create')
            ->notEmpty('password');

        $validator
            ->scalar('phone')
            ->maxLength('phone', 256)
            ->allowEmpty('phone');

        $validator
            ->scalar('address')
            ->maxLength('address', 256)
            ->allowEmpty('address');

        $validator
            ->scalar('latitude')
            ->maxLength('latitude', 256)
            ->allowEmpty('latitude');

        $validator
            ->scalar('longitude')
            ->maxLength('longitude', 256)
            ->allowEmpty('longitude');

        $validator
            ->scalar('profilepic')
            ->maxLength('profilepic', 256)
            ->allowEmpty('profilepic');

        $validator
            ->boolean('status')
            // ->requirePresence('status', 'create')
            ->notEmpty('status');

        $validator
            ->boolean('active')
            // ->requirePresence('active', 'create')
            ->notEmpty('active');

        $validator
            ->boolean('email_verified')
            // ->requirePresence('email_verified', 'create')
            ->notEmpty('email_verified');

        $validator
            ->integer('adhar_verified')
            // ->requirePresence('adhar_verified', 'create')
            ->notEmpty('adhar_verified');

        $validator
            // ->requirePresence('authority_flag', 'create')
            ->notEmpty('authority_flag');

        $validator
            ->scalar('access_role_ids')
            ->maxLength('access_role_ids', 1024)
            // ->requirePresence('access_role_ids', 'create')
            ->notEmpty('access_role_ids');

        $validator
            ->scalar('rwa_name')
            ->maxLength('rwa_name', 1024)
            ->allowEmpty('rwa_name');

        $validator
            ->scalar('designation')
            ->maxLength('designation', 512)
            // ->requirePresence('designation', 'create')
            ->notEmpty('designation');

        $validator
            ->scalar('certificate')
            ->maxLength('certificate', 512)
            // ->requirePresence('certificate', 'create')
            ->notEmpty('certificate');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->isUnique(['email']));

        return $rules;
    }

    public function add( $userData = array() ){
      $response = false;
      if( !empty( $userData ) ){
        if( isset( $userData['password'] ) ){
          $users = TableRegistry::get('User');
          $entity = $users->newEntity();
          $entity = $users->patchEntity( $entity, $userData );
          $record = $users->save( $entity );
          if( isset( $record->id ) ){
            $response = $record->id;
          }
        }
      }
      return $response;
    }

    public function checkPassword( $password, $storedPassword ){
      $users = TableRegistry::get('User');
      $entity = $users->newEntity();
      $response = $entity->_checkPassword( $password, $storedPassword );
      return $response;
    }

    public function getUserInfo( $userIds = array() ){
      $response = array();
      if( !empty( $userIds ) ){
        $users = $this->find()->where([ 'id IN' => $userIds ])->toArray();
        foreach( $users as $index => $user ){
          $tmpResponse = $user;
          if( isset( $user['firstname'] ) && isset( $user['lastname'] ) ){
            $tmpResponse['name'] = $user['firstname'].' '.$user['lastname'];
          }
          $tmpResponse[ 'profilepic' ] = ( !isset( $tmpResponse[ 'profilepic' ] ) or $tmpResponse[ 'profilepic' ] == null or $tmpResponse[ 'profilepic' ] == '' ) ? 'webroot' . DS . 'img' . DS . 'assets' . DS . 'profile-pic.png' : $tmpResponse[ 'profilepic' ];
          $response[ $user->id ] = $tmpResponse;
        }
      }
      return $response;
    }

    public function getUserList( $userIds = array(), $userKeys = array( 'id', 'profilepic', 'firstname', 'lastname' ) ){
      $response = array();
      if( !empty( $userIds ) ){
        $professionFlag = false;
        /*Just Dummy Key, remove as soon as possible*/
        if( in_array( 'profession', $userKeys ) ){
          $professionFlag = true;
          $index = array_search( 'profession', $userKeys );
          unset( $userKeys[ $index ] );
        }
        $users = $this->find('all')->select( $userKeys )->where([ 'id IN' => $userIds, 'status' => 1 ])->toArray();
        foreach( $users as $index => $user ){
          $tmpResponse = array();
          if( isset( $user['firstname'] ) && isset( $user['lastname'] ) ){
            $tmpResponse['name'] = $user['firstname'].' '.$user['lastname'];
          }
          foreach( $userKeys as $stringKey ){
            if( $stringKey == 'profilepic' )
              $tmpResponse[ 'profilepic' ] = ( $user[ 'profilepic' ] == null or $user[ 'profilepic' ] == '' ) ? 'webroot' . DS . 'img' . DS . 'assets' . DS . 'profile-pic.png' : $user[ 'profilepic' ];
            else
              $tmpResponse[ $stringKey ] = $user[ $stringKey ];
          }
          /*Just Dummy Key, remove as soon as possible*/
          if( $professionFlag ){
            $tmpResponse[ 'profession' ] = '';
          }
          $response[ $user->id ] = $tmpResponse;
        }
      }
      return $response;
    }

    public function updateUser( $userDatas ){
      $response = array();
      if( !empty( $userDatas ) ){
        $users = TableRegistry::get('User');
        foreach( $userDatas as $user ){
          $entity = $users->get( $user['id'] );
          foreach( $user as $key => $value ){
            if( $key != 'id' ){
              if( $key != 'date_of_birth' ){
                $entity->{$key} = $value;
              } else {
                $entity->{$key} = time( strtotime( $value ) );
              }
            }
          }
          $entity = $this->fixEncodings( $entity );
          if( $users->save( $entity ) ){
            $response[] = $user['id'];
          }
        }
      }
      return $response;
    }

    public function checkEmailExist( $email = null ){
      $return = false;
      if( $email != null ){
        $user = TableRegistry::get('User');
        $return = $user->exists( [ 'email' => $email ] );
        return $return;
      }
      return $return;
    }
}
