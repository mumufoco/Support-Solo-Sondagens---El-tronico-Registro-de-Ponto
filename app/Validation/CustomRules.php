<?php

namespace App\Validation;

/**
 * Custom Validation Rules
 *
 * Custom validation rules for Brazilian standards and application-specific needs
 */
class CustomRules
{
    /**
     * Validate CPF (Brazilian tax ID)
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_cpf(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        // Load helper
        helper('cpf');

        return validate_cpf($value);
    }

    /**
     * Validate CNPJ (Brazilian company tax ID)
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_cnpj(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        // Load helper
        helper('cpf');

        return validate_cnpj($value);
    }

    /**
     * Validate strong password
     * Must contain: min 8 chars, uppercase, lowercase, number, special char
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function strong_password(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        // Load helper
        helper('security');

        $result = verify_password_strength($value);

        return $result['valid'];
    }

    /**
     * Validate Brazilian phone number
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_phone_br(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        // Remove formatting
        $phone = preg_replace('/[^0-9]/', '', $value);

        // Valid lengths: 10 (landline) or 11 (mobile)
        return in_array(strlen($phone), [10, 11]);
    }

    /**
     * Validate Brazilian postal code (CEP)
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_cep(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        // Remove formatting
        $cep = preg_replace('/[^0-9]/', '', $value);

        return strlen($cep) === 8;
    }

    /**
     * Validate coordinates (latitude/longitude)
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_coordinates(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        // Expect format: "lat,lng"
        $parts = explode(',', $value);

        if (count($parts) !== 2) {
            return false;
        }

        $lat = (float) trim($parts[0]);
        $lng = (float) trim($parts[1]);

        // Validate ranges
        if ($lat < -90 || $lat > 90) {
            return false;
        }

        if ($lng < -180 || $lng > 180) {
            return false;
        }

        return true;
    }

    /**
     * Validate latitude
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_latitude(?string $value, ?string $params, array $data): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $lat = (float) $value;

        return $lat >= -90 && $lat <= 90;
    }

    /**
     * Validate longitude
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_longitude(?string $value, ?string $params, array $data): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $lng = (float) $value;

        return $lng >= -180 && $lng <= 180;
    }

    /**
     * Validate Brazilian date format (dd/mm/yyyy)
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_date_br(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        // Load helper
        helper('datetime');

        $parsed = parse_date_br($value);

        return $parsed !== null;
    }

    /**
     * Validate time format (HH:MM or HH:MM:SS)
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_time(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        // Check HH:MM format
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $value)) {
            return true;
        }

        // Check HH:MM:SS format
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $value)) {
            return true;
        }

        return false;
    }

    /**
     * Validate base64 image
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_base64_image(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        // Remove data URI prefix if present
        if (strpos($value, 'data:image') === 0) {
            $value = preg_replace('/^data:image\/\w+;base64,/', '', $value);
        }

        // Decode base64
        $imageData = base64_decode($value, true);

        if ($imageData === false) {
            return false;
        }

        // Verify it's a valid image
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);

        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

        return in_array($mimeType, $allowedTypes);
    }

    /**
     * Validate if value is a valid JSON
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_json(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate punch type
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_punch_type(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        $validTypes = ['entrada', 'saida', 'intervalo_inicio', 'intervalo_fim'];

        return in_array($value, $validTypes);
    }

    /**
     * Validate employee role
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_employee_role(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        $validRoles = ['admin', 'gestor', 'funcionario'];

        return in_array($value, $validRoles);
    }

    /**
     * Validate hex color
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_hex_color(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value) === 1;
    }

    /**
     * Validate age (minimum age requirement)
     * Format: valid_min_age[18]
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_min_age(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        // Load helper
        helper('datetime');

        $age = calculate_age($value);
        $minAge = (int) ($params ?? 18);

        return $age >= $minAge;
    }

    /**
     * Validate future date
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function is_future_date(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        try {
            $date = new \DateTime($value);
            $today = new \DateTime('today');

            return $date > $today;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate past date
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function is_past_date(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        try {
            $date = new \DateTime($value);
            $today = new \DateTime('today');

            return $date < $today;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate business day
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function is_business_day(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        // Load helper
        helper('datetime');

        return is_business_day($value);
    }

    /**
     * Validate unique employee code (excluding specific ID)
     * Format: unique_employee_code[employee_id]
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function unique_employee_code(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        $employeeModel = new \App\Models\EmployeeModel();

        $builder = $employeeModel->where('unique_code', $value);

        // Exclude specific ID if provided
        if (!empty($params)) {
            $builder->where('id !=', $params);
        }

        return $builder->countAllResults() === 0;
    }

    /**
     * Validate decimal hours (max 24)
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_hours(?string $value, ?string $params, array $data): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $hours = (float) $value;

        return $hours >= 0 && $hours <= 24;
    }

    /**
     * Validate percentage (0-100)
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_percentage(?string $value, ?string $params, array $data): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $percentage = (float) $value;

        return $percentage >= 0 && $percentage <= 100;
    }

    /**
     * Validate file size in bytes
     * Format: max_file_size[5242880] (5MB in bytes)
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function max_file_size(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        // If base64 encoded
        if (strpos($value, 'data:') === 0) {
            $value = preg_replace('/^data:.*base64,/', '', $value);
        }

        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return false;
        }

        $size = strlen($decoded);
        $maxSize = (int) ($params ?? 5242880); // Default 5MB

        return $size <= $maxSize;
    }

    /**
     * Validate IP address (IPv4 or IPv6)
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_ip_address(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate URL with specific protocols
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_url_safe(?string $value, ?string $params, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        // Check if valid URL
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        // Check protocol
        $allowedProtocols = ['http', 'https'];
        $protocol = parse_url($value, PHP_URL_SCHEME);

        return in_array($protocol, $allowedProtocols);
    }

    /**
     * Validate if value exists in database
     * Format: exists[table.field,value]
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function exists(?string $value, ?string $params, array $data): bool
    {
        if (empty($value) || empty($params)) {
            return false;
        }

        $parts = explode('.', $params);

        if (count($parts) !== 2) {
            return false;
        }

        [$table, $field] = $parts;

        $db = \Config\Database::connect();
        $builder = $db->table($table);

        return $builder->where($field, $value)->countAllResults() > 0;
    }

    /**
     * Validate NSR (Número Sequencial de Registro)
     *
     * @param string|null $value
     * @param string|null $params
     * @param array $data
     * @return bool
     */
    public function valid_nsr(?string $value, ?string $params, array $data): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $nsr = (int) $value;

        return $nsr > 0;
    }
}
