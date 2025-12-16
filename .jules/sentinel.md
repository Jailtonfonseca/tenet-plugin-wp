## 2024-05-23 - Hardening WordPress Plugin Inputs and Access
**Vulnerability:** Settings were registered without sanitization callbacks, and PHP files lacked direct access prevention (`ABSPATH` check).
**Learning:** WordPress `register_setting` defaults to no sanitization if a callback isn't provided (prior to 4.7) or if not explicitly set. Raw data in `wp_options` can lead to issues if consumed unsafely. Direct access to class files can reveal path information or trigger PHP errors.
**Prevention:** Always use the array syntax for `register_setting` with a `sanitize_callback`. Always include `defined('ABSPATH') || exit;` in every PHP file.
