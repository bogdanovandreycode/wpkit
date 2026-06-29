<?php

declare(strict_types=1);

namespace Wpkit\Controller;

use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class LocaleTranslationController
{
    public function __construct(
        private readonly string $basePath = 'languages',
        private readonly ?string $projectRoot = null
    ) {
    }

    public function addLanguage(string $locale): string
    {
        $path = $this->languagePath($locale);

        if (is_dir($path)) {
            throw new InvalidArgumentException("Language already exists: {$locale}");
        }

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        mkdir($path, 0777, true);

        return $path;
    }

    public function removeLanguage(string $locale): string
    {
        $path = $this->languagePath($locale);

        if (!is_dir($path)) {
            throw new InvalidArgumentException("Language not found: {$locale}");
        }

        $this->assertPathInsideBase($path);
        $this->removeDirectory($path);

        return $path;
    }

    public function addTranslation(
        string $locale,
        string $address,
        string $value,
        ?string $dictionary = null
    ): string {
        return $this->setTranslation($locale, $address, $value, false, $dictionary);
    }

    public function updateTranslation(
        string $locale,
        string $address,
        string $value,
        ?string $dictionary = null
    ): string {
        return $this->setTranslation($locale, $address, $value, true, $dictionary);
    }

    public function removeTranslation(string $locale, string $address, ?string $dictionary = null): string
    {
        $this->assertLanguageExists($locale);
        $entry = $this->resolveEntry($locale, $address, $dictionary, true);
        $dictionaryData = $this->loadDictionary($entry['file']);
        $removed = $this->arrayUnset($dictionaryData, $entry['keys']);

        if (!$removed) {
            throw new InvalidArgumentException("Translation not found: {$address}");
        }

        $this->saveDictionary($entry['file'], $dictionaryData);

        return $entry['file'];
    }

    /**
     * @return array<int, array{locale: string, found: bool, value: mixed, file: string|null}>
     */
    public function listValues(string $address, ?string $dictionary = null): array
    {
        $rows = [];

        foreach ($this->listLanguages() as $locale) {
            try {
                $entry = $this->resolveEntry($locale, $address, $dictionary, true);
                $data = $this->loadDictionary($entry['file']);
                $found = false;
                $value = $this->arrayGet($data, $entry['keys'], $found);

                $rows[] = [
                    'locale' => $locale,
                    'found' => $found,
                    'value' => $value,
                    'file' => $entry['file'],
                ];
            } catch (InvalidArgumentException) {
                $rows[] = [
                    'locale' => $locale,
                    'found' => false,
                    'value' => null,
                    'file' => null,
                ];
            }
        }

        return $rows;
    }

    /**
     * @return string[]
     */
    public function listLanguages(): array
    {
        $basePath = $this->absoluteBasePath();

        if (!is_dir($basePath)) {
            return [];
        }

        $languages = [];
        foreach (scandir($basePath) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (is_dir($basePath . DIRECTORY_SEPARATOR . $entry)) {
                $languages[] = $entry;
            }
        }

        sort($languages);

        return $languages;
    }

    private function setTranslation(
        string $locale,
        string $address,
        string $value,
        bool $mustExist,
        ?string $dictionary
    ): string {
        $this->assertLanguageExists($locale);
        $entry = $this->resolveEntry($locale, $address, $dictionary, $mustExist);
        $dictionaryData = $this->loadDictionary($entry['file']);
        $exists = false;
        $this->arrayGet($dictionaryData, $entry['keys'], $exists);

        if ($mustExist && !$exists) {
            throw new InvalidArgumentException("Translation not found: {$address}");
        }

        if (!$mustExist && $exists) {
            throw new InvalidArgumentException("Translation already exists: {$address}");
        }

        $this->arraySet($dictionaryData, $entry['keys'], $value);
        $this->saveDictionary($entry['file'], $dictionaryData);

        return $entry['file'];
    }

    /**
     * @return array{file: string, keys: string[]}
     */
    private function resolveEntry(
        string $locale,
        string $address,
        ?string $dictionary,
        bool $existingFileOnly
    ): array {
        $locale = $this->normalizeLocale($locale);
        $addressSegments = $this->normalizePathSegments($address);

        if ($dictionary !== null && trim($dictionary) !== '') {
            return [
                'file' => $this->dictionaryFilePath($locale, $this->normalizePathSegments($dictionary)),
                'keys' => $addressSegments,
            ];
        }

        for ($pathLength = count($addressSegments) - 1; $pathLength >= 1; $pathLength--) {
            $file = $this->dictionaryFilePath($locale, array_slice($addressSegments, 0, $pathLength));

            if (is_file($file)) {
                return [
                    'file' => $file,
                    'keys' => array_slice($addressSegments, $pathLength),
                ];
            }
        }

        if ($existingFileOnly) {
            throw new InvalidArgumentException("Dictionary file not found for address: {$address}");
        }

        if (count($addressSegments) < 2) {
            throw new InvalidArgumentException(
                'Translation address must include a dictionary path and key, or use --dictionary.'
            );
        }

        return [
            'file' => $this->dictionaryFilePath($locale, [$addressSegments[0]]),
            'keys' => array_slice($addressSegments, 1),
        ];
    }

    /**
     * @param string[] $dictionarySegments
     */
    private function dictionaryFilePath(string $locale, array $dictionarySegments): string
    {
        return $this->languagePath($locale)
            . DIRECTORY_SEPARATOR
            . implode(DIRECTORY_SEPARATOR, $dictionarySegments)
            . '.php';
    }

    private function languagePath(string $locale): string
    {
        return $this->absoluteBasePath() . DIRECTORY_SEPARATOR . $this->normalizeLocale($locale);
    }

    private function absoluteBasePath(): string
    {
        $basePath = trim($this->basePath);

        if ($basePath === '') {
            throw new InvalidArgumentException('Translation base path must not be empty.');
        }

        if ($this->isAbsolutePath($basePath)) {
            return rtrim($basePath, '/\\');
        }

        return rtrim($this->projectRoot ?? getcwd(), '/\\') . DIRECTORY_SEPARATOR . trim($basePath, '/\\');
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1;
    }

    private function assertLanguageExists(string $locale): void
    {
        if (!is_dir($this->languagePath($locale))) {
            throw new InvalidArgumentException("Language not found: {$locale}");
        }
    }

    private function assertPathInsideBase(string $path): void
    {
        $basePath = realpath($this->absoluteBasePath());
        $targetPath = realpath($path);

        if ($basePath === false || $targetPath === false) {
            throw new InvalidArgumentException('Unable to resolve translation paths.');
        }

        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($targetPath . DIRECTORY_SEPARATOR, $basePath)) {
            throw new InvalidArgumentException('Resolved path is outside the translation base path.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDictionary(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        $data = (static function (string $file): mixed {
            return require $file;
        })($file);

        if (!is_array($data)) {
            throw new InvalidArgumentException("Dictionary must return an array: {$file}");
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveDictionary(string $file, array $data): void
    {
        $directory = dirname($file);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($file, "<?php\n\nreturn " . $this->exportArray($data) . ";\n");
    }

    /**
     * @param array<mixed> $data
     */
    private function exportArray(array $data, int $indent = 0): string
    {
        if ($data === []) {
            return '[]';
        }

        $nextIndent = $indent + 1;
        $padding = str_repeat('    ', $indent);
        $nextPadding = str_repeat('    ', $nextIndent);
        $lines = ['['];

        foreach ($data as $key => $value) {
            $exportedKey = is_int($key) ? $key : var_export((string) $key, true);
            $exportedValue = is_array($value)
                ? $this->exportArray($value, $nextIndent)
                : var_export($value, true);
            $lines[] = "{$nextPadding}{$exportedKey} => {$exportedValue},";
        }

        $lines[] = "{$padding}]";

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $data
     * @param string[] $keys
     */
    private function arrayGet(array $data, array $keys, bool &$found): mixed
    {
        $found = false;
        $value = $data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }

            $value = $value[$key];
        }

        $found = true;

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     * @param string[] $keys
     */
    private function arraySet(array &$data, array $keys, string $value): void
    {
        $cursor = &$data;
        $lastKey = array_pop($keys);

        foreach ($keys as $key) {
            if (!isset($cursor[$key]) || !is_array($cursor[$key])) {
                $cursor[$key] = [];
            }

            $cursor = &$cursor[$key];
        }

        $cursor[(string) $lastKey] = $value;
    }

    /**
     * @param array<string, mixed> $data
     * @param string[] $keys
     */
    private function arrayUnset(array &$data, array $keys): bool
    {
        $cursor = &$data;
        $lastKey = array_pop($keys);

        foreach ($keys as $key) {
            if (!isset($cursor[$key]) || !is_array($cursor[$key])) {
                return false;
            }

            $cursor = &$cursor[$key];
        }

        if (!array_key_exists((string) $lastKey, $cursor)) {
            return false;
        }

        unset($cursor[(string) $lastKey]);

        return true;
    }

    private function removeDirectory(string $path): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }

    /**
     * @return string[]
     */
    private function normalizePathSegments(string $path): array
    {
        $path = trim($path);

        if ($path === '' || str_contains($path, '..')) {
            throw new InvalidArgumentException('Translation path must not be empty or contain parent directory segments.');
        }

        $segments = preg_split('/[.\/\\\\]+/', trim($path, '.\/\\')) ?: [];
        $segments = array_values(array_filter($segments, static fn (string $segment): bool => $segment !== ''));

        if ($segments === []) {
            throw new InvalidArgumentException('Translation path must contain at least one segment.');
        }

        foreach ($segments as $segment) {
            if (!preg_match('/^[A-Za-z0-9_-]+$/', $segment)) {
                throw new InvalidArgumentException('Translation path contains an invalid segment.');
            }
        }

        return $segments;
    }

    private function normalizeLocale(string $locale): string
    {
        $locale = trim($locale);

        if ($locale === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $locale)) {
            throw new InvalidArgumentException('Locale must contain only letters, numbers, dashes, and underscores.');
        }

        return $locale;
    }
}
