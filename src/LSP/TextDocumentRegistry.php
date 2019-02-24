<?php

declare(strict_types=1);

namespace LanguageServer\LSP;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class TextDocumentRegistry
{
    /** @var TextDocument[] */
    private $documents = [];

    /**
     * Get a document by filename and version.
     *
     * @param string $fileName
     * @param int    $version
     *
     * @return TextDocument
     */
    public function get(string $fileName, int $version): TextDocument
    {
        return $this->documents[sprintf('%s:%d', $fileName, $version)];
    }

    public function getLatest(string $fileName): TextDocument
    {
        $version = $this->getLatestVersionForFile($fileName);

        return $this->get($fileName, $version);
    }

    private function getLatestVersionForFile(string $path): int
    {
        $documents = array_filter(
            $this->documents,
            function (TextDocument $document) use ($path) {
                return $document->getPath() === $path;
            }
        );

        $latestVersion = array_reduce(
            $documents,
            function (int $carry, TextDocument $document) {
                $version = $document->getVersion();

                if ($version >= $carry) {
                    return $version;
                }
            },
            0
        );

        return $latestVersion;
    }

    /**
     * Add a document to the registry.
     *
     * @param TextDocument $document
     */
    public function add(TextDocument $document): void
    {
        $key = sprintf('%s:%d', $document->getPath(), $document->getVersion());

        $this->documents[$key] = $document;
    }
}
