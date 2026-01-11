<?php
/**
 * Format a number as Philippine Peso
 * 
 * @param float $amount The amount to format
 * @return string Formatted amount with ₱ symbol
 */
function format_peso($amount) {
    return '₱' . number_format($amount, 2, '.', ',');
}

/**
 * Get status badge HTML based on status
 */
function getStatusBadge($status) {
    $statusClasses = [
        'pending' => 'badge-warning',
        'in-progress' => 'badge-info',
        'completed' => 'badge-success',
        'cancelled' => 'badge-error'
    ];
    
    $class = $statusClasses[strtolower($status)] ?? 'badge-ghost';
    return '<span class="badge ' . $class . ' text-xs">' . ucfirst($status) . '</span>';
}
?>
