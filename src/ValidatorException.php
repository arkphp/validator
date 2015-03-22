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
 * Exception for mustValid
 */
class ValidatorException extends \Exception{

    /**
     * Validator errors
     * @var ErrorCollection
     */
    protected $validatorErrors;

    public function __construct($message, $code = 0, $errors = null)
    {
        $this->validatorErrors = $errors;
        parent::__construct($message, $code);
    }

    /**
     * Get validator error collection
     * @return ErrorCollection
     */
    public function getErrors()
    {
        return $this->validatorErrors;
    }
}