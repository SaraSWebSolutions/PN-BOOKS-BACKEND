<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PublicationVerifier
{
    /**
     * Attempt to verify a book's identifier against public catalogs.
     *
     * Returns an array:
     * [
     *   'checked'    => bool,   // whether we were able to attempt a check
     *   'verified'   => bool,   // whether a matching public record was found
     *   'source'     => string|null,
     *   'matched_title'  => string|null,
     *   'matched_author' => string|null,
     *   'note'       => string|null,
     * ]
     */
    public function verify(?string $identifier, ?string $title, array $authors = []): array
    {
        if (! $identifier) {
            return $this->result(false, false, null, null, null, 'No identifier present in EPUB metadata.');
        }

        // 1. Project Gutenberg identifiers, e.g. "http://www.gutenberg.org/2701"
        if (preg_match('#gutenberg\.org/(?:ebooks/)?(\d+)#i', $identifier, $m)) {
            return $this->verifyGutenberg($m[1], $title, $authors);
        }

        // 2. ISBN identifiers, e.g. "urn:isbn:9780141439600" or bare "9780141439600"
        $isbn = $this->extractIsbn($identifier);
        if ($isbn) {
            return $this->verifyIsbn($isbn, $title, $authors);
        }

        // 3. Fallback: no usable identifier scheme — try a title/author search instead
        if ($title) {
            return $this->verifyByTitleAuthor($title, $authors);
        }

        return $this->result(false, false, null, null, null, 'Identifier format not recognized (not ISBN or Gutenberg URI), and no title available for fallback search.');
    }

    private function verifyByTitleAuthor(string $title, array $authors): array
    {
        $query = trim($title . ' ' . implode(' ', $authors));

        $response = Http::timeout(10)->get('https://openlibrary.org/search.json', [
            'q' => $query,
            'limit' => 1,
        ]);

        if (! $response->ok()) {
            return $this->result(true, false, 'openlibrary.org (search)', null, null, 'Lookup request failed.');
        }

        $docs = $response->json('docs') ?? [];
        if (empty($docs)) {
            return $this->result(true, false, 'openlibrary.org (search)', null, null, 'No identifier available, and no matching title/author found in public search.');
        }

        $match = $docs[0];
        $matchedTitle = $match['title'] ?? null;
        $matchedAuthors = $match['author_name'] ?? [];

        return $this->result(
            true,
            true,
            'openlibrary.org (search)',
            $matchedTitle,
            implode(', ', $matchedAuthors),
            'No structured identifier (e.g. ISBN) was present, so this match is based on title/author search only — treat as lower-confidence than an ISBN or Gutenberg match.'
        );
    }
        public function extractIsbnFromIdentifier(?string $identifier): ?string
    {
        if (! $identifier) {
            return null;
        }

        return $this->extractIsbn($identifier);
    }

    private function extractIsbn(string $identifier): ?string
    {
        $digits = preg_replace('/[^0-9Xx]/', '', $identifier);
        $len = strlen($digits);

        if ($len === 13 && $this->isValidIsbn13($digits)) {
            return $digits;
        }
        if ($len === 10 && $this->isValidIsbn10($digits)) {
            return $digits;
        }
        return null;
    }

    private function isValidIsbn13(string $isbn): bool
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        $check = (10 - ($sum % 10)) % 10;
        return $check === (int) $isbn[12];
    }

    private function isValidIsbn10(string $isbn): bool
    {
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $isbn[$i] * (10 - $i);
        }
        $last = strtoupper($isbn[9]) === 'X' ? 10 : (int) $isbn[9];
        $sum += $last;
        return $sum % 11 === 0;
    }

    private function verifyIsbn(string $isbn, ?string $title, array $authors): array
    {
        // Open Library is free, no API key required
        $response = Http::timeout(10)->get('https://openlibrary.org/api/books', [
            'bibkeys' => "ISBN:{$isbn}",
            'format' => 'json',
            'jscmd' => 'data',
        ]);

        if (! $response->ok()) {
            return $this->result(true, false, 'openlibrary.org', null, null, 'Lookup request failed.');
        }

        $data = $response->json();
        $record = $data["ISBN:{$isbn}"] ?? null;

        if (! $record) {
            return $this->result(true, false, 'openlibrary.org', null, null, 'No matching public record found for this ISBN.');
        }

        $matchedTitle = $record['title'] ?? null;
        $matchedAuthors = collect($record['authors'] ?? [])->pluck('name')->all();

        return $this->result(
            true,
            true,
            'openlibrary.org',
            $matchedTitle,
            implode(', ', $matchedAuthors),
            $this->compareNote($title, $authors, $matchedTitle, $matchedAuthors)
        );
    }

    private function verifyGutenberg(string $gutenbergId, ?string $title, array $authors): array
    {
        // Gutendex is a free, community-run Project Gutenberg catalog API
        $response = Http::timeout(10)->get("https://gutendex.com/books/{$gutenbergId}");

        if (! $response->ok()) {
            return $this->result(true, false, 'gutendex.com', null, null, 'Lookup request failed or ID not found.');
        }

        $record = $response->json();
        $matchedTitle = $record['title'] ?? null;
        $matchedAuthors = collect($record['authors'] ?? [])->pluck('name')->all();

        return $this->result(
            true,
            true,
            'gutendex.com',
            $matchedTitle,
            implode(', ', $matchedAuthors),
            $this->compareNote($title, $authors, $matchedTitle, $matchedAuthors)
        );
    }

    private function compareNote(?string $title, array $authors, ?string $matchedTitle, array $matchedAuthors): string
    {
        $titleMatches = $title && $matchedTitle
            && str_contains(strtolower($matchedTitle), strtolower(substr($title, 0, 10)));

        return $titleMatches
            ? 'EPUB metadata title appears consistent with the public catalog record.'
            : 'Public record found, but title does not clearly match EPUB metadata — review manually.';
    }

    private function result(bool $checked, bool $verified, ?string $source, ?string $matchedTitle, ?string $matchedAuthor, ?string $note): array
    {
        return [
            'checked' => $checked,
            'verified' => $verified,
            'source' => $source,
            'matched_title' => $matchedTitle,
            'matched_author' => $matchedAuthor,
            'note' => $note,
        ];
    }
}