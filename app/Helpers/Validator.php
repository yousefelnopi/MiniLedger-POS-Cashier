<?php

namespace App\Helpers;

class Validator
{
    private array $errors = [];
    private array $data   = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    // ─── Create new instance ──────────────────────────────────────────
    // Example: Validator::make($_POST)->required('email')->email('email')
    public static function make(array $data): self
    {
        return new self($data);
    }

    // ─── Field is required ────────────────────────────────────────────
    public function required(string $field): self
    {
        if (empty($this->data[$field]) && $this->data[$field] !== '0') {
            $this->errors[$field][] = "The {$field} field is required.";
        }
        return $this;
    }

    // ─── Must be a valid email ────────────────────────────────────────
    public function email(string $field): self
    {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "The {$field} field must be a valid email.";
        }
        return $this;
    }

    // ─── Minimum string length ────────────────────────────────────────
    public function min(string $field, int $min): self
    {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field][] = "The {$field} field must be at least {$min} characters.";
        }
        return $this;
    }

    // ─── Maximum string length ────────────────────────────────────────
    public function max(string $field, int $max): self
    {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field][] = "The {$field} field must not exceed {$max} characters.";
        }
        return $this;
    }

    // ─── Must be a number ─────────────────────────────────────────────
    public function numeric(string $field): self
    {
        if (!empty($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field][] = "The {$field} field must be a number.";
        }
        return $this;
    }

    // ─── Must be a positive number ────────────────────────────────────
    public function positive(string $field): self
    {
        if (!empty($this->data[$field]) && $this->data[$field] <= 0) {
            $this->errors[$field][] = "The {$field} field must be a positive number.";
        }
        return $this;
    }

    // ─── Must be one of the given values ──────────────────────────────
    public function in(string $field, array $values): self
    {
        if (!empty($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $allowed = implode(', ', $values);
            $this->errors[$field][] = "The {$field} field must be one of: {$allowed}.";
        }
        return $this;
    }

    // ─── Must be an array ─────────────────────────────────────────────
    public function isArray(string $field): self
    {
        if (!empty($this->data[$field]) && !is_array($this->data[$field])) {
            $this->errors[$field][] = "The {$field} field must be an array.";
        }
        return $this;
    }

    // ─── Check if validation failed ───────────────────────────────────
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    // ─── Check if validation passed ───────────────────────────────────
    public function passes(): bool
    {
        return empty($this->errors);
    }

    // ─── Get all errors ───────────────────────────────────────────────
    public function errors(): array
    {
        return $this->errors;
    }
}


/*

$v = Validator::make($this->all())
        ->required('email')
        ->email('email')
        ->required('password')
        ->min('password', 6);

if ($v->fails()) {
    Response::error('Validation failed', 422, $v->errors());
}


*/