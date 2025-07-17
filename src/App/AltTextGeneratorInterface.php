<?php

namespace ContextualAltText\App;

interface AltTextGeneratorInterface
{
    public function altText(int $imageId): string;
}
