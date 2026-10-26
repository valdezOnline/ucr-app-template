<?php

namespace Modules\Core\Domain\Notifications\Data;

use InvalidArgumentException;

class EmailMessage
{
    /**
     * @param array<int, string> $to
     * @param array<int, string> $cc
     * @param array<int, string> $bcc
     * @param array<int, string> $replyTo
     */
    public function __construct(
        public readonly array $to,
        public readonly string $subject,
        public readonly ?string $htmlBody = null,
        public readonly ?string $textBody = null,
        public readonly array $cc = [],
        public readonly array $bcc = [],
        public readonly array $replyTo = [],
        public readonly ?string $fromEmail = null,
        public readonly ?string $fromName = null,
    ) {
        if ($this->to === []) {
            throw new InvalidArgumentException('At least one recipient is required.');
        }

        if ($this->htmlBody === null && $this->textBody === null) {
            throw new InvalidArgumentException('Either htmlBody or textBody must be provided.');
        }
    }
}
