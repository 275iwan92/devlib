<?php

namespace Devlib\Assistant;

// Bawaan bila aplikasi tidak mengikat model bahasa.
final class NullModel implements LanguageModel
{
    public function available(): bool
    {
        return false;
    }

    public function choose(string $question, array $options, array $history = []): ?string
    {
        return null;
    }
}
