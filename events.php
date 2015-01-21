<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Document</title>
</head>
<body>
<?php
require_once 'Document.class.php';

$document = new Document();
$a = $document->createElement('a');

$a->classList->add('pizza');
echo $a->className . '<br>';
$a->className = 'soda duck';
$a->className = '';
$a->className = 'soda';
$a->className .= ' duck';
echo $a->className . '<br>';
echo $a->classList->__toString();
?>
</body>
</html>