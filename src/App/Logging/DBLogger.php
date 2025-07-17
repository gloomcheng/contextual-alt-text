<?php

namespace ContextualAltText\App\Logging;

class DBLogger implements LoggerInterface
{
    private function __construct()
    {
    }

    public static function make(): DBLogger
    {
        return new self();
    }

    /**
     * Write a new record for the single error (legacy method)
     *
     * @param  int    $imageId
     * @param  string $errorMessage
     * @return void
     */
    public function writeImageLog(int $imageId, string $errorMessage): void
    {
        $this->writeLog('error', $errorMessage, [], $imageId);
    }

    /**
     * Write a new log record with level, message, context and optional attachment ID
     *
     * @param  string $level
     * @param  string $message
     * @param  array  $context
     * @param  int|null $attachmentId
     * @return void
     */
    public function writeLog(string $level, string $message, array $context = [], ?int $attachmentId = null): void
    {
        global $wpdb;

        if (!$this->logTableExists()) {
            $this->createLogTable();
        }

        $sanitizedMessage = sanitize_text_field($message);
        $contextJson = !empty($context) ? wp_json_encode($context) : null;

        $currentDateTime = current_time('mysql');
        $wpdb->insert(
            $wpdb->prefix . 'contextual_alt_text_logs',
            [
                'level' => $level,
                'message' => $sanitizedMessage,
                'context' => $contextJson,
                'attachment_id' => $attachmentId,
                'created_at' => $currentDateTime
            ],
            ['%s', '%s', '%s', '%d', '%s']
        );
    }

    /**
     * Get all error records from the logs table
     *
     * @return string
     */
    public function getImageLog(): string
    {
        global $wpdb;
        $output = "";

        if (!$this->logTableExists()) {
            return $output;
        }

        // Try new table first, then fallback to old table
        $query = "SELECT * FROM {$wpdb->prefix}contextual_alt_text_logs ORDER BY created_at DESC LIMIT 50";
        $logs = $wpdb->get_results($query, ARRAY_A);

        if (empty($logs)) {
            // Fallback to old table format
            $oldQuery = "SELECT * FROM {$wpdb->prefix}cat_logs ORDER BY time DESC LIMIT 50";
            $oldLogs = $wpdb->get_results($oldQuery, ARRAY_A);
            
            if (!empty($oldLogs)) {
                foreach ($oldLogs as $log) {
                    $output .= sprintf(
                        "[%s] - Image ID: %d - Error: %s\n",
                        $log['time'],
                        $log['image_id'],
                        $log['error_message']
                    );
                }
            }
            return $output;
        }

        foreach ($logs as $log) {
            $context = '';
            if (!empty($log['context'])) {
                $contextData = json_decode($log['context'], true);
                if (is_array($contextData)) {
                    $context = ' - Context: ' . http_build_query($contextData, '', ', ');
                }
            }
            
            $attachmentInfo = '';
            if (!empty($log['attachment_id'])) {
                $attachmentInfo = ' - Attachment ID: ' . $log['attachment_id'];
            }

            $output .= sprintf(
                "[%s] %s: %s%s%s\n",
                $log['created_at'],
                strtoupper($log['level']),
                $log['message'],
                $attachmentInfo,
                $context
            );
        }

        return $output;
    }

    /**
     * Get recent log records with a limit
     *
     * @param  int $limit Number of recent logs to retrieve
     * @return array Array of log objects
     */
    public function getRecentLogs(int $limit = 10): array
    {
        global $wpdb;

        if (!$this->logTableExists()) {
            return [];
        }

        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}contextual_alt_text_logs 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        );
        
        return $wpdb->get_results($query);
    }

    /**
     * Check if Log table exists
     *
     * @return bool
     */
    private function logTableExists(): bool
    {
        global $wpdb;
        
        // Check new table first
        $newTableCheckQuery = $wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'contextual_alt_text_logs');
        if ($wpdb->get_var($newTableCheckQuery) == $wpdb->prefix . 'contextual_alt_text_logs') {
            return true;
        }
        
        // Fallback to old table
        $oldTableCheckQuery = $wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'cat_logs');
        return $wpdb->get_var($oldTableCheckQuery) == $wpdb->prefix . 'cat_logs';
    }

    /**
     * Create the Log table
     *
     * @return void
     */
    public function createLogTable(): void
    {
        global $wpdb;
        
        // Create new table format
        $newTableCheckQuery = $wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'contextual_alt_text_logs');

        if ($wpdb->get_var($newTableCheckQuery) != $wpdb->prefix . 'contextual_alt_text_logs') {
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE {$wpdb->prefix}contextual_alt_text_logs (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                level varchar(20) NOT NULL DEFAULT 'info',
                message text NOT NULL,
                context text,
                attachment_id mediumint(9),
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id),
                KEY level_idx (level),
                KEY attachment_id_idx (attachment_id),
                KEY created_at_idx (created_at)
            ) $charset_collate;";

            include_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        }
    }

    /**
     * Drop the Log table
     *
     * @return void
     */
    public function dropLogTable(): void
    {
        global $wpdb;
        $sql = "DROP TABLE IF EXISTS {$wpdb->prefix}contextual_alt_text_logs;";
        $wpdb->query($sql);
        
        // Also drop old table if exists
        $oldSql = "DROP TABLE IF EXISTS {$wpdb->prefix}cat_logs;";
        $wpdb->query($oldSql);
    }
}
