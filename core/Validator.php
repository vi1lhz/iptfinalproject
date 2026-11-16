<?php

namespace Core;

/**
 * Validator – checks input against pipe-separated rules:
 *
 *   Validator::make($data, ['email' => 'required|email|unique:users,email', 'price' => 'required|numeric|min:0.5']);
 */
class Validator
{
    private array $errors = [];

    public function __construct(private array $data, private array $rules)
    {
    }

    public static function make(array $data, array $rules): self
    {
        $validator = new self($data, $rules);
        $validator->run();
        return $validator;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $label = ucfirst(str_replace('_', ' ', $field));

            foreach (explode('|', $ruleString) as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);

                // Optional fields skip the other rules when empty.
                if ($name !== 'required' && ($value === null || $value === '')) {
                    continue;
                }

                $error = $this->check($name, $arg, $value, $field, $label);
                if ($error !== null) {
                    $this->errors[$field] = $error;
                    break; // one message per field
                }
            }
        }
    }

    private function check(string $rule, ?string $arg, mixed $value, string $field, string $label): ?string
    {
        $isNumeric = is_numeric($value);
        $size = $isNumeric ? (float) $value : mb_strlen((string) $value);

        return match ($rule) {
            'required'  => ($value === null || $value === '') ? "$label is required." : null,
            'email'     => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "$label must be a valid email address.",
            'numeric'   => $isNumeric ? null : "$label must be a number.",
            'integer'   => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : "$label must be a whole number.",
            'min'       => $size >= (float) $arg ? null : ($isNumeric ? "$label must be at least $arg." : "$label must be at least $arg characters."),
            'max'       => $size <= (float) $arg ? null : ($isNumeric ? "$label may not be greater than $arg." : "$label may not be longer than $arg characters."),
            'in'        => in_array((string) $value, explode(',', (string) $arg), true) ? null : "$label is not a valid option.",
            'confirmed' => ($value === ($this->data[$field . '_confirmation'] ?? null)) ? null : "$label confirmation does not match.",
            'alpha_dash'=> preg_match('/^[a-zA-Z0-9_-]+$/', (string) $value) ? null : "$label may only contain letters, numbers, dashes and underscores.",
            'unique'    => $this->isUnique((string) $arg, $value) ? null : "$label is already taken.",
            'exists'    => $this->exists((string) $arg, $value) ? null : "Selected $label does not exist.",
            default     => throw new \InvalidArgumentException("Unknown validation rule: $rule"),
        };
    }

    /** unique:table,column[,ignoreId] */
    private function isUnique(string $arg, mixed $value): bool
    {
        [$table, $column, $ignoreId] = array_pad(explode(',', $arg), 3, null);
        $sql = "SELECT COUNT(*) FROM $table WHERE $column = ?";
        $params = [$value];
        if ($ignoreId) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        return (int) Database::getInstance()->fetchValue($sql, $params) === 0;
    }

    /** exists:table,column */
    private function exists(string $arg, mixed $value): bool
    {
        [$table, $column] = array_pad(explode(',', $arg), 2, 'id');
        return (int) Database::getInstance()->fetchValue("SELECT COUNT(*) FROM $table WHERE $column = ?", [$value]) > 0;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
