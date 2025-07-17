<?php
/**
 * Test file for Alt Text Generation
 * 
 * Usage: Place this file in the plugin directory and access it via:
 * - Local: https://auto-alt-text.ddev.site/wp-content/plugins/contextual-alt-text/test-alt-text.php
 * - ngrok: https://17df7686e917.ngrok-free.app/wp-content/plugins/contextual-alt-text/test-alt-text.php
 */

// Include WordPress
require_once '../../../wp-config.php';

use ContextualAltText\App\AIProviders\HuggingFace\HuggingFaceVision;
use ContextualAltText\App\Logging\DBLogger;

// Detect current URL scheme
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$is_ngrok = strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Alt Text Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .warning { color: orange; }
        textarea { width: 100%; height: 100px; }
        .image-preview { max-width: 300px; max-height: 200px; }
        .url-box { background: #f0f0f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>

<h1>Contextual Alt Text - Test Page</h1>

<div class="test-section info">
    <h3>📍 Access Information</h3>
    <p><strong>Current URL:</strong> <?php echo esc_html($current_url); ?></p>
    <?php if ($is_ngrok): ?>
        <div class="warning">
            <p>⚠️ You are accessing via ngrok. For WordPress admin access, use:</p>
            <div class="url-box">
                <strong>Admin URL:</strong> <a href="<?php echo $current_url; ?>/wp-admin/" target="_blank"><?php echo $current_url; ?>/wp-admin/</a><br>
                <strong>Plugin Settings:</strong> <a href="<?php echo $current_url; ?>/wp-admin/admin.php?page=contextual-alt-text-options" target="_blank">Settings</a>
            </div>
        </div>
    <?php else: ?>
        <div class="info">
            <p>✅ Local access detected</p>
            <div class="url-box">
                <strong>Admin URL:</strong> <a href="/wp-admin/" target="_blank">/wp-admin/</a><br>
                <strong>Plugin Settings:</strong> <a href="/wp-admin/admin.php?page=contextual-alt-text-options" target="_blank">Settings</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php

if (isset($_POST['action']) && $_POST['action'] === 'test_alt_text' && !empty($_POST['image_url'])) {
    $imageUrl = sanitize_url($_POST['image_url']);
    $prompt = sanitize_text_field($_POST['prompt'] ?? '');
    
    echo '<div class="test-section">';
    echo '<h3>Testing Alt Text Generation</h3>';
    echo '<p><strong>Image URL:</strong> ' . esc_html($imageUrl) . '</p>';
    echo '<p><strong>Custom Prompt:</strong> ' . esc_html($prompt) . '</p>';
    
    // Show image preview
    echo '<div><img src="' . esc_url($imageUrl) . '" class="image-preview" alt="Test image"></div>';
    
    // Test the HuggingFace Vision API
    $vision = new HuggingFaceVision();
    
    $startTime = microtime(true);
    $result = $vision->response($imageUrl, $prompt);
    $endTime = microtime(true);
    
    $duration = round(($endTime - $startTime), 2);
    
    if (!empty($result)) {
        echo '<div class="success">';
        echo '<h4>✅ Success! (took ' . $duration . ' seconds)</h4>';
        echo '<p><strong>Generated Alt Text:</strong></p>';
        echo '<textarea readonly>' . esc_textarea($result) . '</textarea>';
        echo '<p><strong>Length:</strong> ' . strlen($result) . ' characters</p>';
        echo '</div>';
    } else {
        echo '<div class="error">';
        echo '<h4>❌ Failed to generate alt text</h4>';
        echo '<p>Check the plugin logs for details.</p>';
        echo '</div>';
    }
    
    echo '</div>';
}

// Show configuration status
echo '<div class="test-section">';
echo '<h3>Plugin Configuration Status</h3>';

$pluginEnabled = get_option('cat_enable_plugin', false);
$apiKey = get_option('cat_vision_huggingface_api_key', '');
$model = get_option('cat_vision_huggingface_model', '');

echo '<p><strong>Plugin Enabled:</strong> ' . ($pluginEnabled ? '✅ Yes' : '❌ No') . '</p>';
echo '<p><strong>HuggingFace API Key:</strong> ' . (!empty($apiKey) ? '✅ Set (' . strlen($apiKey) . ' chars)' : '❌ Not set') . '</p>';
echo '<p><strong>Selected Model:</strong> ' . ($model ? esc_html($model) : 'Default') . '</p>';
echo '</div>';

// Test form
?>

<div class="test-section">
    <h3>Test Alt Text Generation</h3>
    <form method="post">
        <input type="hidden" name="action" value="test_alt_text">
        
        <p>
            <label for="image_url"><strong>Image URL:</strong></label><br>
            <input type="url" name="image_url" id="image_url" style="width: 100%;" 
                   placeholder="https://example.com/image.jpg" 
                   value="<?php echo esc_attr($_POST['image_url'] ?? 'https://raw.githubusercontent.com/gradio-app/gradio/main/test/test_files/bus.png'); ?>">
        </p>
        
        <p>
            <label for="prompt"><strong>Custom Prompt (optional):</strong></label><br>
            <input type="text" name="prompt" id="prompt" style="width: 100%;" 
                   placeholder="Write a detailed description for this image."
                   value="<?php echo esc_attr($_POST['prompt'] ?? ''); ?>">
        </p>
        
        <p>
            <button type="submit">Generate Alt Text</button>
        </p>
    </form>
</div>

<div class="test-section">
    <h3>Sample Test Images</h3>
    <p>Click on any image URL to test:</p>
    <ul>
        <li><a href="#" onclick="document.getElementById('image_url').value='https://raw.githubusercontent.com/gradio-app/gradio/main/test/test_files/bus.png'; return false;">Bus Image (from Gradio examples)</a></li>
        <li><a href="#" onclick="document.getElementById('image_url').value='https://picsum.photos/400/300'; return false;">Random Image (Lorem Picsum)</a></li>
        <li><a href="#" onclick="document.getElementById('image_url').value='https://via.placeholder.com/300x200/0066cc/ffffff?text=Test+Image'; return false;">Simple Test Image</a></li>
    </ul>
</div>

<div class="test-section">
    <h3>Recent Logs</h3>
    <?php
    // Show recent logs
    $logs = DBLogger::make()->getRecentLogs(10);
    if (!empty($logs)) {
        echo '<table border="1" style="width: 100%; border-collapse: collapse;">';
        echo '<tr><th>Time</th><th>Level</th><th>Message</th><th>Context</th></tr>';
        foreach ($logs as $log) {
            $levelClass = $log->level === 'error' ? 'error' : ($log->level === 'success' ? 'success' : 'info');
            echo '<tr class="' . $levelClass . '">';
            echo '<td>' . esc_html($log->created_at) . '</td>';
            echo '<td>' . esc_html($log->level) . '</td>';
            echo '<td>' . esc_html($log->message) . '</td>';
            echo '<td>' . esc_html(substr($log->context, 0, 100)) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p>No logs found.</p>';
    }
    ?>
</div>

<div class="test-section">
    <h3>Setup Instructions</h3>
    <ol>
        <li>Make sure the plugin is activated</li>
        <li>Go to Plugin Settings (link above)</li>
        <li>Enable the plugin</li>
        <li>Enter your HuggingFace API key</li>
        <li>Select "Joy Caption Beta One" as your vision model</li>
        <li>Test image upload in the WordPress block editor</li>
    </ol>
    
    <h4>Troubleshooting</h4>
    <ul>
        <li>If you get 500 errors, check that the plugin dependencies are installed</li>
        <li>If API calls fail, verify your HuggingFace API key is correct</li>
        <li>Check the Recent Logs section above for detailed error information</li>
    </ul>
</div>

</body>
</html> 