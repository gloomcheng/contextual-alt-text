<?php

namespace ContextualAltText\App\AIProviders;

interface AITranslatorInterface
{
    public function translate(string $text, string $language): string;
}
