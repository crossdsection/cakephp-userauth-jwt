<?php
namespace App\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Table;

/**
 * ArrayOps behavior
 */
class ArrayOpsBehavior extends Behavior
{

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    public function array_group_by( array $array, $key ) {
      if (!is_string($key) && !is_int($key) && !is_float($key) && !is_callable($key) ) {
        trigger_error('array_group_by(): The key should be a string, an integer, or a callback', E_USER_ERROR);
        return null;
      }
      $func = (!is_string($key) && is_callable($key) ? $key : null);
      $_key = $key;
      // Load the new array, splitting by the target key
      $grouped = [];
      foreach ($array as $value) {
        $key = null;
        if (is_callable($func)) {
          $key = call_user_func($func, $value);
        } elseif (is_object($value) && isset($value->{$_key})) {
          $key = $value->{$_key};
        } elseif (isset($value[$_key])) {
          $key = $value[$_key];
        }
        if ($key === null) {
          continue;
        }
        $grouped[$key][] = $value;
      }
      // Recursively build a nested grouping if more parameters are supplied
      // Each grouped array value is grouped according to the next sequential key
      if (func_num_args() > 2) {
        $args = func_get_args();
        foreach ($grouped as $key => $value) {
          $params = array_merge([ $value ], array_slice($args, 2, func_num_args()));
          // call_user_func_array('array_group_by', $params);
          $grouped[$key] = $this->array_group_by( $params[0], $params[1] );
        }
      }
      return $grouped;
    }
}
