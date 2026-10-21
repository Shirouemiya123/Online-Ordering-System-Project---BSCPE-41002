<?php
// Phase 4 helpers: cuisines, ratings. Include after config.php.
const CUISINES = ['Filipino', 'Fast food', 'Chicken', 'Burgers', 'Pizza', 'Asian', 'Coffee & Dessert', 'Healthy', 'Other'];

// Adds avg_rating and review_count columns to a query that selects from `restaurants r`.
const RATING_SQL = "(SELECT ROUND(AVG(restaurant_rating),1) FROM reviews WHERE restaurant_id=r.id) AS avg_rating, (SELECT COUNT(*) FROM reviews WHERE restaurant_id=r.id) AS review_count";

function rating_badge($avg, $cnt): string {
    if (!$cnt) return '<span class="rate-badge new">New</span>';
    return '<span class="rate-badge">★ ' . number_format((float)$avg, 1) . ' <small>(' . (int)$cnt . ')</small></span>';
}
function star_str(int $n): string { return str_repeat('★', $n) . str_repeat('☆', 5 - $n); }

// Clickable 1-5 star input (pure CSS, radio buttons).
function rating_input(string $name, bool $required = true): string {
    $h = '<div class="rate">';
    for ($i = 5; $i >= 1; $i--) {
        $h .= '<input type="radio" id="' . $name . $i . '" name="' . $name . '" value="' . $i . '"' . ($required ? ' required' : '') . '><label for="' . $name . $i . '" title="' . $i . ' star' . ($i > 1 ? 's' : '') . '">★</label>';
    }
    return $h . '</div>';
}
