<?php

namespace ContextualAltText\App\Admin;

use ContextualAltText\App\Utilities\Encryption;
use ContextualAltText\Config\Constants;

/**
 * Class PluginOptions
 *
 * Handles the plugin's admin options and settings.
 */
class PluginOptions
{
    /**
     * Singleton instance of the PluginOptions class.
     *
     * @var PluginOptions|null
     */
    private static $instance;

    /**
     * Get the singleton instance of the PluginOptions class.
     *
     * @return PluginOptions
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * PluginOptions constructor.
     *
     * Initializes the plugin's admin settings and hooks.
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addOptionsPageToTheMenu']);
        add_action('admin_init', [$this, 'setupPluginOptions']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        if (defined('CONTEXTUAL_ALT_TEXT_PLUGIN_FILE')) {
            add_filter('plugin_action_links_' . plugin_basename(CONTEXTUAL_ALT_TEXT_PLUGIN_FILE), [$this, 'addSettingsLinkToActionLinks']);
        }
    }

    /**
     * Add a settings link to the plugin's action links.
     *
     * @param  array $links Existing plugin action links.
     * @return array Modified plugin action links.
     */
    public function addSettingsLinkToActionLinks($links)
    {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=' . Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG)) . '">' . esc_html__('Settings', 'contextual-alt-text') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Enqueue admin assets for the plugin.
     *
     * @param  string $hook The current admin page hook.
     * @return void
     */
    public function enqueueAssets($hook)
    {
        if ('settings_page_contextual-alt-text-options' !== $hook && 'contextual-alt-text_page_contextual_alt_text_log' !== $hook) {
            return;
        }
        wp_enqueue_script('cat-admin-js', plugins_url('../../../assets/js/admin.js', __FILE__), ['jquery'], '1.0.3', true);
        wp_add_inline_script(
            'cat-admin-js',
            'const cat_plugin_ids = ' . wp_json_encode(
                [
                'enable_plugin'  => Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN,
                'vision_section' => 'cat_section_vision_provider',
                'text_section'   => 'cat_section_text_provider',
                'global_section' => 'cat_section_global',
                ]
            ) . ';',
            'before'
        );
    }

    /**
     * Add the plugin's options page to the WordPress admin menu.
     *
     * @return void
     */
    public static function addOptionsPageToTheMenu(): void
    {
        add_menu_page('Contextual Alt Text', 'Contextual Alt Text', 'manage_options', Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, [self::$instance, 'optionsMainPage'], 'dashicons-format-image', 99);
        add_submenu_page(Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, esc_html__('Settings', 'contextual-alt-text'), esc_html__('Settings', 'contextual-alt-text'), 'manage_options', Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, [self::$instance, 'optionsMainPage']);
        add_submenu_page(Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, esc_html__('Error Log', 'contextual-alt-text'), esc_html__('Error Log', 'contextual-alt-text'), 'manage_options', Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTION_LOG_PAGE_SLUG, [self::$instance, 'logOptionsPage']);
    }

    /**
     * Implement the main option page
     *
     * @return void
     */
    /**
     * Render the main options page for the plugin.
     *
     * @return void
     */
    public static function optionsMainPage(): void
    {
        $plugin_enabled = get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN, false);
        $sections_style = $plugin_enabled ? '' : 'style="display: none;"';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Contextual Alt Text Settings', 'contextual-alt-text'); ?></h1>
            <div style="margin: 1em 0; padding: 1em; background-color: #f7f7f7; border-left: 4px solid #7e8993;">
                <p><strong><?php esc_html_e('How it works:', 'contextual-alt-text'); ?></strong></p>
                <ol>
                    <li><?php esc_html_e('A Vision Model first analyzes the image to generate a detailed description.', 'contextual-alt-text'); ?></li>
                    <li><?php esc_html_e('This description is then sent to a Text Model to create a concise and SEO-friendly alt text.', 'contextual-alt-text'); ?></li>
                    <li><?php esc_html_e('Finally, the alt text is translated if a language other than English is selected.', 'contextual-alt-text'); ?></li>
                </ol>
                <p><?php esc_html_e('To begin, enable the plugin and configure your desired Vision and Text providers below.', 'contextual-alt-text'); ?></p>
            </div>
            <form action="options.php" method="post" id="cat-options-form">
                <?php
                settings_fields(Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG);
                do_settings_sections(Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG);
                submit_button();
                ?>
            </form>
            <script>
            jQuery(document).ready(function($) {
                function toggleSections() {
                    var isEnabled = $('#cat_enable_plugin').is(':checked');
                    var visionSection = $('#cat_section_vision_provider');
                    var textSection = $('#cat_section_text_provider');
                    var globalSection = $('#cat_section_global');
                    var visionTable = visionSection.next('.form-table');
                    var textTable = textSection.next('.form-table');
                    var globalTable = globalSection.next('.form-table');
                    if (isEnabled) {
                        visionSection.show();
                        textSection.show();
                        globalSection.show();
                        visionTable.show();
                        textTable.show();
                        globalTable.show();
                    } else {
                        visionSection.hide();
                        textSection.hide();
                        globalSection.hide();
                        visionTable.hide();
                        textTable.hide();
                        globalTable.hide();
                    }
                }
                toggleSections();
                $('#cat_enable_plugin').on('change', toggleSections);
            });
            </script>
        </div>
        <?php
    }

    /**
     * Set up the plugin options page.
     * This method registers the settings sections and fields for the plugin's options page.
     *
     * @return void
     */
    public static function setupPluginOptions(): void
    {
        // --- General Settings ---
        add_settings_section('cat_section_general', esc_html__('General Settings', 'contextual-alt-text'), null, Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG);
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN, esc_html__('Enable Plugin', 'contextual-alt-text'), [ self::$instance, 'renderFieldEnablePlugin' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_general');
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN,
            [
                'type'    => 'boolean',
                'default' => false,
            ]
        );

        // --- Vision Provider Section ---
        add_settings_section('cat_section_vision_provider', esc_html__('Vision Model', 'contextual-alt-text'), [ self::$instance, 'renderVisionSectionCallback' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG);
        add_settings_field('cat_field_vision_provider_select', esc_html__('Vision Provider', 'contextual-alt-text'), [ self::$instance, 'renderFieldVisionProviderSelect' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_vision_provider');
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_PROVIDER,
            [
                'type'    => 'string',
                'default' => '',
            ]
        );

        // Vision Provider Fields (dynamically shown)
        self::addVisionProviderFields();

        // --- Text Provider Section ---
        add_settings_section('cat_section_text_provider', esc_html__('Text Model', 'contextual-alt-text'), [ self::$instance, 'renderTextSectionCallback' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG);
        add_settings_field('cat_field_text_provider_select', esc_html__('Text Provider', 'contextual-alt-text'), [ self::$instance, 'renderFieldTextProviderSelect' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_text_provider');
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_PROVIDER,
            [
                'type'    => 'string',
                'default' => '',
            ]
        );

        // Text Provider Fields (dynamically shown)
        self::addTextProviderFields();

        // --- Global & Translation Settings ---
        add_settings_section('cat_section_global', esc_html__('Global & Translation', 'contextual-alt-text'), [ self::$instance, 'renderGlobalSectionCallback' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG);
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT, esc_html__('Existing alt text', 'contextual-alt-text'), [ self::$instance, 'renderFieldPreserveAltText' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_global');
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_LANGUAGE, esc_html__('Language of the alt text', 'contextual-alt-text'), [ self::$instance, 'renderFieldLanguage' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_global');
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_AZURE_TRANSLATOR_API_KEY, esc_html__('Azure Translator API Key', 'contextual-alt-text'), [ self::$instance, 'renderFieldAzureTranslatorApiKey' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_global');
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_AZURE_TRANSLATOR_REGION, esc_html__('Azure Translator Region', 'contextual-alt-text'), [ self::$instance, 'renderFieldAzureTranslatorRegion' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_global');
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT,
            [
                'type'    => 'boolean',
                'default' => false,
            ]
        );
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_LANGUAGE,
            [
                'type'    => 'string',
                'default' => 'en',
            ]
        );
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_AZURE_TRANSLATOR_API_KEY,
            [
                'type'              => 'string',
                'sanitize_callback' => [ Encryption::class, 'encryptStatic' ],
            ]
        );
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_AZURE_TRANSLATOR_REGION,
            [
                'type'    => 'string',
                'default' => '',
            ]
        );
    }

    private static function addVisionProviderFields()
    {
        // OpenAI Vision
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_OPENAI_API_KEY, esc_html__('OpenAI API Key', 'contextual-alt-text'), [ self::$instance, 'renderFieldVisionOpenaiApiKey' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_vision_provider', [ 'class' => 'cat-vision-provider-field cat-vision-openai' ]);
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_OPENAI_PROMPT, esc_html__('OpenAI Prompt', 'contextual-alt-text'), [ self::$instance, 'renderFieldVisionOpenaiPrompt' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_vision_provider', [ 'class' => 'cat-vision-provider-field cat-vision-openai' ]);
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_OPENAI_API_KEY,
            [
                'type'              => 'string',
                'sanitize_callback' => [ Encryption::class, 'encryptStatic' ],
            ]
        );
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_OPENAI_PROMPT,
            [
                'type'    => 'string',
                'default' => Constants::CONTEXTUAL_ALT_TEXT_OPENAI_DEFAULT_VISION_PROMPT,
            ]
        );

        // Azure Vision
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_AZURE_API_KEY, esc_html__('Azure CV API Key', 'contextual-alt-text'), [ self::$instance, 'renderFieldVisionAzureApiKey' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_vision_provider', [ 'class' => 'cat-vision-provider-field cat-vision-azure_vision' ]);
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_AZURE_ENDPOINT, esc_html__('Azure CV Endpoint', 'contextual-alt-text'), [ self::$instance, 'renderFieldVisionAzureEndpoint' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_vision_provider', [ 'class' => 'cat-vision-provider-field cat-vision-azure_vision' ]);
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_AZURE_API_KEY,
            [
                'type'              => 'string',
                'sanitize_callback' => [ Encryption::class, 'encryptStatic' ],
            ]
        );
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_AZURE_ENDPOINT,
            [
                'type'    => 'string',
                'default' => '',
            ]
        );

        // Hugging Face Vision
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_HUGGINGFACE_API_KEY, esc_html__('Hugging Face API Key', 'contextual-alt-text'), [ self::$instance, 'renderFieldVisionHuggingfaceApiKey' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_vision_provider', [ 'class' => 'cat-vision-provider-field cat-vision-huggingface' ]);
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_HUGGINGFACE_MODEL, esc_html__('Hugging Face Model', 'contextual-alt-text'), [ self::$instance, 'renderFieldVisionHuggingfaceModel' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_vision_provider', [ 'class' => 'cat-vision-provider-field cat-vision-huggingface' ]);
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_PROMPT, esc_html__('Vision Prompt', 'contextual-alt-text'), [ self::$instance, 'renderFieldVisionPrompt' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_vision_provider', [ 'class' => 'cat-vision-provider-field cat-vision-huggingface' ]);
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_HUGGINGFACE_API_KEY,
            [
                'type'              => 'string',
                'sanitize_callback' => [ Encryption::class, 'encryptStatic' ],
            ]
        );
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_HUGGINGFACE_MODEL,
            [
                'type'    => 'string',
                'default' => Constants::CONTEXTUAL_ALT_TEXT_HF_JOY_CAPTION_BETA,
            ]
        );
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_PROMPT,
            [
                'type'    => 'string',
                'default' => Constants::CONTEXTUAL_ALT_TEXT_DEFAULT_VISION_PROMPT,
            ]
        );
    }

    private static function addTextProviderFields()
    {
        // OpenAI Text
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_OPENAI_API_KEY, esc_html__('OpenAI API Key', 'contextual-alt-text'), [ self::$instance, 'renderFieldTextOpenaiApiKey' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_text_provider', [ 'class' => 'cat-text-provider-field cat-text-openai' ]);
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_OPENAI_MODEL, esc_html__('OpenAI Model', 'contextual-alt-text'), [ self::$instance, 'renderFieldTextOpenaiModel' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_text_provider', [ 'class' => 'cat-text-provider-field cat-text-openai' ]);
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_OPENAI_PROMPT, esc_html__('OpenAI Prompt', 'contextual-alt-text'), [ self::$instance, 'renderFieldTextOpenaiPrompt' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_text_provider', [ 'class' => 'cat-text-provider-field cat-text-openai' ]);
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_OPENAI_API_KEY,
            [
                'type'              => 'string',
                'sanitize_callback' => [ Encryption::class, 'encryptStatic' ],
            ]
        );
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_OPENAI_MODEL,
            [
                'type'    => 'string',
                'default' => Constants::CONTEXTUAL_ALT_TEXT_GPT4O,
            ]
        );
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_OPENAI_PROMPT,
            [
                'type'    => 'string',
                'default' => Constants::CONTEXTUAL_ALT_TEXT_OPENAI_DEFAULT_TEXT_PROMPT,
            ]
        );

        // Hugging Face Text
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_HUGGINGFACE_API_KEY, esc_html__('Hugging Face API Key', 'contextual-alt-text'), [ self::$instance, 'renderFieldTextHuggingfaceApiKey' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_text_provider', [ 'class' => 'cat-text-provider-field cat-text-huggingface' ]);
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_HUGGINGFACE_MODEL, esc_html__('Hugging Face Model', 'contextual-alt-text'), [ self::$instance, 'renderFieldTextHuggingfaceModel' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_text_provider', [ 'class' => 'cat-text-provider-field cat-text-huggingface' ]);
        add_settings_field(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_HUGGINGFACE_PROMPT, esc_html__('Hugging Face Prompt', 'contextual-alt-text'), [ self::$instance, 'renderFieldTextHuggingfacePrompt' ], Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG, 'cat_section_text_provider', [ 'class' => 'cat-text-provider-field cat-text-huggingface' ]);
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_HUGGINGFACE_API_KEY,
            [
                'type'              => 'string',
                'sanitize_callback' => [ Encryption::class, 'encryptStatic' ],
            ]
        );
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_HUGGINGFACE_MODEL,
            [
                'type'    => 'string',
                'default' => Constants::CONTEXTUAL_ALT_TEXT_HF_LLAMA31_8B,
            ]
        );
        register_setting(
            Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG,
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_HUGGINGFACE_PROMPT,
            [
                'type'    => 'string',
                'default' => Constants::CONTEXTUAL_ALT_TEXT_HF_DEFAULT_TEXT_PROMPT,
            ]
        );
    }

    // --- Section Callbacks ---

    public function renderVisionSectionCallback()
    {
        $plugin_enabled = get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN, false);
        if (! $plugin_enabled) {
            echo '<style>#cat_section_vision_provider { display: none !important; } #cat_section_vision_provider + .form-table { display: none !important; }</style>';
        }
    }

    public function renderTextSectionCallback()
    {
        $plugin_enabled = get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN, false);
        if (! $plugin_enabled) {
            echo '<style>#cat_section_text_provider { display: none !important; } #cat_section_text_provider + .form-table { display: none !important; }</style>';
        }
    }

    public function renderGlobalSectionCallback()
    {
        $plugin_enabled = get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN, false);
        if (! $plugin_enabled) {
            echo '<style>#cat_section_global { display: none !important; } #cat_section_global + .form-table { display: none !important; }</style>';
        }
    }

    // --- Render Methods ---

    public function renderFieldEnablePlugin()
    {
        $option = get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN);
        ?>
<input type="checkbox" id="<?php echo esc_attr(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN); ?>" name="<?php echo esc_attr(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN); ?>" value="1" <?php checked(1, $option, true); ?> />
<label for="<?php echo esc_attr(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN); ?>"><?php esc_html_e('Enable automatic alt text generation.', 'contextual-alt-text'); ?></label>
        <?php
    }

    // Vision Provider Select
    public function renderFieldVisionProviderSelect()
    {
        $option = get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_PROVIDER);
        ?>
        <select id="cat_vision_provider" name="<?php echo esc_attr(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_PROVIDER); ?>" onchange="toggleVisionFields()">
            <option value=""><?php esc_html_e('-- None --', 'contextual-alt-text'); ?></option>
<option value="<?php echo esc_attr(Constants::CONTEXTUAL_ALT_TEXT_PROVIDER_OPENAI); ?>" <?php selected($option, Constants::CONTEXTUAL_ALT_TEXT_PROVIDER_OPENAI); ?>>OpenAI</option>
<option value="<?php echo esc_attr(Constants::CONTEXTUAL_ALT_TEXT_PROVIDER_AZURE_VISION); ?>" <?php selected($option, Constants::CONTEXTUAL_ALT_TEXT_PROVIDER_AZURE_VISION); ?>>Azure Computer Vision</option>
<option value="<?php echo esc_attr(Constants::CONTEXTUAL_ALT_TEXT_PROVIDER_HUGGINGFACE); ?>" <?php selected($option, Constants::CONTEXTUAL_ALT_TEXT_PROVIDER_HUGGINGFACE); ?>>Hugging Face</option>
        </select>
<script>
jQuery(document).ready(function($) {
    function toggleVisionFields() {
        var selectedProvider = $('#cat_vision_provider').val();
        $('.cat-vision-provider-field').hide();
        $('.cat-vision-' + selectedProvider).show();
    }
    toggleVisionFields();
    $('#cat_vision_provider').on('change', toggleVisionFields);
});
</script>
        <?php
    }

    // Text Provider Select
    public function renderFieldTextProviderSelect()
    {
        $option = get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_PROVIDER);
        ?>
        <select id="cat_text_provider" name="<?php echo esc_attr(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_PROVIDER); ?>" onchange="toggleTextFields()">
<option value=""><?php esc_html_e('-- None --', 'contextual-alt-text'); ?></option>
<option value="<?php echo esc_attr(Constants::CONTEXTUAL_ALT_TEXT_PROVIDER_OPENAI); ?>" <?php selected($option, Constants::CONTEXTUAL_ALT_TEXT_PROVIDER_OPENAI); ?>>OpenAI</option>
<option value="<?php echo esc_attr(Constants::CONTEXTUAL_ALT_TEXT_PROVIDER_HUGGINGFACE); ?>" <?php selected($option, Constants::CONTEXTUAL_ALT_TEXT_PROVIDER_HUGGINGFACE); ?>>Hugging Face</option>
        </select>
<script>
jQuery(document).ready(function($) {
    function toggleTextFields() {
        var selectedProvider = $('#cat_text_provider').val();
        $('.cat-text-provider-field').hide();
        $('.cat-text-' + selectedProvider).show();
    }
    toggleTextFields();
    $('#cat_text_provider').on('change', toggleTextFields);
});
</script>
        <?php
    }

    // --- RENDER METHODS FOR VISION FIELDS ---
    public function renderFieldVisionOpenaiApiKey()
    {
        $this->renderEncryptedTextInput(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_OPENAI_API_KEY);
    }
    public function renderFieldVisionOpenaiPrompt()
    {
        $this->renderTextarea(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_OPENAI_PROMPT, Constants::CONTEXTUAL_ALT_TEXT_OPENAI_DEFAULT_VISION_PROMPT);
    }
    public function renderFieldVisionAzureApiKey()
    {
        $this->renderEncryptedTextInput(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_AZURE_API_KEY);
    }
    public function renderFieldVisionAzureEndpoint()
    {
        $this->renderTextInput(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_AZURE_ENDPOINT);
    }
    public function renderFieldVisionHuggingfaceApiKey()
    {
        $this->renderEncryptedTextInput(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_HUGGINGFACE_API_KEY);
    }
    public function renderFieldVisionHuggingfaceModel()
    {
        $this->renderSelect(
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_HUGGINGFACE_MODEL,
            [
                Constants::CONTEXTUAL_ALT_TEXT_HF_JOY_CAPTION_BETA => 'Joy Caption Beta One (推薦)',
                Constants::CONTEXTUAL_ALT_TEXT_HF_BLIP_BASE => 'Salesforce BLIP Base',
            ],
            Constants::CONTEXTUAL_ALT_TEXT_HF_JOY_CAPTION_BETA
        );
    }
    public function renderFieldVisionPrompt()
    {
        $this->renderTextarea(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_PROMPT, Constants::CONTEXTUAL_ALT_TEXT_DEFAULT_VISION_PROMPT);
    }

    // --- RENDER METHODS FOR TEXT FIELDS ---
    public function renderFieldTextOpenaiApiKey()
    {
        $this->renderEncryptedTextInput(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_OPENAI_API_KEY);
    }
    public function renderFieldTextOpenaiModel()
    {
        $this->renderSelect(
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_OPENAI_MODEL,
            [
                Constants::CONTEXTUAL_ALT_TEXT_GPT4O       => 'GPT-4o',
                Constants::CONTEXTUAL_ALT_TEXT_GPT4_TURBO  => 'GPT-4 Turbo',
                Constants::CONTEXTUAL_ALT_TEXT_GPT35_TURBO => 'GPT-3.5 Turbo',
            ],
            Constants::CONTEXTUAL_ALT_TEXT_GPT4O
        );
    }
    public function renderFieldTextOpenaiPrompt()
    {
        $this->renderTextarea(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_OPENAI_PROMPT, Constants::CONTEXTUAL_ALT_TEXT_OPENAI_DEFAULT_TEXT_PROMPT);
    }
    public function renderFieldTextHuggingfaceApiKey()
    {
        $this->renderEncryptedTextInput(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_HUGGINGFACE_API_KEY);
    }
    public function renderFieldTextHuggingfaceModel()
    {
        $this->renderSelect(
            Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_HUGGINGFACE_MODEL,
            [
                Constants::CONTEXTUAL_ALT_TEXT_HF_LLAMA31_8B => 'Meta Llama 3.1 8B Instruct (推薦)',
            ],
            Constants::CONTEXTUAL_ALT_TEXT_HF_LLAMA31_8B
        );
    }
    public function renderFieldTextHuggingfacePrompt()
    {
        $this->renderTextarea(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_HUGGINGFACE_PROMPT, Constants::CONTEXTUAL_ALT_TEXT_HF_DEFAULT_TEXT_PROMPT);
    }


    // --- RENDER METHODS FOR GLOBAL & TRANSLATION FIELDS ---
    public function renderFieldPreserveAltText()
    {
        $option = get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT);
        ?>
<input type="checkbox" id="<?php echo esc_attr(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT); ?>" name="<?php echo esc_attr(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT); ?>" value="1" <?php checked(1, $option, true); ?> />
<label for="<?php echo esc_attr(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT); ?>"><?php esc_html_e('Do not overwrite existing alt text.', 'contextual-alt-text'); ?></label>
        <?php
    }

    public function renderFieldLanguage()
    {
        $this->renderSelect(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_LANGUAGE, Constants::CONTEXTUAL_ALT_TEXT_SUPPORTED_LANGUAGES, 'en');
    }

    public function renderFieldAzureTranslatorApiKey()
    {
        $this->renderEncryptedTextInput(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_AZURE_TRANSLATOR_API_KEY);
    }
    public function renderFieldAzureTranslatorRegion()
    {
        $this->renderTextInput(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_AZURE_TRANSLATOR_REGION);
    }


    // --- Generic Render Helpers ---
    private function renderTextInput($name, $default = '')
    {
        $option = get_option($name, $default);
        echo '<input type="text" name="' . esc_attr($name) . '" value="' . esc_attr($option) . '" class="regular-text">';
    }

    private function renderEncryptedTextInput($name)
    {
        $option = get_option($name);
        $encryption = new Encryption();
        $value = !empty($option) ? $encryption->decrypt($option) : '';
        echo '<input type="password" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" class="regular-text">';
    }

    private function renderTextarea($name, $default = '')
    {
        $option = get_option($name, $default);
        echo '<textarea name="' . esc_attr($name) . '" rows="5" cols="50" class="large-text">' . esc_textarea($option) . '</textarea>';
    }

    private function renderSelect($name, $choices, $default = '')
    {
        $option = get_option($name, $default);
        echo '<select name="' . esc_attr($name) . '">';
        foreach ($choices as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($option, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }

    // --- Log Page ---
    public static function logOptionsPage(): void
    {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Error Log', 'contextual-alt-text'); ?></h1>
            <p><?php esc_html_e('This page displays the recent logs from the Contextual Alt Text plugin.', 'contextual-alt-text'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('clear_logs', 'clear_logs_nonce'); ?>
                <input type="submit" name="clear_logs" class="button button-secondary" value="<?php esc_attr_e('Clear All Logs', 'contextual-alt-text'); ?>" 
                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to clear all logs?', 'contextual-alt-text'); ?>');">
            </form>
            
            <?php
            // Handle clear logs action
            if (isset($_POST['clear_logs']) && wp_verify_nonce($_POST['clear_logs_nonce'], 'clear_logs')) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'contextual_alt_text_logs';
                $wpdb->query("TRUNCATE TABLE $table_name");
                echo '<div class="notice notice-success"><p>' . esc_html__('All logs have been cleared.', 'contextual-alt-text') . '</p></div>';
            }
            
            // Get logs using DBLogger
            $logger = \ContextualAltText\App\Logging\DBLogger::make();
            $log_output = $logger->getImageLog();
            
            if (!empty($log_output)) {
                echo '<div style="background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-top: 20px; font-family: monospace; white-space: pre-wrap; max-height: 500px; overflow-y: auto;">';
                echo esc_html($log_output);
                echo '</div>';
            } else {
                echo '<div class="notice notice-info"><p>' . esc_html__('No logs found.', 'contextual-alt-text') . '</p></div>';
            }
            ?>
        </div>
        <?php
    }

    // --- Static Getter Methods for AI Providers ---

    /**
     * Get HuggingFace API key for both vision and text providers
     *
     * @return string
     */
    public static function apiKeyHuggingFace(): string
    {
        // Try text provider first, then vision provider
        $text_key = get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_HUGGINGFACE_API_KEY, '');
        if (!empty($text_key)) {
            $encryption = new Encryption();
            return $encryption->decrypt($text_key);
        }
        
        $vision_key = get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_HUGGINGFACE_API_KEY, '');
        if (!empty($vision_key)) {
            $encryption = new Encryption();
            return $encryption->decrypt($vision_key);
        }
        
        return '';
    }

    /**
     * Get selected language
     *
     * @return string
     */
    public static function selectedLanguage(): string
    {
        return get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_LANGUAGE, 'en');
    }

    /**
     * Get Azure Translator language (legacy)
     *
     * @return string
     */
    public static function languageAzureTranslateInstance(): string
    {
        // For backward compatibility, return empty string to disable translation
        // since we're using text models that generate in the target language directly
        return '';
    }

    /**
     * Get vision provider prompt
     *
     * @return string|null
     */
    public static function visionProviderPrompt(): ?string
    {
        // Return a simple default prompt since we don't have this setting configured
        return 'Describe this image in detail in English.';
    }

    /**
     * Check if contextual awareness is enabled
     *
     * @return bool
     */
    public static function is_contextual_awareness_enabled(): bool
    {
        // Enable contextual awareness by default to use post title, content, and terms
        // when generating alt text
        return get_option('cat_enable_contextual_awareness', true);
    }
}
