<?php

namespace ContextualAltText\Config;

class Constants
{
    // General Plugin Settings
    public const CONTEXTUAL_ALT_TEXT_PLUGIN_OPTIONS_PAGE_SLUG = 'contextual-alt-text-options';
    public const CONTEXTUAL_ALT_TEXT_PLUGIN_OPTION_LOG_PAGE_SLUG = 'contextual_alt_text_log';
    public const CONTEXTUAL_ALT_TEXT_PLUGIN_MEDIA_LIBRARY_HANDLE = 'contextual-alt-text-media-library';
    public const CONTEXTUAL_ALT_TEXT_AJAX_GENERATE_ALT_TEXT_NONCE = 'generate_alt_text_nonce';

    // --- Providers ---
    public const CONTEXTUAL_ALT_TEXT_PROVIDER_OPENAI = 'openai';
    public const CONTEXTUAL_ALT_TEXT_PROVIDER_AZURE_VISION = 'azure_vision';
    public const CONTEXTUAL_ALT_TEXT_PROVIDER_HUGGINGFACE = 'huggingface';

    // --- Option Fields ---

    // Top-level enable/disable
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN = 'cat_enable_plugin';

    // Vision Provider Settings
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_PROVIDER = 'cat_vision_provider';
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_PROMPT = 'cat_vision_prompt';
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_OPENAI_API_KEY = 'cat_vision_openai_api_key';
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_OPENAI_PROMPT = 'cat_vision_openai_prompt';
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_AZURE_API_KEY = 'cat_vision_azure_api_key';
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_AZURE_ENDPOINT = 'cat_vision_azure_endpoint';
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_HUGGINGFACE_API_KEY = 'cat_vision_huggingface_api_key';
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_HUGGINGFACE_MODEL = 'cat_vision_huggingface_model';


    // Text Provider Settings
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_PROVIDER = 'cat_text_provider';
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_OPENAI_API_KEY = 'cat_text_openai_api_key';
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_OPENAI_MODEL = 'cat_text_openai_model';
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_OPENAI_PROMPT = 'cat_text_openai_prompt';
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_HUGGINGFACE_API_KEY = 'cat_text_huggingface_api_key';
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_HUGGINGFACE_MODEL = 'cat_text_huggingface_model';
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_HUGGINGFACE_PROMPT = 'cat_text_huggingface_prompt';


    // Global & Translation Settings
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT = 'cat_preserve_alt_text';
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_LANGUAGE = 'cat_language';
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_AZURE_TRANSLATOR_API_KEY = 'cat_azure_translator_api_key';
    public const CONTEXTUAL_ALT_TEXT_OPTION_FIELD_AZURE_TRANSLATOR_REGION = 'cat_azure_translator_region';


    // --- Default Values & Models ---
    public const CONTEXTUAL_ALT_TEXT_DEFAULT_VISION_PROMPT = 'Describe this image in detail in English.';
    public const CONTEXTUAL_ALT_TEXT_OPENAI_DEFAULT_VISION_PROMPT = 'Describe this image for visually impaired users.';
    public const CONTEXTUAL_ALT_TEXT_OPENAI_DEFAULT_TEXT_PROMPT = 'Based on the following description, write a concise and SEO-friendly alt text:';
    public const CONTEXTUAL_ALT_TEXT_HF_DEFAULT_TEXT_PROMPT = 'Generate a concise alt text for an image with this description. The alt text should be no more than 50 Chinese characters or 120 English/Western characters:';

    // OpenAI Models
    public const CONTEXTUAL_ALT_TEXT_GPT4O = 'gpt-4o';
    public const CONTEXTUAL_ALT_TEXT_GPT4_TURBO = 'gpt-4-turbo';
    public const CONTEXTUAL_ALT_TEXT_GPT35_TURBO = 'gpt-3.5-turbo';

    // HuggingFace Models - Vision (僅保留實際使用的)
    public const CONTEXTUAL_ALT_TEXT_HF_JOY_CAPTION_BETA = 'joy-caption-beta-one';
    public const CONTEXTUAL_ALT_TEXT_HF_BLIP_BASE = 'Salesforce/blip-image-captioning-base';

    // HuggingFace Models - Text (僅保留實際使用的)
    public const CONTEXTUAL_ALT_TEXT_HF_LLAMA31_8B = 'meta-llama/Llama-3.1-8B-Instruct';


    // --- Miscellaneous ---
    public const CONTEXTUAL_ALT_TEXT_SUPPORTED_LANGUAGES = [
        'en' => 'English', 'es' => 'Spanish', 'fr' => 'French', 'de' => 'German', 'it' => 'Italian',
        'pt' => 'Portuguese', 'ru' => 'Russian', 'zh' => 'Chinese', 'ja' => 'Japanese', 'ar' => 'Arabic'
    ];
}
