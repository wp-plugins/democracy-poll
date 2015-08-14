<?php

function demphp53_notice( $software_name = '', $software_text_domain = '', $notice_cap = '', $notice_hook = '', $notice = ''){
    if(!$software_name) $software_name = 'this software';
    if(!$software_text_domain) // We can build this dynamically.
        $software_text_domain = strtolower(trim(preg_replace('/[^a-z0-9\-]/i', '-', $software_name), '-'));
    if(!$notice_cap) $notice_cap = 'activate_plugins'; // WordPress capability.
    if(!$notice_hook) $notice_hook = 'all_admin_notices'; // Action hook.

    if(!$notice) // Only if there is NOT a custom `$notice` defined already.
    {
        $notice = sprintf('%1$s requires PHP v5.3 (or higher).', $software_name);
        $notice .= ' '.sprintf('You\'re currently running <code>PHP v%1$s</code>.', PHP_VERSION);
        $notice .= ' A simple update is necessary. Please ask your web hosting company to do this for you.';
        $notice .= ' '.sprintf('To remove this message, please deactivate %1$s.', $software_name);
    }
    $notice_handler = create_function('', 'if(current_user_can(\''.str_replace("'", "\\'", $notice_cap).'\'))'.
                                          '  echo \'<div class="error"><p>'.str_replace("'", "\\'", $notice).'</p></div>\';');
    add_action( $notice_hook, $notice_handler );
}

if( version_compare(PHP_VERSION, '5.3', '>=') ) return true;

demphp53_notice('Democracy Poll', '');

return false;