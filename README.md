# PHPDOM

[![GitHub](https://img.shields.io/github/license/TRowbotham/PHPDOM.svg?style=flat-square)](https://github.com/TRowbotham/PHPDOM/blob/master/LICENSE)
[![GitHub Workflow Status (branch)](https://img.shields.io/github/workflow/status/TRowbotham/PHPDOM/Test/dev?style=flat-square)](https://github.com/TRowbotham/PHPDOM/actions)
[![Codecov branch](https://img.shields.io/codecov/c/github/TRowbotham/PHPDOM/master?logo=Codecov&style=flat-square&token=mT7l2Nu8Zf)](https://codecov.io/gh/TRowbotham/PHPDOM)

PHPDOM is an attempt to implement the Document Object Model (DOM) in PHP that was more inline with current standards.
While PHP does already have its own implementation of the DOM, it is somewhat outdated and is more geared towards
XML/XHTML/HTML4. This is very much a work in progress and as a result things may be broken.

## Usage

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

### Parsing an HTML Document

```php
<?php

require_once "vendor/autoload.php";

use Rowbot\DOM\DOMParser;

$parser = new DOMParser();

// Currently "text/html" is the only supported option.
$document = $parser->parseFromString(file_get_contents('/path/to/file.html'), 'text/html');

// Do some things with the document
$document->getElementById('foo');
```

## Caveats

* Only UTF-8 encoded documents are supported.
* All string input is expected to be in UTF-8.
* All strings returned to the user, such as those returned from `Text.data`, are in UTF-8, rather than UCS-2.
* All string offsets and lengths such as those in `Text.replaceData()` or `Text.length` are expressed in UTF-8 code points, rather than UCS-2 code units.

## Turning your tree back into a string

* For the entire Document:
  * You may call the `toString()` method on the Document, e.g. `$document->toString()`, or you may cast the Document to a string, e.g. `(string) $document`,
* For Elements:
  * Depending on your needs, you may use the `innerHTML` property to get all of the Element's descendants, e.g. `$element->innerHTML`, or you may use the `outerHTML` property to get the Element itself and all its descendants, e.g. `$element->outerHTML`.
* For Text nodes:
  * You may use the `data` property, e.g. `$textNode->data` to get the text data from the node.
* For the entire Range:
  * You may call the `toString()` method, e.g. `$range->toString()`, or you may cast the Range to a string, e.g. `(string) $range`.
