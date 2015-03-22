<?php
/**
 * Validator
 *
 * @link http://github.com/arkphp/validator
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

namespace Ark\Validator;

class Validator
{
    protected $data;
    protected $dataRules;
    protected $validateResult;
    static protected $params = array();

    static protected $defaultCustomRules = array();

    protected $customRules = array();

    /**
     * Builtin rule messages defined here, can be extended with Validator::addDefaultRuleMessage
     * @var array
     */
    static protected $defaultRuleMessages = array(
        'required' => 'The :field field is required.',
        'in' => 'The :field must be one of the following: :in.',
        'int' => 'The :field must be integer.',
        'min' => 'The :field must be at least :min.',
        'max' => 'The :field may not be greater than :max.',
        'between' => 'The :field must be between :min and :max.',
        'min_length' => 'The length of :field must be at least :min_length.',
        'max_length' => 'The length of :field may not be greater than :max_length.',
        'between_length' => 'The length of :field must be between :min_length and :max_length.',
        'length' => 'The length of :field must be exactly :length.',
        'regexp' => 'The :field format is invalid.',
        'email' => 'The :field format is invalid.',
        'date' => 'The :field is not a valid date.',
        'date_format' => 'The :field does not match the format :date_format.',
        'date_before' => 'The :field must be before :date_before.',
        'date_after' => 'The :field must be after :date_after.',
        'url' => 'The :field is not a valid url.',
        'alpha' => 'The :field may only contain letters.',
        'alpha_num' => 'The :field may only contain letters and numbers',
        'alpha_dash' => 'The :field may only contain letters, numbers and dashes.',
        'same' => 'The :field must be the same as :same_field.',
        'different' => 'The :field must be different from :different_field.',
    );

    protected $ruleMessages = array();

    protected $fieldMessages = array();

    function __construct($data, $dataRules, $fieldMessages = null) 
    {
        $this->data = $data;
        $this->dataRules = $dataRules;
        if ($fieldMessages !== null) {
            $this->addFieldMessage($fieldMessages);
        }
    }

    static public function addDefaultRule($name, $ruleHandler = null, $message = null) 
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                self::$defaultCustomRules[$key] = $value;
            }
        } else {
            self::$defaultCustomRules[$name] = $ruleHandler;
            if (null !== $message) {
                self::$defaultRuleMessages[$name] = $message;
            }
        }
    }

    public function addRule($name, $ruleHandler = null, $message = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->customRules[$key] = $value;
            }
        } else {
            $this->customRules[$name] = $ruleHandler;
            if (null !== $message) {
                $this->ruleMessages[$name] = $message;
            }
        }

        return $this;
    }

    static public function addDefaultRuleMessage($name, $message)
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                self::$defaultRuleMessages[$key] = $value;
            }
        } else {
            self::$defaultRuleMessages[$name] = $message;
        }
    }

    public function addRuleMessage($name, $message)
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->ruleMessages[$key] = $value;
            }
        } else {
            $this->ruleMessages[$name] = $message;
        }

        return $this;
    }

    public function addFieldMessage($name, $message)
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->addFieldMessage($key, $value);
            }
        } else {
            $this->fieldMessages[$name] = $message;
        }

        return $this;
    }

    static public function notifyParams($params)
    {
        self::$params = $params;
    }

    /**
     * Validate only one time, loop each rules and find all errors.
     */
    public function validateOnce()
    {
        if (!isset($this->validateResult)) {
            $this->validateResult = array();
            $rules = array_merge(self::$defaultCustomRules, $this->customRules);
            $ruleMessages = array_merge(self::$defaultRuleMessages, $this->ruleMessages);
            $fieldMessages = $this->fieldMessages;

            foreach ($this->dataRules as $name => $dataRule) {
                if (!is_array($dataRule)) {
                    $dataRule = explode('|', $dataRule);
                } 
                $checkRequired = false;
                foreach ($dataRule as $key => $value) {                    
                    // rule name as key
                    if (!is_int($key)) {
                        $ruleName = $key;
                        $options = $value;
                    } else {
                        $parts = explode(':', $value, 2);
                        $ruleName = $parts[0];
                        $options = isset($parts[1])?$parts[1]:null;
                    }

                    // check required
                    if (!$checkRequired) {
                        $checkRequired = true;
                        
                        if ($ruleName !== 'required') {
                            // not required, so ignore following rules
                            if (!isset($this->data[$name]) || $this->data[$name] === '' || trim($this->data[$name]) === '') {
                                break;
                            }
                        }
                    }

                    if (isset($rules[$ruleName])) {
                        $rule = $rules[$ruleName];
                    } else {
                        $methodName = str_replace(' ', '', ucfirst(str_replace('_', ' ', $ruleName)));
                        $rule = __CLASS__ . '::is'.$methodName;
                    }

                    if (!isset($this->data[$name]) || !call_user_func($rule, $this->data, $name, $options)) {

                        // find template
                        $k1 = $name.'.'.$ruleName;
                        $k2 = $name;
                        if (isset($fieldMessages[$k1])) {
                            $template = $fieldMessages[$k1];
                        } elseif (isset($fieldMessages[$k2])) {
                            $template = $fieldMessages[$k2];
                        } elseif (isset($ruleMessages[$ruleName])) {
                            $template = $ruleMessages[$ruleName];
                        } else {
                            $template = ':field is not valid';
                        }

                        $params = array_merge(array(
                            ':field' => $name,
                            ':rule' => $ruleName,
                            ':options' => $options,
                            ':value' => $this->data[$name],
                        ), self::$params);

                        self::$params = array();

                        // format message
                        $this->validateResult[$name][$key] = strtr($template, $params);
                    }

                }
            }
        }
    }

    /**
     * Throw exception if validation not passed
     */
    public function mustValid()
    {
        if (!$this->valid()) {
            throw new ValidatorException("Validation failed", 1, $this->getErrors());
        }
    }

    /**
     * Check if the validation is passed
     * @return boolean
     */
    public function valid()
    {
        $this->validateOnce();
        return empty($this->validateResult);
    }

    /**
     * Get validation error collection
     * @return ValidatorErrorCollection The error collection object
     */
    public function getErrors()
    {
        $this->validateOnce();
        return new ErrorCollection($this->validateResult);
    }

    static public function isRequired($data, $field, $options)
    {
        return $data[$field] !== '' && trim($data[$field]) !== '';
    }

    static public function isIn($data, $field, $options)
    {
        if (!is_array($options)) {
            $options = explode(',', $options);
        }

        self::notifyParams(array(
            ':in' => implode(', ', $options)
        ));

        return in_array($data[$field], $options);
    }

    static public function isInt($data, $field, $options)
    {
        return filter_var($data[$field], FILTER_VALIDATE_INT) !== false;
    }

    static public function isMin($data, $field, $options)
    {
        self::notifyParams(array(
            ':min' => $options,
        ));
        return $data[$field] >= $options;
    }

    static public function isMax($data, $field, $options)
    {
        self::notifyParams(array(
            ':max' => $options,
        ));

        return $data[$field] <= $options;
    }

    static public function isBetween($data, $field, $options)
    {
        if (!is_array($options)) {
            $options = explode(',', $options);
        }
        self::notifyParams(array(
            ':min' => $options[0],
            ':max' => $options[1],
        ));

        return $data[$field] >= $options[0] && $data[$field] <= $options[1];
    }

    static public function isMinLength($data, $field, $options)
    {
        self::notifyParams(array(
            ':min_length' => $options,
        ));
        return strlen($data[$field]) >= $options;
    }

    static public function isMaxLength($data, $field, $options)
    {
        self::notifyParams(array(
            ':max_length' => $options,
        ));
        return strlen($data[$field]) <= $options;
    }

    static public function isLength($data, $field, $options)
    {
        self::notifyParams(array(
            ':length' => $options,
        ));

        return strlen($data[$field]) == $options;
    }

    static public function isBetweenLength($data, $field, $options)
    {
        if (!is_array($options)) {
            $options = explode(',', $options);
        }

        self::notifyParams(array(
            ':min_length' => $options[0],
            ':max_length' => $options[1],
        ));

        $len = strlen($data[$field]);

        return $len >= $options[0] && $len <= $options[1];
    }

    static public function isRegexp($data, $field, $options)
    {
        self::notifyParams(array(
            ':regexp' => $options,
        ));

        return preg_match($options, $data[$field]);
    }

    static public function isEmail($data, $field, $options)
    {
        return filter_var($data[$field], FILTER_VALIDATE_EMAIL) !== false;
    }

    static public function isUrl($data, $field, $options)
    {
        return filter_var($data[$field], FILTER_VALIDATE_URL) !== false;
    }

    static public function isAlpha($data, $field, $options)
    {
        return preg_match('/^[a-zA-Z]+$/', $data[$field]);
    }

    static public function isAlphaNum($data, $field, $options)
    {
        return preg_match('/^[a-zA-Z0-9]+$/', $data[$field]);
    }

    static public function isAlphaDash($data, $field, $options)
    {
        return preg_match('/^[a-zA-Z0-9_-]+$/', $data[$field]);
    }

    static public function isDate($data, $field, $options)
    {
        return strtotime($data[$field]) !== false;
    }

    static public function isDateBefore($data, $field, $options)
    {
        self::notifyParams(array(
            ':date_before' => $options,
        ));

        $dateBefore = strtotime($options);
        $date = strtotime($data[$field]);

        return $date !== false && $date < $dateBefore;
    }

    static public function isDateAfter($data, $field, $options)
    {
        self::notifyParams(array(
            ':date_after' => $options,
        ));

        $dateAfter = strtotime($options);
        $date = strtotime($data[$field]);

        return $date !== false && $date > $dateAfter;
    }

    /**
     * Check date format(PHP >= 5.3)
     * @param  array  $data
     * @param  string  $field
     * @param  string  $options
     * @return boolean
     */
    static public function isDateFormat($data, $field, $options)
    {
        self::notifyParams(array(
            ':date_format' => $options,
        ));

        $result = date_parse_from_format($options, $data[$field]);

        return $result['error_count'] === 0 && $result['warning_count'] === 0;
    }



    static public function isSame($data, $field, $options)
    {
        self::notifyParams(array(
            ':same_field' => $options,
            ':same_value' => $data[$options],
        ));
        return isset($data[$options]) && $data[$field] === $data[$options];
    }

    static public function isDifferent($data, $field, $options)
    {
        self::notifyParams(array(
            ':different_field' => $options,
            ':different_value' => $data[$options]
        ));

        return !(isset($data[$options]) && $data[$field] === $data[$options]);
    }

}