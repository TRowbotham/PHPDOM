# PHPDOM

[![GitHub](https://img.shields.io/github/license/TRowbotham/PHPDOM.svg?style=flat-square)](https://github.com/TRowbotham/PHPDOM/blob/master/LICENSE)
[![Codecov](https://codecov.io/gh/TRowbotham/PHPDOM/branch/master/graph/badge.svg?token=mT7l2Nu8Zf)](https://codecov.io/gh/TRowbotham/PHPDOM)

PHPDOM is an attempt to implement the Document Object Model (DOM) in PHP that was more inline with current standards.
While PHP does already have its own implementation of the DOM, it is somewhat outdated and is more geared towards
XML/XHTML/HTML4. This is very much a work in progress and as a result things may be broken.

Here is a small sample of how to use PHPDOM:

```php
<?php
require_once "vendor/autoload.php";

use Rowbot\DOM\HTMLDocument;

/**
 * This creates a new empty HTML Document.
 */
$doc = new HTMLDocument();

/**
 * Want a skeleton framework for an HTML Document?
 */
$doc = $doc->implementation->createHTMLDocument();

// Set the page title
$doc->title = "My HTML Document!";

// Create an HTML anchor tag
$a = $doc->createElement("a");
$a->href = "http://www.example.com/";

// Insert it into the document
$doc->body->appendChild($a);

// Convert the DOM tree into a HTML string
echo $doc->toString();
```

```php
/**
 * Parse an HTML file.
 */
require_once "vendor/autoload.php";

use Rowbot\DOM\DOMParser;

$parser = new DOMParser();

// Currently "text/html" is the only supported option.
$document = $parser->parseFromString(file_get_contents('/path/to/file.html'), 'text/html');

// Do some things with the document
$document->getElementById('foo');
```
