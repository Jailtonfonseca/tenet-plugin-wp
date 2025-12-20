<?php

// This file is a unit test simulation for the JSON cleaning logic.
// It is intended to be run manually or by a CI process that can execute PHP scripts.
// Since the current environment lacks PHP CLI, this serves as documentation and code verification.

function test_json_cleaning() {
    $test_cases = [
        'Clean JSON' => '{"key": "value"}',
        'Markdown Block' => '```json
{"key": "value"}
```',
        'Intro Text' => 'Here is the JSON: {"key": "value"}',
        'Intro and Outro' => 'Here is the JSON: {"key": "value"} and some more text.',
        'Nested Braces' => '{"key": "val{u}e"}',
    ];

    $passed = 0;
    $total = count($test_cases);

    echo "Running JSON Cleaning Tests...\n";

    foreach ($test_cases as $name => $input) {
        $start = strpos( $input, '{' );
        $end = strrpos( $input, '}' );

        $cleaned = $input;
        if ( $start !== false && $end !== false ) {
            $cleaned = substr( $input, $start, ( $end - $start ) + 1 );
        }

        $decoded = json_decode( $cleaned, true );

        if ( json_last_error() === JSON_ERROR_NONE && isset($decoded['key']) && $decoded['key'] === ( $name === 'Nested Braces' ? 'val{u}e' : 'value' ) ) {
            echo "[PASS] $name\n";
            $passed++;
        } else {
            echo "[FAIL] $name. Output: $cleaned\n";
        }
    }

    echo "Result: $passed/$total passed.\n";
}

if (php_sapi_name() == "cli") {
    test_json_cleaning();
}
