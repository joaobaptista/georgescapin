<?php

namespace App\Core;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $validated = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->validate();
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $ruleList = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            $isRequired = in_array('required', $ruleList);
            $isSometimes = in_array('sometimes', $ruleList);

            if ($isSometimes && !array_key_exists($field, $this->data)) {
                continue;
            }

            if ($isRequired && ($value === null || $value === '')) {
                $this->addError($field, "O campo {$field} é obrigatório.");
                continue;
            }

            if ($value === null || $value === '') {
                if (in_array('nullable', $ruleList)) {
                    $this->validated[$field] = null;
                    continue;
                }
            }

            foreach ($ruleList as $rule) {
                if ($rule === 'required' || $rule === 'nullable' || $rule === 'sometimes') {
                    continue;
                }

                if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "O campo {$field} deve ser um e-mail válido.");
                }

                if ($rule === 'string' && !is_string($value)) {
                    $this->addError($field, "O campo {$field} deve ser um texto.");
                }

                $comparesAsNumber = in_array('numeric', $ruleList, true) || in_array('integer', $ruleList, true);

                if (str_starts_with($rule, 'min:')) {
                    $min = (int) substr($rule, 4);
                    if (!$comparesAsNumber && is_string($value) && mb_strlen($value) < $min) {
                        $this->addError($field, "O campo {$field} deve ter pelo menos {$min} caracteres.");
                    } elseif ($comparesAsNumber && is_numeric($value) && $value < $min) {
                        $this->addError($field, "O campo {$field} deve ser no mínimo {$min}.");
                    }
                }

                if (str_starts_with($rule, 'max:')) {
                    $max = (int) substr($rule, 4);
                    if (!$comparesAsNumber && is_string($value) && mb_strlen($value) > $max) {
                        $this->addError($field, "O campo {$field} não pode ser maior que {$max} caracteres.");
                    } elseif ($comparesAsNumber && is_numeric($value) && $value > $max) {
                        $this->addError($field, "O campo {$field} não pode ser maior que {$max}.");
                    }
                }

                if (str_starts_with($rule, 'in:')) {
                    $allowed = explode(',', substr($rule, 3));
                    if (!in_array($value, $allowed, true)) {
                        $this->addError($field, "O valor selecionado para {$field} é inválido.");
                    }
                }

                if ($rule === 'numeric' && !is_numeric($value)) {
                    $this->addError($field, "O campo {$field} deve ser numérico.");
                }

                if ($rule === 'integer' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "O campo {$field} deve ser um número inteiro.");
                }

                if ($rule === 'confirmed') {
                    $confirmationField = $field . '_confirmation';
                    $confirmationValue = $this->data[$confirmationField] ?? null;
                    if ($value !== $confirmationValue) {
                        $this->addError($field, "A confirmação do campo {$field} não confere.");
                    }
                }
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        if ($this->fails()) {
            Response::error('Erro de validação.', 422, $this->errors);
        }
        return $this->validated;
    }
}
