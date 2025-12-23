<?php
// Usage:
//   php tools/generate_secret.php
echo bin2hex(random_bytes(32)) . PHP_EOL;
