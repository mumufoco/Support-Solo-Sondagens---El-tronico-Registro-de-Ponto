<?php

namespace App\Services;

/**
 * SMS Service
 *
 * Handles SMS verification codes for electronic signatures
 * Can be integrated with Twilio, AWS SNS, or other SMS providers
 */
class SMSService
{
    protected string $provider;
    protected array $codes = []; // In-memory storage (use Redis/Database in production)
    protected int $codeExpiry = 300; // 5 minutes

    public function __construct()
    {
        $this->provider = env('SMS_PROVIDER', 'mock'); // mock, twilio, aws_sns
    }

    /**
     * Send verification code via SMS
     *
     * @param int $employeeId
     * @param string $phone
     * @return array ['success' => bool, 'message' => string]
     */
    public function sendVerificationCode(int $employeeId, string $phone): array
    {
        try {
            // Generate 6-digit code
            $code = $this->generateCode();

            // Store code with expiry
            $this->storeCode($employeeId, $code);

            // Send SMS based on provider
            $result = $this->sendSMS($phone, $code);

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Código de verificação enviado para ' . $this->maskPhone($phone),
                    'expires_in' => $this->codeExpiry
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro ao enviar SMS: ' . ($result['error'] ?? 'Erro desconhecido')
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao enviar código: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify SMS code
     *
     * @param int $employeeId
     * @param string $code
     * @return array ['success' => bool, 'message' => string]
     */
    public function verifyCode(int $employeeId, string $code): array
    {
        $storedData = $this->getStoredCode($employeeId);

        if (!$storedData) {
            return [
                'success' => false,
                'message' => 'Nenhum código encontrado. Solicite um novo código.'
            ];
        }

        // Check expiry
        if (time() > $storedData['expiry']) {
            $this->deleteCode($employeeId);
            return [
                'success' => false,
                'message' => 'Código expirado. Solicite um novo código.'
            ];
        }

        // Verify code
        if ($storedData['code'] !== $code) {
            return [
                'success' => false,
                'message' => 'Código inválido. Verifique e tente novamente.'
            ];
        }

        // Code is valid, delete it (one-time use)
        $this->deleteCode($employeeId);

        return [
            'success' => true,
            'message' => 'Código verificado com sucesso.'
        ];
    }

    /**
     * Generate 6-digit code
     *
     * @return string
     */
    protected function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Store code with expiry
     *
     * @param int $employeeId
     * @param string $code
     * @return void
     */
    protected function storeCode(int $employeeId, string $code): void
    {
        // In production, use Redis or Database
        // For now, use class property (will not persist across requests)

        // Simulate database storage
        $cache = \Config\Services::cache();
        $cache->save('sms_code_' . $employeeId, [
            'code' => $code,
            'expiry' => time() + $this->codeExpiry,
            'attempts' => 0
        ], $this->codeExpiry);
    }

    /**
     * Get stored code
     *
     * @param int $employeeId
     * @return array|null
     */
    protected function getStoredCode(int $employeeId): ?array
    {
        $cache = \Config\Services::cache();
        return $cache->get('sms_code_' . $employeeId);
    }

    /**
     * Delete code
     *
     * @param int $employeeId
     * @return void
     */
    protected function deleteCode(int $employeeId): void
    {
        $cache = \Config\Services::cache();
        $cache->delete('sms_code_' . $employeeId);
    }

    /**
     * Send SMS based on provider
     *
     * @param string $phone
     * @param string $code
     * @return array
     */
    protected function sendSMS(string $phone, string $code): array
    {
        switch ($this->provider) {
            case 'twilio':
                return $this->sendTwilioSMS($phone, $code);

            case 'aws_sns':
                return $this->sendAWSSNS($phone, $code);

            case 'mock':
            default:
                return $this->sendMockSMS($phone, $code);
        }
    }

    /**
     * Send SMS via Twilio
     *
     * @param string $phone
     * @param string $code
     * @return array
     */
    protected function sendTwilioSMS(string $phone, string $code): array
    {
        try {
            $accountSid = env('TWILIO_ACCOUNT_SID');
            $authToken = env('TWILIO_AUTH_TOKEN');
            $twilioNumber = env('TWILIO_PHONE_NUMBER');

            if (!$accountSid || !$authToken || !$twilioNumber) {
                return [
                    'success' => false,
                    'error' => 'Twilio não configurado corretamente.'
                ];
            }

            // Use Guzzle HTTP client to call Twilio API
            $client = \Config\Services::curlrequest();

            $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";

            $response = $client->post($url, [
                'auth' => [$accountSid, $authToken],
                'form_params' => [
                    'To' => $phone,
                    'From' => $twilioNumber,
                    'Body' => "Seu código de verificação é: {$code}\nVálido por 5 minutos."
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);

            if ($statusCode !== 201) {
                log_message('error', 'Twilio SMS failed: ' . ($body['message'] ?? 'Unknown error'));
                return [
                    'success' => false,
                    'error' => 'Falha ao enviar SMS via Twilio: ' . ($body['message'] ?? 'Unknown error')
                ];
            }

            log_message('info', "SMS sent via Twilio to {$phone}, SID: {$body['sid']}");

            return [
                'success' => true,
                'provider' => 'twilio',
                'message_sid' => $body['sid'] ?? null
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send SMS via AWS SNS
     *
     * @param string $phone
     * @param string $code
     * @return array
     */
    protected function sendAWSSNS(string $phone, string $code): array
    {
        try {
            $awsKey = env('AWS_ACCESS_KEY_ID');
            $awsSecret = env('AWS_SECRET_ACCESS_KEY');
            $awsRegion = env('AWS_REGION', 'us-east-1');

            if (!$awsKey || !$awsSecret) {
                return [
                    'success' => false,
                    'error' => 'AWS SNS não configurado corretamente.'
                ];
            }

            // Use Guzzle HTTP client with AWS Signature V4
            $client = \Config\Services::curlrequest();

            $message = "Seu código de verificação é: {$code}\nVálido por 5 minutos.";

            // AWS SNS endpoint
            $endpoint = "https://sns.{$awsRegion}.amazonaws.com/";

            // Prepare request parameters
            $params = [
                'Action' => 'Publish',
                'Message' => $message,
                'PhoneNumber' => $phone,
                'Version' => '2010-03-31'
            ];

            // Sign request with AWS Signature Version 4
            $signedHeaders = $this->signAWSRequest(
                'POST',
                $endpoint,
                $params,
                $awsKey,
                $awsSecret,
                $awsRegion
            );

            // Make request
            $response = $client->post($endpoint, [
                'headers' => $signedHeaders,
                'form_params' => $params
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody();

            if ($statusCode !== 200) {
                log_message('error', 'AWS SNS SMS failed: ' . $body);
                return [
                    'success' => false,
                    'error' => 'Falha ao enviar SMS via AWS SNS'
                ];
            }

            // Parse XML response
            $xml = simplexml_load_string($body);
            $messageId = (string)$xml->PublishResult->MessageId ?? null;

            log_message('info', "SMS sent via AWS SNS to {$phone}, MessageId: {$messageId}");

            return [
                'success' => true,
                'provider' => 'aws_sns',
                'message_id' => $messageId
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Mock SMS sending (for development/testing)
     *
     * @param string $phone
     * @param string $code
     * @return array
     */
    protected function sendMockSMS(string $phone, string $code): array
    {
        // Log to file instead of actually sending SMS
        $logFile = WRITEPATH . 'logs/sms_mock.log';
        $message = sprintf(
            "[%s] SMS para %s: Seu código de verificação é: %s (válido por 5 minutos)\n",
            date('Y-m-d H:i:s'),
            $phone,
            $code
        );

        file_put_contents($logFile, $message, FILE_APPEND);

        // In development, also log to error_log for easy viewing
        log_message('info', "SMS Mock: Code {$code} sent to {$phone}");

        return [
            'success' => true,
            'provider' => 'mock',
            'code' => $code // Only return code in mock mode
        ];
    }

    /**
     * Mask phone number for display
     *
     * @param string $phone
     * @return string
     */
    protected function maskPhone(string $phone): string
    {
        // Example: (11) 98765-4321 -> (11) ****-4321
        $cleaned = preg_replace('/\D/', '', $phone);

        if (strlen($cleaned) === 11) {
            // Mobile: (XX) 9XXXX-XXXX
            return '(' . substr($cleaned, 0, 2) . ') ****-' . substr($cleaned, -4);
        } elseif (strlen($cleaned) === 10) {
            // Landline: (XX) XXXX-XXXX
            return '(' . substr($cleaned, 0, 2) . ') ****-' . substr($cleaned, -4);
        }

        return '****-' . substr($cleaned, -4);
    }

    /**
     * Get rate limit info for employee
     *
     * @param int $employeeId
     * @return array ['attempts' => int, 'can_send' => bool, 'wait_seconds' => int]
     */
    public function getRateLimitInfo(int $employeeId): array
    {
        $cache = \Config\Services::cache();
        $key = 'sms_rate_limit_' . $employeeId;
        $data = $cache->get($key);

        if (!$data) {
            return [
                'attempts' => 0,
                'can_send' => true,
                'wait_seconds' => 0
            ];
        }

        $maxAttempts = 3; // Max 3 SMS per hour
        $windowSeconds = 3600; // 1 hour

        $canSend = $data['attempts'] < $maxAttempts;
        $waitSeconds = $canSend ? 0 : ($data['first_attempt'] + $windowSeconds - time());

        return [
            'attempts' => $data['attempts'],
            'can_send' => $canSend,
            'wait_seconds' => max(0, $waitSeconds)
        ];
    }

    /**
     * Increment rate limit counter
     *
     * @param int $employeeId
     * @return void
     */
    protected function incrementRateLimit(int $employeeId): void
    {
        $cache = \Config\Services::cache();
        $key = 'sms_rate_limit_' . $employeeId;
        $data = $cache->get($key);

        if (!$data) {
            $data = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
        } else {
            $data['attempts']++;
        }

        $cache->save($key, $data, 3600); // 1 hour
    }

    /**
     * Sign AWS request with Signature Version 4
     *
     * @param string $method HTTP method
     * @param string $endpoint AWS endpoint URL
     * @param array $params Request parameters
     * @param string $accessKey AWS access key
     * @param string $secretKey AWS secret key
     * @param string $region AWS region
     * @return array Signed headers
     */
    protected function signAWSRequest(
        string $method,
        string $endpoint,
        array $params,
        string $accessKey,
        string $secretKey,
        string $region
    ): array {
        $service = 'sns';
        $algorithm = 'AWS4-HMAC-SHA256';
        $dateTime = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');

        // Create canonical request
        $parsedUrl = parse_url($endpoint);
        $host = $parsedUrl['host'];
        $uri = $parsedUrl['path'] ?? '/';

        // Canonical query string
        ksort($params);
        $canonicalQueryString = http_build_query($params);

        // Canonical headers
        $canonicalHeaders = "content-type:application/x-www-form-urlencoded\n";
        $canonicalHeaders .= "host:{$host}\n";
        $canonicalHeaders .= "x-amz-date:{$dateTime}\n";

        $signedHeaders = 'content-type;host;x-amz-date';

        // Payload hash
        $payloadHash = hash('sha256', $canonicalQueryString);

        // Canonical request
        $canonicalRequest = implode("\n", [
            $method,
            $uri,
            '',  // query string (empty for POST)
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash
        ]);

        // Create string to sign
        $credentialScope = "{$date}/{$region}/{$service}/aws4_request";
        $stringToSign = implode("\n", [
            $algorithm,
            $dateTime,
            $credentialScope,
            hash('sha256', $canonicalRequest)
        ]);

        // Calculate signature
        $kDate = hash_hmac('sha256', $date, 'AWS4' . $secretKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        // Create authorization header
        $authorization = "{$algorithm} ";
        $authorization .= "Credential={$accessKey}/{$credentialScope}, ";
        $authorization .= "SignedHeaders={$signedHeaders}, ";
        $authorization .= "Signature={$signature}";

        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Host' => $host,
            'X-Amz-Date' => $dateTime,
            'Authorization' => $authorization
        ];
    }
}
