# validator [![Build Status](https://travis-ci.org/arkphp/validator.svg)](https://travis-ci.org/arkphp/validator)

PHP validator borrowed from Lavarel.

## Installation

```
composer require ark/validator
```

## Usage

```
<?php
use Ark\Validator\Validator;

$validator = new Validator($form, [
    'email' => 'required|email',
    'password' => 'required|length_between:8,20'
]);

if (!$validator->valid()) {
    echo $validator->errors()->first();
} else {
    echo 'success';
}
```