<?php
/**
 * Plugin Name: module naming file import
 * Plugin URI:
 * Description: Gestion de l'import par fichier
 * Version: 0.1
 * Requires PHP: 8.1
 * Author: Benoit DELBOE & Grégory COLLIN
 * Author URI:
 * Licence: GPLv2
 */
add_action('plugins_loaded', 'D4rk0snet\NamingFileImport\Plugin::launchActions');
