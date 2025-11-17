<?php

namespace App\Validation;

/**
 * Custom Validation Rules
 *
 * Regras de validação customizadas para o Sistema de Ponto Eletrônico
 */
class CustomRules
{
    /**
     * Valida tipo de ponto (entrada, saída, pausa, etc.)
     */
    public function valid_punch_type(string $value, string $params = null, array $data = []): bool
    {
        $validTypes = ['entrada', 'saida', 'pausa_inicio', 'pausa_fim', 'extra_entrada', 'extra_saida'];
        return in_array(strtolower($value), $validTypes, true);
    }

    /**
     * Valida latitude
     */
    public function valid_latitude(string $value, string $params = null, array $data = []): bool
    {
        if (empty($value)) {
            return true; // permit_empty handle this
        }

        $lat = floatval($value);
        return $lat >= -90 && $lat <= 90;
    }

    /**
     * Valida longitude
     */
    public function valid_longitude(string $value, string $params = null, array $data = []): bool
    {
        if (empty($value)) {
            return true; // permit_empty handle this
        }

        $lng = floatval($value);
        return $lng >= -180 && $lng <= 180;
    }

    /**
     * Valida imagem base64
     */
    public function valid_base64_image(string $value, string $params = null, array $data = []): bool
    {
        if (empty($value)) {
            return true; // permit_empty handle this
        }

        // Remove data URI scheme if present
        if (strpos($value, 'data:image') === 0) {
            $value = substr($value, strpos($value, ',') + 1);
        }

        // Decode base64
        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return false;
        }

        // Check if it's a valid image
        $imageInfo = @getimagesizefromstring($decoded);

        return $imageInfo !== false;
    }

    /**
     * Valida tamanho máximo de arquivo (em bytes)
     */
    public function max_file_size(string $value, string $params = null, array $data = []): bool
    {
        if (empty($value) || empty($params)) {
            return true;
        }

        // Remove data URI scheme if present
        if (strpos($value, 'data:image') === 0) {
            $value = substr($value, strpos($value, ',') + 1);
        }

        // Calculate base64 decoded size
        $decodedSize = (strlen($value) * 3) / 4;

        // Account for padding
        $paddingCount = substr_count(substr($value, -2), '=');
        $decodedSize -= $paddingCount;

        return $decodedSize <= (int)$params;
    }

    /**
     * Valida senha forte
     */
    public function strong_password(string $value, string $params = null, array $data = []): bool
    {
        if (strlen($value) < 8) {
            return false;
        }

        if (!preg_match('/[A-Z]/', $value)) {
            return false;
        }

        if (!preg_match('/[a-z]/', $value)) {
            return false;
        }

        if (!preg_match('/[0-9]/', $value)) {
            return false;
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $value)) {
            return false;
        }

        return true;
    }

    /**
     * Valida CPF brasileiro
     */
    public function valid_cpf(string $value, string $params = null, array $data = []): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $value);

        if (strlen($cpf) != 11) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        if ((int)$cpf[9] != $digit1) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int)$cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        return (int)$cpf[10] == $digit2;
    }
}
