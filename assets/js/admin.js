function fetchPluginDetails(pluginFile) {
    jQuery.ajax({
        url: ajaxurl,
        method: 'POST',
        data: {
            action: 'get_plugin_details',
            plugin: pluginFile
        },
        success: function(response) {
            const data = JSON.parse(response);
            document.getElementById('plugin-functions').innerHTML = data.functions;
            document.getElementById('plugin-classes').innerHTML = data.classes;
            document.getElementById('plugin-text-domain').innerHTML = data.text_domain;
        }
    });
}

function replaceName(pluginFile, oldName, newName, type) {
    jQuery.ajax({
        url: ajaxurl,
        method: 'POST',
        data: {
            action: 'replace_name',
            plugin: pluginFile,
            old_name: oldName,
            new_name: newName,
            type: type
        },
        success: function(response) {
            alert(response);
            // Refresh the plugin details to reflect the changes
            fetchPluginDetails(pluginFile);
        }
    });
}
