<?php

namespace App\Support;

readonly class LinkMetadata
{
    public function __construct(
        public string $title = '',
        public string $faviconUrl = '',
    ) {}
}
