<?php

namespace App\Services\Traffic;

use Kassner\LogParser\LogParser;
use Kassner\LogParser\FormatException;

class NginxLogParser
{
    protected LogParser $parser;

    public function __construct()
    {
        $this->parser = new LogParser();

        // Nginx combined format:
        // IP - user [time] "request" status bytes "referer" "user-agent"
        $this->parser->setFormat('%h %l %u %t "%r" %>s %b "%{Referer}i" "%{User-Agent}i"');
    }

    public function parse(string $line): ?array
    {
        try {
            $entry = $this->parser->parse($line);
        } catch (FormatException) {
            return null;
        }

        [$method, $path, $protocol] = $this->parseRequest($entry->request ?? '');

        return [
            'ip' => $entry->host ?? null,
            'time' => $entry->time ?? null,
            'timestamp' => $entry->stamp ?? null,
            'method' => $method,
            'path' => $path,
            'protocol' => $protocol,
            'status' => (int) ($entry->status ?? 0),
            'bytes' => (int) ($entry->responseBytes ?? 0),
            'referer' => $this->normalizeHeader($entry->HeaderReferer ?? null),
            'user_agent' => $this->normalizeHeader($entry->HeaderUserAgent ?? null),
            'raw' => $line,
        ];
    }

    protected function parseRequest(string $request): array
    {
        $parts = preg_split('/\s+/', trim($request), 3);

        return [
            $parts[0] ?? null,
            $parts[1] ?? null,
            $parts[2] ?? null,
        ];
    }

    private function normalizeHeader(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' || $value === '-' ? null : $value;
    }
}