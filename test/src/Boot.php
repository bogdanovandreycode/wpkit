<?php
namespace ;

class Boot {
    public function __construct(
        private string $pluginFilePath,
    )
    {
        register_activation_hook( $pluginFilePath, 'activate' );
        register_deactivation_hook( $pluginFilePath, 'deactivate' );
        register_install_hook( $pluginFilePath, 'install' );
        register_uninstall_hook( $pluginFilePath, 'uninstall' );
    }

    public static function install() {
        //TODO CODE
    }

    public static function uninstall() {
        //TODO CODE
    }

    public static function activate() {
        //TODO CODE
    }

    public static function deactivate() {
        //TODO CODE
    }
}