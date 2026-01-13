<?php
/**
 * Template Name: Update Team Table
 * 
 * This template simply updates the existing wp_team_selections table structure
 * Load this page once to update the table, then you can delete this file.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Update the team_selections table to ensure all fields exist
function update_team_selections_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'team_selections';
    
    // Get current table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    $existing_columns = array();
    foreach ($columns as $column) {
        $existing_columns[] = $column->Field;
    }
    
    // Define all required columns
    $required_columns = array(
        'id' => 'mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY',
        'first_name' => 'varchar(100) NOT NULL',
        'last_name' => 'varchar(100) NOT NULL', 
        'email' => 'varchar(100) NOT NULL',
        'sports_team' => 'varchar(100) NOT NULL',
        'music_team' => 'varchar(100) NOT NULL',
        'movies_team' => 'varchar(100) NOT NULL',
        'international_team' => 'varchar(100) NOT NULL',
        'team_reason' => 'text',
        'celebrity_team_reason' => 'text',
        'international_team_reason' => 'text',
        'submission_date' => 'datetime DEFAULT CURRENT_TIMESTAMP'
    );
    
    // Add missing columns
    $added_columns = array();
    foreach ($required_columns as $column_name => $column_definition) {
        if (!in_array($column_name, $existing_columns)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN $column_name $column_definition");
            $added_columns[] = $column_name;
        }
    }
    
    // Add indexes if they don't exist
    $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name");
    $existing_indexes = array();
    foreach ($indexes as $index) {
        $existing_indexes[] = $index->Key_name;
    }
    
    $added_indexes = array();
    if (!in_array('email', $existing_indexes)) {
        $wpdb->query("ALTER TABLE $table_name ADD INDEX email (email)");
        $added_indexes[] = 'email';
    }
    if (!in_array('submission_date', $existing_indexes)) {
        $wpdb->query("ALTER TABLE $table_name ADD INDEX submission_date (submission_date)");
        $added_indexes[] = 'submission_date';
    }
    
    return array(
        'columns' => $added_columns,
        'indexes' => $added_indexes
    );
}

// Update table structure
$updates = update_team_selections_table();

?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-dark text-white">
                <div class="card-header">
                    <h1 class="text-center text-warning">Table Structure Updated</h1>
                </div>
                <div class="card-body">
                    
                    <div class="alert alert-success">
                        <h4>✅ Table Update Complete!</h4>
                        <p>The <code>wp_team_selections</code> table has been updated.</p>
                    </div>
                    
                    <?php if (!empty($updates['columns'])): ?>
                        <div class="alert alert-info">
                            <h5>📋 Added Columns:</h5>
                            <ul class="mb-0">
                                <?php foreach ($updates['columns'] as $column): ?>
                                    <li><code><?php echo esc_html($column); ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary">
                            <p>🔄 All columns already exist - no changes needed.</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($updates['indexes'])): ?>
                        <div class="alert alert-info">
                            <h5>🔍 Added Indexes:</h5>
                            <ul class="mb-0">
                                <?php foreach ($updates['indexes'] as $index): ?>
                                    <li><code><?php echo esc_html($index); ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary">
                            <p>🔍 All indexes already exist - no changes needed.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <h3 class="text-warning">Final Table Structure</h3>
                        <?php
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'team_selections';
                        $columns = $wpdb->get_results("DESCRIBE $table_name");
                        
                        echo "<div class='table-responsive'>";
                        echo "<table class='table table-dark table-striped'>";
                        echo "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>";
                        echo "<tbody>";
                        
                        foreach ($columns as $column) {
                            echo "<tr>";
                            echo "<td><code>" . esc_html($column->Field) . "</code></td>";
                            echo "<td>" . esc_html($column->Type) . "</td>";
                            echo "<td>" . esc_html($column->Null) . "</td>";
                            echo "<td>" . esc_html($column->Key) . "</td>";
                            echo "<td>" . esc_html($column->Default) . "</td>";
                            echo "</tr>";
                        }
                        
                        echo "</tbody></table>";
                        echo "</div>";
                        ?>
                    </div>
                    
                    <div class="alert alert-success">
                        <h5>✅ Done!</h5>
                        <p>Your table is now ready for the team selection form. You can delete this template file now.</p>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table-dark {
    --bs-table-bg: #212529;
}
</style>

<?php get_footer(); ?>

