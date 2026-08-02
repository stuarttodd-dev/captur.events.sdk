<?php

declare(strict_types=1);

namespace Captur\Exceptions;

use Exception;
use Throwable;

class CapturException extends Exception
{
    public function __construct(
        string $message,
        protected ?string $errorCode = null,
        protected ?string $errorType = null,
        protected ?string $parameter = null,
        protected ?int $status = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function code(): ?string
    {
        return $this->errorCode;
    }

    public function type(): ?string
    {
        return $this->errorType;
    }

    public function parameter(): ?string
    {
        return $this->parameter;
    }

    public function status(): ?int
    {
        return $this->status;
    }
}
