<?php
namespace Savv\Utils;

/**
 * Performs lightweight rule-based validation on associative input arrays.
 *
 * Supported rules currently include `required`, `email`, `min`, `max`,
 * `numeric`, and `url`.
 */
class Validator {
    /**
     * Validation errors keyed by field name.
     *
     * @var array<string, string>
     */
    private static $errors = [];

    /**
     * Validate a data payload against a set of pipe-delimited rules.
     *
     * @param array<string, mixed> $data Input data to validate.
     * @param array<string, string> $rules Validation rules keyed by field name.
     * @return bool True when validation passes with no errors.
     */
    public static function validate(array $data, array $rules) {
        self::$errors = []; // Reset errors on each call

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $ruleList = explode('|', $fieldRules);

            foreach ($ruleList as $rule) {
                // Handle rules with parameters like min:3 or max:10
                $params = [];
                if (strpos($rule, ':') !== false) {
                    list($rule, $paramString) = explode(':', $rule);
                    $params = explode(',', $paramString);
                }

                self::applyRule($field, $value, $rule, $params);
            }
        }
        
        return empty(self::$errors);
    }

    /**
     * Apply a single validation rule to a field value.
     *
     * @param string $field Field name currently being validated.
     * @param mixed $value Current field value.
     * @param string $rule Validation rule name.
     * @param array<int, string> $params Optional rule parameters.
     * @return void
     */
    private static function applyRule($field, $value, $rule, $params) {
        $fieldName = ucfirst($field);

        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    self::$errors[$field] = "{$fieldName} is required.";
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    self::$errors[$field] = "Invalid email format.";
                }
                break;

            case 'min':
                $min = $params[0];
                if (!empty($value) && strlen($value) < $min) {
                    self::$errors[$field] = "{$fieldName} must be at least {$min} characters.";
                }
                break;

            case 'max':
                $max = $params[0];
                if (!empty($value) && strlen($value) > $max) {
                    self::$errors[$field] = "{$fieldName} cannot exceed {$max} characters.";
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    self::$errors[$field] = "{$fieldName} must be a number.";
                }
                break;
                
            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    self::$errors[$field] = "{$fieldName} must be a valid URL.";
                }
                break;
        }
    }

    /**
     * Return the validation errors collected during the last validation run.
     *
     * @return array<string, string> Validation errors keyed by field name.
     */
    public static function getErrors() {
        return self::$errors;
    }
}
