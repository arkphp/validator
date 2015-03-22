<?php
/**
 * Validator
 *
 * @link http://github.com/arkphp/validator
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

namespace Ark\Validator;

/**
 * Validator error collection to simplify error retrieval
 */
class ErrorCollection
{
    protected $errors = array();

    public function __construct($errors)
    {
        $this->errors = $errors;
    }

    /**
     * Get the first error
     * @param  string $field Specify field to retrieve, default is the first field
     * @return string
     */
    public function first($field = null)
    {
        if (null === $field) {
            foreach ($this->errors as $field => $errors) {
                foreach ($errors as $key => $error) {
                    return $error;
                }
            }
        } else {
            if (isset($this->errors[$field])) {
                foreach ($this->errors[$field] as $key => $error) {
                    return $error;
                }
            }
        }

        return null;
    }

    /**
     * Check if specified field is in the error list
     * @param  string  $field 
     * @return boolean
     */
    public function has($field = null)
    {
        if (null === $field) {
            return !empty($this->errors);
        }

        return isset($this->errors[$field]);
    }

    /**
     * Get errors of specified field
     * @param  string $field
     * @return array
     */
    public function get($field)
    {
        if (isset($this->errors[$field])) {
            return $this->errors[$field];
        } else {
            return array();
        }
    }

    /**
     * Add error to collection
     * @param mixed $field
     * @param string $error
     */
    public function add($field, $error = null)
    {
        if (null === $error) {
            if (!is_array($field)) {
                $this->errors['.'][] = $field;
            } else {
                foreach ($field as $key => $value) {
                    $this->errors[$key][] = $value;
                }
            }
        } else {
            $this->errors[$field][] = $error;
        }
    }

    /**
     * Get all errors
     * @return array
     */
    public function all()
    {
        $errors = array();
        foreach ($this->errors as $key => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }
}