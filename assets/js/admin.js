jQuery(document).ready(function($) {

    // Direct selectors - more reliable than relying on PHP variables
    const enableCheckbox = $('#cat_enable_plugin');
    
    // Find all sections by their headers and corresponding tables
    const visionSection = $('h2:contains("Vision Model")').next('.form-table');
    const textSection = $('h2:contains("Text Model")').next('.form-table'); 
    const globalSection = $('h2:contains("Global & Translation")').next('.form-table');
    
    // Also include the section headers themselves
    const visionHeader = $('h2:contains("Vision Model")');
    const textHeader = $('h2:contains("Text Model")');
    const globalHeader = $('h2:contains("Global & Translation")');

    // Group all elements that should be hidden when plugin is disabled
    const settingsToToggle = visionSection.add(textSection).add(globalSection)
        .add(visionHeader).add(textHeader).add(globalHeader);

    /**
     * Toggles the visibility of provider-specific fields within a section (Vision or Text).
     */
    function toggleProviderFields(providerType) {
        const selectedProvider = $('#cat_' + providerType + '_provider').val();
        
        // Hide all fields for this provider type first
        $('.cat-' + providerType + '-provider-field').closest('tr').hide();
        
        // Show fields for the currently selected provider
        if (selectedProvider) {
            $('.cat-' + providerType + '-' + selectedProvider).closest('tr').show();
        }
    }

    /**
     * Toggles the main settings sections based on the 'Enable Plugin' checkbox.
     */
    function toggleMainSettings() {
        console.log('toggleMainSettings called, checkbox checked:', enableCheckbox.is(':checked'));
        
        if (enableCheckbox.is(':checked')) {
            console.log('Showing settings sections');
            settingsToToggle.show();
            // After showing the main sections, ensure the correct provider fields are visible
            toggleProviderFields('vision');
            toggleProviderFields('text');
        } else {
            console.log('Hiding settings sections');
            settingsToToggle.hide();
        }
    }

    // --- Event Listeners ---

    // Toggle main settings when the enable checkbox changes
    enableCheckbox.on('change', function() {
        console.log('Enable checkbox changed to:', $(this).is(':checked'));
        toggleMainSettings();
    });

    // Toggle provider fields when dropdowns change
    $('#cat_vision_provider').on('change', function() {
        if (enableCheckbox.is(':checked')) {
            toggleProviderFields('vision');
        }
    });

    $('#cat_text_provider').on('change', function() {
        if (enableCheckbox.is(':checked')) {
            toggleProviderFields('text');
        }
    });

    // --- Initial Run ---
    console.log('Page loaded, running initial setup');
    console.log('Enable checkbox found:', enableCheckbox.length > 0);
    console.log('Vision section found:', visionSection.length > 0);
    console.log('Text section found:', textSection.length > 0);
    console.log('Global section found:', globalSection.length > 0);
    
    // Set the initial state of the page on load
    toggleMainSettings();

});
