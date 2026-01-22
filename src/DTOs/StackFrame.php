<?php

namespace Dinithoshan\Racelab\DTOs;

final class StackFrame
{
    public function __construct(
        public readonly ?string $file,
        public readonly ?int $line,
        public readonly ?string $class,
        public readonly ?string $function,
        public readonly ?bool $isVendor = false,
    ) {}


    public static function fromBacktraceFrame(array $frame): self
    {
        return new self(
            $frame['file'] ?? null,
            isset($frame['line']) ? (int)$frame['line'] : null,
            $frame['class'] ?? null,
            $frame['function'] ?? null,
            str_contains($frame['file'] ?? '', '/vendor/') || str_contains($frame['file'] ?? '', '\\\\vendor\\\\'),
        );
    }


    public function isVendor(): bool
    {
        return $this->isVendor;
    }


    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'line' => $this->line,
            'class' => $this->class,
            'function' => $this->function,
            'is_vendor' => $this->isVendor,
        ];
    }
}