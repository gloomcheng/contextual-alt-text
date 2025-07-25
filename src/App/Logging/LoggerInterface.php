<?php

namespace ContextualAltText\App\Logging;

interface LoggerInterface
{
    public function writeImageLog(int $imageId, string $errorMessage): void;
    public function getImageLog(): string;
}
