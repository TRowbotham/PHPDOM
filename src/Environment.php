<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\URL\BasicURLParser;
use Rowbot\URL\String\Utf8String;
use Rowbot\URL\URLRecord;

use function assert;

class Environment
{
    private static ?URLRecord $defaultUrl = null;

    private URLRecord $url;

    private string $contentType;

    private bool $scriptingEnabled;

    public function __construct(?URLRecord $url = null, string $contentType = null)
    {
        if ($url === null) {
            $url = $this->getDefaultUrl();
        }

        $this->url = clone $url;
        $this->scriptingEnabled = false;
        $this->contentType = $contentType ?? 'application/xml';
    }

    public function getUrl(): URLRecord
    {
        return $this->url;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    public function setScriptingEnabled(bool $enable): void
    {
        $this->scriptingEnabled = $enable;
    }

    public function isScriptingEnabled(): bool
    {
        return $this->scriptingEnabled;
    }

    private function getDefaultUrl(): URLRecord
    {
        if (self::$defaultUrl === null) {
            $parser = new BasicURLParser();
            $url = $parser->parse(new Utf8String('about:blank'));
            assert($url !== false);
            self::$defaultUrl = $url;
        }

        return self::$defaultUrl;
    }

    public function __clone()
    {
        $this->url = clone $this->url;
    }
}
