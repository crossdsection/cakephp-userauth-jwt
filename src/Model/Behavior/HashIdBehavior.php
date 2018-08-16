<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Database\ExpressionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Hashids\Hashids;

/**
 * HashId behavior
 */
class HashIdBehavior extends Behavior
{

    const HID = 'hid';

  	/**
  	 * @var array|string
  	 */
  	protected $_primaryKey;

  	/**
  	 * @var array
  	 */
  	protected $_defaultConfig = [
  		'salt' => 'SayForExampleThisIsMySalt', // Please provide your own salt via Configure key 'Hashid.salt'
  		'field' => array(), // To populate upon find() and save(), false to deactivate
  		'debug' => false, // Auto-detect from Configure::read('debug')
  		'minHashLength' => 8, // You can overwrite the Hashid defaults
  		'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_', // You can overwrite the Hashid defaults
  		'recursive' => false, // Also transform nested entities
  		'findFirst' => false, // Either true or 'first' or 'firstOrFail'
  		'implementedFinders' => [
  			'hashed' => 'findHashed',
  		]
  	];

    /**
     * Constructor
     *
     * Merges config with the default and store in the config property
     *
     * Does not retain a reference to the Table object. If you need this
     * you should override the constructor.
     *
     * @param \Cake\ORM\Table $table The table this behavior is attached to.
     * @param array $config The config for this behavior.
     */
    public function __construct(Table $table, array $config = []) {
      parent::__construct($table, $config);
      $this->_table = $table;
      $this->_primaryKey = $table->getPrimaryKey();
      if ($this->_config['salt'] === null) {
        $this->_config['salt'] = Configure::read('Security.salt') ? sha1(Configure::read('Security.salt')) : null;
      }
      if ($this->_config['debug'] === null) {
        $this->_config['debug'] = Configure::read('debug');
      }
      if ( empty( $this->_config['field'] )) {
        $this->_config['field'][] = $this->_primaryKey;
      }
    }

    /**
    * @param \Cake\Event\Event $event
    * @param \Cake\ORM\Query $query
    * @param \ArrayObject $options
    * @param bool $primary
    * @return void
    */
    public function beforeFind(Event $event, Query $query, ArrayObject $options ) {
      $field = $this->_config['field'];
      if( !in_array( $this->_primaryKey, $field ) ){
        $field[] = $this->_primaryKey;
      }
      if ( empty( $field ) ) {
        return;
      }
      if ( !empty( $field ) ) {
        foreach( $field as $idField ){
          $query->traverseExpressions(function (ExpressionInterface $expression) use ( $idField ){
              if (method_exists($expression, 'getField')
                && ( $expression->getField() === $idField || $expression->getField() === $this->_table->alias() . '.' . $idField )
              ) {
                /** @var \Cake\Database\Expression\Comparison $expression */
                $expression->setValue( $this->decodeHashid( $expression->getValue() ) );
              }
            return $expression;
          });
        }
      }
      $query->find('hashed');
      foreach ($this->_table->associations() as $association) {
      	if ($association->getTarget()->hasBehavior('Hashid') && $association->getFinder() === 'all') {
      		$association->setFinder('hashed');
      	}
      }
    }

    /**
    * @param \Cake\Event\Event $event The beforeSave event that was fired
    * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be saved
    * @return void
    */
    // public function beforeRules( Event $event, EntityInterface $entity, ArrayObject $options ){
    // }

    /**
    * @param \Cake\Event\Event $event The fixEncodings event that was fired
    * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be saved
    * @return void
    */
    public function fixEncodings( EntityInterface $entity ) {
      if( $entity->isNew() ){
        return $entity;
      }
      $this->decode( $entity );
      return $entity;
    }

    /**
    * @param \Cake\Event\Event $event The fixEncodings event that was fired
    * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be saved
    * @return void
    */
    public function encodeResultSet( EntityInterface $entity ) {
      $field = $this->_config['field'];
      if( !in_array( $this->_primaryKey, $field ) ){
        $field[] = $this->_primaryKey;
      }
      foreach( $field as $key ){
        if( isset( $entity[ $key ] ) ){
          $entity[ $key ] = $this->encodeId( $entity[ $key ] );
        }
      }
      return $entity;
    }

    /**
    * @param \Cake\Event\Event $event The fixEncodings event that was fired
    * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be saved
    * @return void
    */
    public function beforeDelete(Event $event, EntityInterface $entity, ArrayObject $options){
      if( $entity->isNew() ){
        return $entity;
      }
      $this->decode( $entity );
    }

    /**
    * @param \Cake\Event\Event $event The fixConditions event that was fired Manual Call to decode Conditions
    * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be saved
    * @return void
    */
    public function fixConditions( Array $conditions ) {
      $field = $this->_config['field'];
      if( !in_array( $this->_primaryKey, $field ) ){
        $field[] = $this->_primaryKey;
      }
      if ( !empty( $field ) ) {
        foreach( $field as $idField ){
          if( isset( $conditions[ $idField ] ) && !is_numeric( $conditions[ $idField ] ) ){
            $hashid = $conditions[ $idField ];
            $id = $this->decodeHashid( $hashid );
            $conditions[ $idField ] = $id;
          }
        }
      }
      return $conditions;
    }

    /**
     * @param \Cake\Event\Event $event The beforeMarshal event that was fired
     * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be saved
     * @param \ArrayObject $options
     * @return void
     */
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options) {
      $field = $this->_config['field'];
      if( !in_array( $this->_primaryKey, $field ) ){
        $field[] = $this->_primaryKey;
      }
      if ( !empty( $field ) ) {
        foreach( $field as $idField ){
          if( isset( $data[ $idField ] ) && !is_numeric( $data[ $idField ] ) ){
            $hashid = $data[ $idField ];
            $id = $this->decodeHashid( $hashid );
            $data[ $idField ] = $id;
          }
        }
      }
    }

    /**
    * Custom finder for hashids field.
    *
    * Options:
    * - hid (required), best to use HashidBehavior::HID constant
    * - noFirst (optional, to leave the query open for adjustments, no first() called)
    *
    * @param \Cake\ORM\Query $query Query.
    * @param array $options Array of options as described above
    * @return \Cake\ORM\Query
    */
    public function findHashed(Query $query, array $options) {
      $field = $this->_config['field'];
      if( !in_array( $this->_primaryKey, $field ) ){
        $field[] = $this->_primaryKey;
      }
      if ( empty( $field ) ) {
        return $query;
      }
      $query->formatResults(function ( $results ) use ( $field ) {
        $newResult = [];
        $results->each( function ( $row, $key ) use ( $field, &$newResult ) {
          foreach( $field as $idField ){
            if ( !empty( $row[ $idField ] ) && is_numeric( $row[ $idField ] ) ) {
              $row[ $idField ] = $this->encodeId( $row[ $idField ] );
              if ( $row instanceof EntityInterface ) {
                $row->setDirty( $idField, false );
              }
            } elseif ( is_string( $row ) ) {
              $newResult[ $this->encodeId( $key ) ] = $row;
            }
          }
          $newResult[] = $row;
        });
        return new Collection( $newResult );
      });
      if (!empty( $options[ static::HID ] ) ) {
        $id = $this->decodeHashid( $options[ static::HID ] );
        $query->where( [ $idField => $id ]);
      }
      $first = $this->_config['findFirst'] === true ? 'first' : $this->_config['findFirst'];
      if ( !$first || !empty( $options['noFirst'] ) ) {
        return $query;
      }
      return $query->first();
    }

    /**
     * @param int $id
     * @return string
     */
    public function encodeId($id) {
      if ($id < 0 || !is_numeric($id)) {
        return $id;
      }
      $hashid = $this->_getHasher()->encode($id);
      if ($this->_config['debug']) {
        $hashid .= '-' . $id;
      }
      return $hashid;
    }


    /**
     * Sets up hashid for model.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be saved
     * @return bool True if save should proceed, false otherwise
     */
    public function decode(EntityInterface $entity) {
      $field = $this->_config['field'];
      $field[] = $this->_primaryKey;
      foreach( $field as $idField ){
        $hashid = $entity->get( $idField );
        if( $hashid && !is_numeric( $hashid ) ){
          $id = $this->decodeHashid( $hashid );
          $originalId = $entity->getOriginal( $idField );
          $entity->set( $idField, $id);
          if( $originalId == $id ){
            $entity->setDirty( $idField, false );
          } else {
            $entity->setDirty( $idField, true );
          }
        }
      }
    }

    /**
     * @param string $hashid
     * @return int
     */
    public function decodeHashid($hashid) {
      if ( is_array( $hashid ) ) {
        foreach ($hashid as $k => $v) {
          if( !is_numeric( $v ) ){
            $hashid[ $k ] = $this->decodeHashid( $v );
          }
        }
        return $hashid;
      }
      if ($this->_config['debug']) {
        $hashid = substr($hashid, 0, strpos($hashid, '-'));
      }
      if( !is_numeric( $hashid ) ){
        $ids = $this->_getHasher()->decode($hashid);
        return array_shift($ids);
      } else {
        return $hashid;
      }
    }

    /**
     * @return \Hashids\Hashids
     */
    protected function _getHasher() {
      if (isset($this->_hashids)) {
        return $this->_hashids;
      }
      if ( $this->_config['alphabet'] ) {
        $this->_hashids = new Hashids($this->_config['salt'], $this->_config['minHashLength'], $this->_config['alphabet']);
      } else {
        $this->_hashids = new Hashids($this->_config['salt'], $this->_config['minHashLength']);
      }
      return $this->_hashids;
    }
}
