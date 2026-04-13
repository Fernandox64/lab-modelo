SET NAMES utf8mb4;

INSERT INTO laboratory_page_items (
    page_slug,
    slug,
    title,
    summary,
    category,
    content_html,
    image_url,
    external_url,
    is_active,
    sort_order,
    published_at
)
SELECT
    'blog' AS page_slug,
    n.slug,
    n.title,
    n.summary,
    n.category,
    CONCAT('<p>', REPLACE(n.content, '\n', '</p><p>'), '</p>') AS content_html,
    n.image AS image_url,
    '' AS external_url,
    1 AS is_active,
    0 AS sort_order,
    n.published_at
FROM news_items n
WHERE n.slug LIKE '%-2026'
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    summary = VALUES(summary),
    category = VALUES(category),
    content_html = VALUES(content_html),
    image_url = VALUES(image_url),
    external_url = VALUES(external_url),
    is_active = VALUES(is_active),
    sort_order = VALUES(sort_order),
    published_at = VALUES(published_at);
