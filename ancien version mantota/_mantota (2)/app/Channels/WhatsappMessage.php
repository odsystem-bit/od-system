<?php

declare(strict_types=1);

namespace App\Channels;

class WhatsappMessage
{
    public function __construct(
        public readonly string $content,
    ) {}
}
