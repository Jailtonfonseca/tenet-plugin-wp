## 2025-05-20 - Beware of the ID-only Trap
**Learning:** Fetching only IDs (`fields => 'ids'`) to save memory often leads to N+1 performance issues when those IDs are immediately used to fetch properties (like titles) in a loop.
**Action:** When only specific columns are needed (like titles), use direct SQL (`$wpdb`) or cache the results, rather than partial object fetching followed by loop queries.

## 2025-05-20 - The White Screen of Patience
**Learning:** Synchronous API calls (OpenAI + Pixabay) inside `admin-post.php` or page rendering can exceed PHP execution time limits (30-60s), leading to a white screen of death and data loss.
**Action:** Move long-running generation tasks to background processing (Action Scheduler or WP-Cron) and provide UI feedback via AJAX/polling.

## 2025-05-20 - The JSON Hallucination
**Learning:** LLMs are conversational and often wrap JSON output in Markdown blocks (```json ... ```) or add introductory text, causing `json_decode` to fail on the raw string.
**Action:** Implement a "cleaner" function that extracts the substring between the first `{` and last `}` before decoding, and consider a retry mechanism for malformed responses.
