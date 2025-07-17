# Contextual Alt Text

> **Fork Notice:** This is a fork of the original "Auto Alt Text" WordPress plugin by [Valerio Monti](https://www.vmweb.it).
>
> - **Original Repository:** https://github.com/valeriomonti/auto-alt-text
> - **Fork Maintainer:** [Fuyuan Cheng](https://github.com/gloomcheng)
> - **Current Version:** 3.0.0 (fork version)
> - **License:** GPL v3 (unchanged)
>
> This fork maintains full compatibility with the original plugin while preserving all original author attributions as required by GPL v3.

This advanced WordPress plugin automatically generates Alt Text for images uploaded to your media library using cutting-edge AI technology. Choose from multiple AI providers including OpenAI, Azure, and HuggingFace for cost-effective, multilingual alt text generation with full contextual awareness.

## ✨ New Features in v3.0.0

### 🚀 Advanced HuggingFace AI Integration

- **Joy Caption Beta One**: State-of-the-art vision model for superior image analysis
- **Llama 3.1 8B**: Advanced text model for contextual alt text refinement
- **Two-Stage Processing**: Vision model analyzes image → Text model creates contextual description
- **Cost-Effective**: Free and affordable alternative to OpenAI/Azure
- **Multi-language Generation**: Direct generation in target language without translation

### 🧠 Enhanced Contextual Awareness

- **Smart Context Analysis**: Automatically considers post title, content, categories, and tags
- **Relevance-Focused**: Alt text reflects the actual context of where the image appears
- **Team Introduction Example**: For a team page, describes images in relation to team context
- **SEO Optimized**: Better search engine relevance through contextual descriptions

### 🔧 Bulk Generator Tool

- **Post-Based Processing**: Generate alt text for all images in a specific post
- **Force Regenerate**: Option to override existing alt text
- **Real-time Progress**: Visual progress tracking with detailed logs
- **Asynchronous Processing**: Prevents timeouts for large image sets
- **Context-Aware**: Uses post content for all images in that post

### 🌍 Enhanced Multilingual Support

- **15+ Languages**: Including Traditional Chinese, Simplified Chinese, Japanese, Korean, and European languages
- **Direct Generation**: AI generates alt text directly in target language (no translation needed)
- **Length Limitations**: Automatic character limits (50 for CJK languages, 120 for others)
- **Complete Localization**: Full Traditional Chinese (zh_TW) interface

### ⚙️ Advanced Configuration

- **Flexible Providers**: Separate Text and Vision model configurations
- **Multiple API Keys**: Individual management for different providers
- **Smart Detection**: Context awareness from post editor, media library, and bulk generator
- **Auto-Cleanup**: Removes AI-generated formatting prefixes and quotes automatically

## 🎯 Features

This plugin supports multiple alt text generation methods:

### AI-Powered Methods

- **HuggingFace APIs** (Joy Caption Beta One + Llama 3.1) - Advanced contextual analysis (Recommended)
- **OpenAI APIs** (GPT-4o, GPT-4o Mini, o1 Mini) - Premium AI vision models
- **Azure Computer Vision** - Enterprise-grade image analysis with translation support

### Non-AI Methods

- **Article Title** - Uses the title of the article containing the image
- **Attachment Title** - Uses the filename/title of the image itself

## 🛠️ Prerequisites

- **PHP**: >= 7.4
- **WordPress**: >= 6.0
- **Node.js**: 18+ (for development)
- **Composer**: For dependency management
- **npm**: For asset building (development)

## 🚀 Installation

### For WordPress Users

1. **Download the plugin** from GitHub releases
2. **Upload to WordPress:**
   - Go to WordPress Admin → Plugins → Add New → Upload Plugin
   - Choose the zip file and install
3. **Activate the plugin** in WordPress admin
4. **Configure** in Settings → Contextual Alt Text Options

### For Developers

1. **Clone the repository:**

```bash
git clone git@github.com:gloomcheng/contextual-alt-text.git
cd contextual-alt-text
```

2. **Install dependencies:**

```bash
composer install
npm install
npm run build
```

3. **Copy to WordPress:**
   - Copy the entire folder to `/wp-content/plugins/contextual-alt-text/`
   - Activate in WordPress admin

## 🔧 Configuration

### HuggingFace Setup (Recommended)

1. Visit [HuggingFace](https://huggingface.co/settings/tokens)
2. Sign up for a free account
3. Create a new access token with "Read" permission
4. Enter the token in plugin settings
5. Select "Joy Caption Beta One" for vision model
6. Select "Llama 3.1 8B" for text model

### OpenAI Setup

1. Visit [OpenAI API](https://platform.openai.com/api-keys)
2. Create an account and add billing information
3. Generate an API key
4. Enter the key in plugin settings

### Azure Computer Vision Setup

1. Create an Azure account and Computer Vision resource
2. Get your API key and endpoint
3. Optionally set up Azure Translator for multilingual support
4. Configure in plugin settings

## 💡 How It Works

### Two-Stage AI Processing (HuggingFace)

1. **Vision Analysis**: Joy Caption Beta One analyzes the image content
2. **Contextual Refinement**: Llama 3.1 combines image description with post context
3. **Smart Output**: Generates alt text relevant to both image and surrounding content
4. **Auto-Cleanup**: Removes formatting prefixes and ensures clean output

### Automatic Generation

Once configured, alt text is automatically generated when uploading images. The AI considers:

- **Image Content**: Visual analysis of the actual image
- **Post Context**: Title, content, categories, and tags of the containing post
- **Language Preference**: Direct generation in your selected language
- **Character Limits**: Automatic length optimization

### Bulk Generation

For existing images:

1. **Go to Contextual Alt Text → Bulk Generator**
2. **Enter Post ID** you want to process
3. **Choose Force Regenerate** to override existing alt text (optional)
4. **Click "Start Processing"**
5. **Monitor Progress** in real-time with detailed logs

### Individual Generation

For single images:

1. Open Media Library (grid view)
2. Select an image
3. Click "Generate alt text" button
4. Alt text updates instantly

## 🌐 Language Support

The plugin supports direct alt text generation in 15+ languages:

- **English** (en)
- **Traditional Chinese** (zh-tw) 繁體中文
- **Simplified Chinese** (zh) 简体中文
- **Japanese** (ja) 日本語
- **Korean** (ko) 한국어
- **Spanish** (es)
- **French** (fr)
- **German** (de)
- **Italian** (it)
- **Portuguese** (pt)
- **Russian** (ru)
- **Arabic** (ar)
- **Hindi** (hi)
- **Thai** (th)
- **Vietnamese** (vi)

### Character Limits

- **CJK Languages** (Chinese, Japanese, Korean): 50 characters
- **Other Languages**: 120 characters

## 🔒 Security & Encryption

### Enhanced API Key Protection

We strongly recommend defining plugin-specific encryption constants in your `wp-config.php`:

```php
define( 'CONTEXTUAL_ALT_TEXT_ENCRYPTION_KEY',  'a_random_string_of_at_least_64_characters' );
define( 'CONTEXTUAL_ALT_TEXT_ENCRYPTION_SALT', 'another_random_string_of_at_least_64_characters' );
```

These constants ensure your API keys are securely encrypted in the database. Without these constants, the plugin will use WordPress default encryption which may be less secure.

## 📊 Debug Logging

The plugin includes comprehensive debug logging:

- **Debug Logs**: View detailed processing logs in Contextual Alt Text → Debug Logs
- **Context Tracking**: See what context information is collected for each image
- **API Responses**: Monitor API calls and responses
- **Error Handling**: Failed API calls are logged with full details
- **No Disruption**: Images upload successfully even if alt text generation fails

## ⚠️ Important Notes

### Performance Considerations

- **Two-Stage Processing**: May take longer but provides higher quality results
- **API Methods**: Upload times may increase due to external API calls
- **Timeout Protection**: Asynchronous processing prevents WordPress timeouts
- **Fallback**: Images upload successfully even if alt text generation fails

### Context Requirements

- **Post Context**: Best results when images are uploaded to existing posts/pages
- **Draft Posts**: Save posts as draft before uploading images for optimal context
- **Bulk Processing**: Use Bulk Generator for processing images in existing posts

## 🔄 Migration from Original Plugin

This fork maintains full compatibility with the original plugin. Your existing settings and API keys will continue to work without any changes.

## 🆘 Troubleshooting

### Common Issues

1. **API Key Errors**: Check the debug logs for specific API responses
2. **Missing Context**: Use Bulk Generator to process images with proper post context
3. **Format Issues**: Plugin automatically cleans AI-generated prefixes and quotes
4. **Timeout Issues**: Large images processed asynchronously to prevent timeouts

### Debug Information

- **Check Debug Logs**: Contextual Alt Text → Debug Logs
- **Context Information**: Look for "Context information collected" entries
- **API Responses**: Monitor "generated contextual alt text" entries
- **Error Details**: Review any error entries for specific issues

### Getting Help

- Check the debug logs in plugin settings
- Verify API keys are correctly entered
- Ensure billing is set up for paid APIs (OpenAI, Azure)
- Test with smaller images first
- Use Bulk Generator for better context awareness

## 📄 License

This plugin is licensed under GPL v3, maintaining the same license as the original work. You are free to use, modify, and distribute this plugin under the terms of the GPL v3 license.

## 🙏 Credits

- **Original Author**: [Valerio Monti](https://www.vmweb.it)
- **Original Repository**: https://github.com/valeriomonti/auto-alt-text
- **Fork Maintainer**: [Fuyuan Cheng](https://github.com/gloomcheng)

This fork adds significant enhancements while maintaining full respect for the original author's work and GPL v3 license requirements.

## 🚀 Key Improvements in This Fork

- **Advanced Contextual Awareness**: Analyzes post content, title, categories, and tags for relevant alt text
- **Joy Caption Beta One Integration**: State-of-the-art vision model for superior image understanding
- **Two-Stage AI Processing**: Vision + Text model pipeline for optimal results
- **Bulk Generator Tool**: Process multiple images with proper context awareness
- **Multi-Provider Support**: Choose between HuggingFace, OpenAI, and Azure based on your needs
- **Enhanced Security**: Improved API key encryption and secure storage
- **Comprehensive Logging**: Detailed debug information for troubleshooting
- **Auto-Cleanup**: Intelligent removal of AI-generated formatting artifacts
- **Developer Friendly**: Hooks, filters, and extensible architecture for customization
