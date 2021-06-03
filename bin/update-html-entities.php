<?php

declare(strict_types=1);

use const DIRECTORY_SEPARATOR as DS;

$entities = file_get_contents('https://html.spec.whatwg.org/entities.json');

if ($entities === false) {
    throw new RuntimeException('Failed to download entities.json.');
}

$json = json_decode($entities, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    throw new RuntimeException('Failed to decode entities.json. ' . json_last_error_msg());
}

$table = [];

foreach ($json as $name => $data) {
    $workingTable = &$table;

    for ($i = 1, $len = strlen($name); $i < $len; ++$i) {
        if (!isset($workingTable[$name[$i]])) {
            $workingTable[$name[$i]] = null;
        }

        $workingTable = &$workingTable[$name[$i]];
    }

    if (!isset($workingTable['chars'])) {
        $workingTable = [];
    }

    $workingTable['chars'] = $data['characters'];
}

unset($workingTable);

$serialized = json_encode($table);

if (json_last_error() !== JSON_ERROR_NONE) {
    throw new RuntimeException('Failed to encode the array. ' . json_last_error_msg());
}

$out = file_put_contents(__DIR__ . DS . '..' . DS . 'src' . DS . 'Parser' . DS . 'HTML' . DS . 'named-character-references.json', $serialized);

if ($out === false) {
    throw new RuntimeException('Failed to write named-character-references.json.');
}
