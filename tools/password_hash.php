<?php
// Usage:
//   php tools/password_hash.php "YourNewPassword"
if ($argc < 2) {
    fwrite(STDERR, "Usage: php tools/password_hash.php "YourNewPassword"\n");
    exit(1);
}
$pw = $argv[1];
echo password_hash($pw, PASSWORD_BCRYPT) . PHP_EOL;
