<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * LoginRecord Model
 *
 * @property \App\Model\Table\UserTable|\Cake\ORM\Association\BelongsTo $User
 *
 * @method \App\Model\Entity\LoginRecord get($primaryKey, $options = [])
 * @method \App\Model\Entity\LoginRecord newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\LoginRecord[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\LoginRecord|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\LoginRecord|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\LoginRecord patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\LoginRecord[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\LoginRecord findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class LoginRecordTable extends Table
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

        $this->setTable('login_record');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('HashId', ['field' => array( 'user_id' ) ]);

        $this->belongsTo('User', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER'
        ]);
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
            ->scalar('latitude')
            ->maxLength('latitude', 256)
            ->requirePresence('latitude', 'create')
            ->notEmpty('latitude');

        $validator
            ->scalar('longitude')
            ->maxLength('longitude', 256)
            ->requirePresence('longitude', 'create')
            ->notEmpty('longitude');

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
        $rules->add($rules->existsIn(['user_id'], 'User'));
        return $rules;
    }

    public function saveLog( $userData = array() ){
      $return = false;
      if( !empty( $userData ) ){
        $loginLog = TableRegistry::get('LoginRecord');
        $entity = $loginLog->newEntity();
        $entity = $loginLog->patchEntity( $entity, $userData );
        if( $loginLog->save( $entity ) ){
          $return = true;
        }
      }
      return $return;
    }

    public function getLastLogin( $userId = null ){
      $res = array();
      if( $userId != null ){
        $loginLog = $this->find()->where([ 'user_id' => $userId ])->max('id')->toArray();
        $res = $loginLog;
      }
      return $res;
    }
}
