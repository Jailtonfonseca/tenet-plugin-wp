## 2025-05-20 - Beware of the ID-only Trap
**Learning:** Fetching only IDs (`fields => 'ids'`) to save memory often leads to N+1 performance issues when those IDs are immediately used to fetch properties (like titles) in a loop.
**Action:** When only specific columns are needed (like titles), use direct SQL (`$wpdb`) or cache the results, rather than partial object fetching followed by loop queries.
