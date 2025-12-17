# Sentinel's Journal

## 2024-05-14 - Direct File Access Protection
**Vulnerability:** PHP files were missing `defined('ABSPATH') || exit;` check.
**Learning:** In WordPress plugins, any PHP file can be accessed directly by a URL, potentially exposing errors or executing code out of context.
**Prevention:** Always add the check at the top of every PHP file.
