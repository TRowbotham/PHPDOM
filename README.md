# PHPJS
PHPJS is an attempt to implement the Document Object Model (DOM) in PHP that was more inline with current standards.
While PHP does already have its own impelmentation of the DOM, it is somewhat outdated and is more geared towards
XML/XHTML/HTML4.  This is very much a work in progress and as a result things may be broken.

Here is a small sample of how to use PHPJS:
```php
require_once "phpjs.class.php";

/**
 * This creates a skeleton html page, which includes the DOCTYPE,
 * html, body, head, and title tags.
 */
$doc = new HTMLDocument();

// Set the page title
$doc->title = "My HTML Document!";

// Create an HTML anchor tag
$a = $doc->createElement("a");
$a->href = "http://www.example.com/";

// Insert it into the document
$doc->body->appendChild($a);

// Convert the DOM tree into a HTML string
echo $doc->toHTML();
```
