<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\ApplePay;

use Exception;

class CertificateException extends Exception
{
    public const ERROR_ID_LOAD_ERROR = 'LOAD_CERTIFICATE_ERROR';
    public const ERROR_ID_TRANSFORMATION_ERROR = 'CERTIFICATE_TRANSFORMATION_ERROR';

    private string $errorId;

    /**
     * @param string $message
     * @param string|null $errorId
     */
    public function __construct(string $message, string $errorId = null)
    {
        parent::__construct($message);
        $this->errorId = empty($errorId) ? $message : $errorId;
    }

    public function getErrorId(): string
    {
        return $this->errorId;
    }
}
