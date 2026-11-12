<?php

declare(strict_types=1);

namespace App\Http\Validation;

/** Input shape validation (types, sizes, formats). Business rules live in the domain. */
final class Validator
{
    /**
     * @param array<string, string> $rules e.g. ['email' => 'required|email|max:255']
     * @return array<string, mixed> only the validated fields
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $ruleString) {
            $ruleList = explode('|', $ruleString);
            $present = array_key_exists($field, $data);
            $value = $data[$field] ?? null;
            $empty = $value === null || $value === '';
            $numeric = in_array('numeric', $ruleList, true) || in_array('integer', $ruleList, true);

            if (in_array('sometimes', $ruleList, true) && !$present) {
                continue;
            }
            if ($empty) {
                if (in_array('required', $ruleList, true)) {
                    $errors[$field][] = "O campo {$field} é obrigatório.";
                } elseif (in_array('nullable', $ruleList, true)) {
                    $validated[$field] = null;
                }
                continue;
            }

            foreach ($ruleList as $rule) {
                [$name, $argument] = array_pad(explode(':', $rule, 2), 2, null);
                $message = match ($name) {
                    'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "O campo {$field} deve ser um e-mail válido.",
                    'string' => is_string($value) ? null : "O campo {$field} deve ser um texto.",
                    'numeric' => is_numeric($value) ? null : "O campo {$field} deve ser numérico.",
                    'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : "O campo {$field} deve ser um número inteiro.",
                    'in' => in_array($value, explode(',', (string) $argument), true) ? null : "O valor selecionado para {$field} é inválido.",
                    'confirmed' => $value === ($data["{$field}_confirmation"] ?? null) ? null : "A confirmação do campo {$field} não confere.",
                    'min' => self::checkSize($field, $value, (int) $argument, $numeric, true),
                    'max' => self::checkSize($field, $value, (int) $argument, $numeric, false),
                    default => null,
                };
                if ($message !== null) {
                    $errors[$field][] = $message;
                }
            }

            if (!isset($errors[$field])) {
                $validated[$field] = $value;
            }
        }

        if ($errors !== []) {
            throw new ValidationFailed($errors);
        }

        return $validated;
    }

    private static function checkSize(string $field, mixed $value, int $limit, bool $numeric, bool $isMin): ?string
    {
        if ($numeric) {
            if (!is_numeric($value)) {
                return null;
            }
            $fails = $isMin ? $value < $limit : $value > $limit;

            return $fails ? ($isMin ? "O campo {$field} deve ser no mínimo {$limit}." : "O campo {$field} não pode ser maior que {$limit}.") : null;
        }
        if (!is_string($value)) {
            return null;
        }
        $fails = $isMin ? mb_strlen($value) < $limit : mb_strlen($value) > $limit;

        return $fails ? ($isMin ? "O campo {$field} deve ter pelo menos {$limit} caracteres." : "O campo {$field} não pode ser maior que {$limit} caracteres.") : null;
    }
}
