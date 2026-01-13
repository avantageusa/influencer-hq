<?php
/**
 * Team Selection Submissions Admin Page
 * 
 * This file creates an admin page in WordPress dashboard to view
 * all team selection form submissions from the wp_team_selections table.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu for Team Selection Submissions
 */
function team_selections_admin_menu() {
    add_menu_page(
        'Team Selection Submissions',     // Page title
        'Team Selections',               // Menu title
        'manage_options',                // Capability
        'team-selections',               // Menu slug
        'team_selections_admin_page',    // Function
        'dashicons-groups',              // Icon
        30                               // Position
    );
}
add_action('admin_menu', 'team_selections_admin_menu');

/**
 * Display the admin page content
 */
function team_selections_admin_page() {
    // Handle CSV export FIRST, before any output
    if (isset($_POST['export_csv'])) {
        team_selections_export_csv();
        return; // This should never be reached due to exit in export function
    }
    
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'team_selections';
    
    // Handle actions (delete, etc.)
    if (isset($_GET['action']) && isset($_GET['id']) && $_GET['action'] === 'delete') {
        $id = intval($_GET['id']);
        if ($id > 0) {
            $wpdb->delete($table_name, array('id' => $id), array('%d'));
            echo '<div class="notice notice-success is-dismissible"><p>Submission deleted successfully.</p></div>';
        }
    }
    
    // Pagination
    $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    // Search functionality
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $where_clause = '';
    if (!empty($search)) {
        $where_clause = $wpdb->prepare(
            " WHERE first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR sports_team LIKE %s OR music_team LIKE %s OR movies_team LIKE %s OR international_team LIKE %s OR team_reason LIKE %s",
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%'
        );
    }
    
    // Get total count for pagination
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name" . $where_clause);
    $total_pages = ceil($total_items / $per_page);
    
    // Get submissions with pagination and search
    $submissions = $wpdb->get_results(
        "SELECT * FROM $table_name" . $where_clause . " ORDER BY submission_date DESC LIMIT $per_page OFFSET $offset"
    );
    
    ?>
    <div class="wrap">
        <h1>Team Selection Submissions</h1>
        
        <!-- Stats Box -->
        <div class="postbox" style="margin-bottom: 20px;">
            <div class="inside">
                <h3>Statistics</h3>
                <p><strong>Total Submissions:</strong> <?php echo number_format($total_items); ?></p>
                <?php
                // Get team stats for all categories
                $sports_stats = $wpdb->get_results("SELECT sports_team, COUNT(*) as count FROM $table_name WHERE sports_team IS NOT NULL AND sports_team != '' GROUP BY sports_team ORDER BY count DESC LIMIT 3");
                $music_stats = $wpdb->get_results("SELECT music_team, COUNT(*) as count FROM $table_name WHERE music_team IS NOT NULL AND music_team != '' GROUP BY music_team ORDER BY count DESC LIMIT 3");
                $movies_stats = $wpdb->get_results("SELECT movies_team, COUNT(*) as count FROM $table_name WHERE movies_team IS NOT NULL AND movies_team != '' GROUP BY movies_team ORDER BY count DESC LIMIT 3");
                $international_stats = $wpdb->get_results("SELECT international_team, COUNT(*) as count FROM $table_name WHERE international_team IS NOT NULL AND international_team != '' GROUP BY international_team ORDER BY count DESC LIMIT 3");
                
                if ($sports_stats) {
                    echo '<p><strong>Top Sports Teams:</strong></p>';
                    echo '<ul>';
                    foreach ($sports_stats as $stat) {
                        echo '<li>' . esc_html(ucwords(str_replace('-', ' ', $stat->sports_team))) . ': ' . $stat->count . '</li>';
                    }
                    echo '</ul>';
                }
                
                if ($music_stats) {
                    echo '<p><strong>Top Music Teams:</strong></p>';
                    echo '<ul>';
                    foreach ($music_stats as $stat) {
                        echo '<li>' . esc_html(ucwords(str_replace('-', ' ', $stat->music_team))) . ': ' . $stat->count . '</li>';
                    }
                    echo '</ul>';
                }
                
                if ($movies_stats) {
                    echo '<p><strong>Top Movie Teams:</strong></p>';
                    echo '<ul>';
                    foreach ($movies_stats as $stat) {
                        echo '<li>' . esc_html(ucwords(str_replace('-', ' ', $stat->movies_team))) . ': ' . $stat->count . '</li>';
                    }
                    echo '</ul>';
                }
                
                if ($international_stats) {
                    echo '<p><strong>Top International Teams:</strong></p>';
                    echo '<ul>';
                    foreach ($international_stats as $stat) {
                        echo '<li>' . esc_html(ucwords(str_replace('-', ' ', $stat->international_team))) . ': ' . $stat->count . '</li>';
                    }
                    echo '</ul>';
                }
                ?>
            </div>
        </div>
        
        <!-- Search and Export Form -->
        <form method="get" action="" style="margin-bottom: 20px;">
            <input type="hidden" name="page" value="team-selections">
            <p class="search-box">
                <label class="screen-reader-text" for="team-search-input">Search submissions:</label>
                <input type="search" id="team-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search by name, email, teams, or reason...">
                <input type="submit" id="search-submit" class="button" value="Search">
                <?php if (!empty($search)): ?>
                    <a href="<?php echo admin_url('admin.php?page=team-selections'); ?>" class="button">Clear</a>
                <?php endif; ?>
            </p>
        </form>
        
        <!-- Export Button -->
        <form method="post" action="" style="margin-bottom: 20px;">
            <input type="submit" name="export_csv" class="button button-secondary" value="Export to CSV">
        </form>
        
        <!-- Results Table -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <span class="displaying-num"><?php echo number_format($total_items); ?> items</span>
            </div>
            <?php if ($total_pages > 1): ?>
            <div class="tablenav-pages">
                <span class="pagination-links">
                    <?php
                    $page_links = paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $page,
                        'type' => 'array'
                    ));
                    
                    if ($page_links) {
                        echo implode('', $page_links);
                    }
                    ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column">ID</th>
                    <th scope="col" class="manage-column">Name</th>
                    <th scope="col" class="manage-column">Email</th>
                    <th scope="col" class="manage-column">Sports Team</th>
                    <th scope="col" class="manage-column">Music Team</th>
                    <th scope="col" class="manage-column">Movie Team</th>
                    <th scope="col" class="manage-column">International Team</th>
                    <th scope="col" class="manage-column">Main Reason</th>
                    <th scope="col" class="manage-column">Date</th>
                    <th scope="col" class="manage-column">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($submissions): ?>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?php echo esc_html($submission->id); ?></td>
                            <td>
                                <strong><?php echo esc_html($submission->first_name . ' ' . $submission->last_name); ?></strong>
                            </td>
                            <td>
                                <a href="mailto:<?php echo esc_attr($submission->email); ?>">
                                    <?php echo esc_html($submission->email); ?>
                                </a>
                            </td>
                            <td>
                                <?php if (!empty($submission->sports_team)): ?>
                                    <span title="<?php echo esc_attr($submission->sports_team); ?>">
                                        <?php echo esc_html(ucwords(str_replace('-', ' ', $submission->sports_team))); ?>
                                    </span>
                                <?php else: ?>
                                    <em>Not selected</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($submission->music_team)): ?>
                                    <span title="<?php echo esc_attr($submission->music_team); ?>">
                                        <?php echo esc_html(ucwords(str_replace('-', ' ', $submission->music_team))); ?>
                                    </span>
                                <?php else: ?>
                                    <em>Not selected</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($submission->movies_team)): ?>
                                    <span title="<?php echo esc_attr($submission->movies_team); ?>">
                                        <?php echo esc_html(ucwords(str_replace('-', ' ', $submission->movies_team))); ?>
                                    </span>
                                <?php else: ?>
                                    <em>Not selected</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($submission->international_team)): ?>
                                    <span title="<?php echo esc_attr($submission->international_team); ?>">
                                        <?php echo esc_html(ucwords(str_replace('-', ' ', $submission->international_team))); ?>
                                    </span>
                                <?php else: ?>
                                    <em>Not selected</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($submission->team_reason)): ?>
                                    <span title="<?php echo esc_attr($submission->team_reason); ?>">
                                        <?php echo esc_html(wp_trim_words($submission->team_reason, 8)); ?>
                                    </span>
                                <?php else: ?>
                                    <em>No reason</em>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($submission->submission_date))); ?></td>
                            <td>
                                <a href="javascript:void(0);" 
                                   onclick="showSubmissionDetails(<?php echo $submission->id; ?>)"
                                   class="button button-small">View</a>
                                <a href="<?php echo wp_nonce_url(
                                    admin_url('admin.php?page=team-selections&action=delete&id=' . $submission->id),
                                    'delete_submission_' . $submission->id
                                ); ?>" 
                                class="button button-small button-link-delete"
                                onclick="return confirm('Are you sure you want to delete this submission?');">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 40px;">
                            <?php if (!empty($search)): ?>
                                No submissions found matching your search.
                            <?php else: ?>
                                No submissions found yet.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Bottom pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="pagination-links">
                    <?php echo implode('', $page_links); ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Submission Details Modal -->
    <div id="submission-modal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
        <div style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 5px;">
            <span onclick="closeSubmissionModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            <h2>Submission Details</h2>
            <div id="submission-details-content">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
    
    <script>
        function showSubmissionDetails(submissionId) {
            // Get submission data via AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById('submission-details-content').innerHTML = xhr.responseText;
                    document.getElementById('submission-modal').style.display = 'block';
                }
            };
            xhr.send('action=get_submission_details&submission_id=' + submissionId);
        }
        
        function closeSubmissionModal() {
            document.getElementById('submission-modal').style.display = 'none';
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            var modal = document.getElementById('submission-modal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    
    <style>
        .wp-list-table th,
        .wp-list-table td {
            vertical-align: middle;
        }
        .button-link-delete {
            color: #d63638 !important;
        }
        .button-link-delete:hover {
            color: #d63638 !important;
        }
        .postbox .inside {
            padding: 15px;
        }
        .postbox h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: 600;
        }
        .wp-list-table .column-actions {
            width: 120px;
        }
    </style>
    <?php
}

/**
 * AJAX handler for getting submission details
 */
function get_submission_details_ajax() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    
    $submission_id = intval($_POST['submission_id']);
    if ($submission_id <= 0) {
        wp_die('Invalid submission ID');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'team_selections';
    
    $submission = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $submission_id
    ));
    
    if (!$submission) {
        echo '<p>Submission not found.</p>';
        wp_die();
    }
    
    ?>
    <table style="width: 100%; border-collapse: collapse;">
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 8px; font-weight: bold; width: 30%;">Name:</td>
            <td style="padding: 8px;"><?php echo esc_html($submission->first_name . ' ' . $submission->last_name); ?></td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 8px; font-weight: bold;">Email:</td>
            <td style="padding: 8px;"><a href="mailto:<?php echo esc_attr($submission->email); ?>"><?php echo esc_html($submission->email); ?></a></td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 8px; font-weight: bold;">Sports Team:</td>
            <td style="padding: 8px;"><?php echo esc_html(ucwords(str_replace('-', ' ', $submission->sports_team ?? 'Not selected'))); ?></td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 8px; font-weight: bold;">Music Team:</td>
            <td style="padding: 8px;"><?php echo esc_html(ucwords(str_replace('-', ' ', $submission->music_team ?? 'Not selected'))); ?></td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 8px; font-weight: bold;">Movie Team:</td>
            <td style="padding: 8px;"><?php echo esc_html(ucwords(str_replace('-', ' ', $submission->movies_team ?? 'Not selected'))); ?></td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 8px; font-weight: bold;">International Team:</td>
            <td style="padding: 8px;"><?php echo esc_html(ucwords(str_replace('-', ' ', $submission->international_team ?? 'Not selected'))); ?></td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 8px; font-weight: bold; vertical-align: top;">Main Story:</td>
            <td style="padding: 8px;"><?php echo esc_html($submission->team_reason ?? 'No story provided'); ?></td>
        </tr>
        <?php if (!empty($submission->celebrity_team_reason)): ?>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 8px; font-weight: bold; vertical-align: top;">Celebrity Team Reason:</td>
            <td style="padding: 8px;"><?php echo esc_html($submission->celebrity_team_reason); ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($submission->international_team_reason)): ?>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 8px; font-weight: bold; vertical-align: top;">International Team Reason:</td>
            <td style="padding: 8px;"><?php echo esc_html($submission->international_team_reason); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td style="padding: 8px; font-weight: bold;">Submission Date:</td>
            <td style="padding: 8px;"><?php echo esc_html(date('F j, Y g:i A', strtotime($submission->submission_date))); ?></td>
        </tr>
    </table>
    <?php
    
    wp_die();
}
add_action('wp_ajax_get_submission_details', 'get_submission_details_ajax');

/**
 * Export submissions to CSV
 */
function team_selections_export_csv() {
    // Check if we should process the export
    if (!isset($_POST['export_csv'])) {
        return;
    }
    
    // Verify user permissions
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'team_selections';
    
    // Get all submissions
    $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submission_date DESC");
    
    // Clean any output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="team-selections-' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for proper Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add CSV headers
    fputcsv($output, array(
        'ID',
        'First Name',
        'Last Name',
        'Email',
        'Sports Team',
        'Music Team',
        'Movie Team',
        'International Team',
        'Main Story',
        'Celebrity Team Reason',
        'International Team Reason',
        'Submission Date'
    ));
    
    // Add data rows
    foreach ($submissions as $submission) {
        fputcsv($output, array(
            $submission->id,
            wp_strip_all_tags(html_entity_decode($submission->first_name, ENT_QUOTES, 'UTF-8')),
            wp_strip_all_tags(html_entity_decode($submission->last_name, ENT_QUOTES, 'UTF-8')),
            wp_strip_all_tags(html_entity_decode($submission->email, ENT_QUOTES, 'UTF-8')),
            wp_strip_all_tags(html_entity_decode(ucwords(str_replace('-', ' ', $submission->sports_team ?? '')), ENT_QUOTES, 'UTF-8')),
            wp_strip_all_tags(html_entity_decode(ucwords(str_replace('-', ' ', $submission->music_team ?? '')), ENT_QUOTES, 'UTF-8')),
            wp_strip_all_tags(html_entity_decode(ucwords(str_replace('-', ' ', $submission->movies_team ?? '')), ENT_QUOTES, 'UTF-8')),
            wp_strip_all_tags(html_entity_decode(ucwords(str_replace('-', ' ', $submission->international_team ?? '')), ENT_QUOTES, 'UTF-8')),
            wp_strip_all_tags(html_entity_decode($submission->team_reason ?? '', ENT_QUOTES, 'UTF-8')),
            wp_strip_all_tags(html_entity_decode($submission->celebrity_team_reason ?? '', ENT_QUOTES, 'UTF-8')),
            wp_strip_all_tags(html_entity_decode($submission->international_team_reason ?? '', ENT_QUOTES, 'UTF-8')),
            $submission->submission_date
        ));
    }
    
    fclose($output);
    exit; // This is crucial - prevents WordPress from rendering anything else
}

/**
 * Add custom CSS for better styling
 */
function team_selections_admin_styles() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_team-selections') {
        echo '<style>
            .wrap h1 {
                margin-bottom: 20px;
            }
            .search-box {
                float: right;
                margin-bottom: 0;
            }
            .search-box input[type="search"] {
                width: 280px;
            }
            .tablenav {
                margin: 10px 0;
            }
            .pagination-links a,
            .pagination-links span {
                padding: 3px 8px;
                margin: 0 2px;
                border: 1px solid #ddd;
                background: #f7f7f7;
                text-decoration: none;
            }
            .pagination-links .current {
                background: #0073aa;
                color: white;
                border-color: #0073aa;
            }
            .wp-list-table .column-actions {
                width: 80px;
            }
        </style>';
    }
}
add_action('admin_head', 'team_selections_admin_styles');

/**
 * Add admin notice if table doesn't exist
 */
function team_selections_check_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'team_selections';
    $screen = get_current_screen();
    
    if ($screen && $screen->id === 'toplevel_page_team-selections') {
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            echo '<div class="notice notice-warning">
                <p><strong>Notice:</strong> The team selections table does not exist yet. 
                It will be created automatically when the first form submission is made.</p>
            </div>';
        }
    }
}
add_action('admin_notices', 'team_selections_check_table');
