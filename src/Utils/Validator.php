<?php

namespace InstagramClone\Utils;

class Validator
{
    private array $errors = [];

    public function required(array $fields, array $data): self
    {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $this->errors[$field] = "The {$field} field is required.";
            }
        }
        return $this;
    }

    public function email(string $field, string $value): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "The {$field} must be a valid email address.";
        }
        return $this;
    }

    public function minLength(string $field, string $value, int $min): self
    {
        if (!empty($value) && strlen($value) < $min) {
            $this->errors[$field] = "The {$field} must be at least {$min} characters.";
        }
        return $this;
    }

    public function maxLength(string $field, string $value, int $max): self
    {
        if (!empty($value) && strlen($value) > $max) {
            $this->errors[$field] = "The {$field} must not exceed {$max} characters.";
        }
        return $this;
    }

    public function url(string $field, string $value): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field] = "The {$field} must be a valid URL.";
        }
        return $this;
    }

    public function numeric(string $field, $value): self
    {
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field] = "The {$field} must be a number.";
        }
        return $this;
    }

    public function in(string $field, $value, array $allowed): self
    {
        if (!empty($value) && !in_array($value, $allowed)) {
            $allowedStr = implode(', ', $allowed);
            $this->errors[$field] = "The {$field} must be one of: {$allowedStr}.";
        }
        return $this;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }
}