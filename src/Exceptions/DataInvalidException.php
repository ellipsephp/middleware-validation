<?php declare(strict_types=1);

namespace Ellipse\Middleware\Validation\Exceptions;

use Exception;

class DataInvalidException extends Exception implements ValidationExceptionInterface
{
    private $messages;

    public function __construct(array $messages)
    {
        $this->messages = $messages;

        parent::__construct('The request data does not pass the validation rules');
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
