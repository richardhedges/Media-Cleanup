<?php

/*

Plugin Name: Media Cleanup
Plugin URI: https://www.richardhedges.co.uk
Description: A tool to help clean up your website by removing unused media
Version: 1.2
Author: Richard Hedges
Author URI: https://www.richardhedges.co.uk
License: GPL

*/

class MediaCleanup {

	function __construct() {

		add_action('admin_menu', array($this, 'options_menu'));

		add_action('wp_ajax_prepare_cleanup', array($this, 'ajax_prepare_cleanup'));
		add_action('wp_ajax_delete_attachment', array($this, 'ajax_delete_attachment'));

	}

	public function options_menu() {
		add_management_page('Media Cleanup', 'Media Cleanup', 'manage_options', 'media-cleanup', array($this, 'options_page'));
	}

	public function options_page() {
		include(dirname(__FILE__) . '/templates/options.php');
	}

	public function ajax_prepare_cleanup() {

		global $wpdb;

		$rules = $_POST['rules'];

		$featured_images = in_array('featured-images', $rules);
		$post_content = in_array('post-content', $rules);
		$acf_fields = in_array('acf-fields', $rules);
		$theme_files = in_array('theme-files', $rules);

		$attachments = get_children(array(
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'numberposts' => -1,
			'post_status' => null,
			'post_parent' => null,
			'output' => 'object',
			'orderby' => 'post_date',
			'order' => 'desc'
		));

		if ($featured_images) {

			$featured_images_results = $wpdb->get_results("SELECT meta_value AS attachment_id FROM {$wpdb->postmeta}, {$wpdb->posts} WHERE meta_key = '_thumbnail_id' AND {$wpdb->postmeta}.post_id={$wpdb->posts}.ID ORDER BY post_date DESC");

			$featured_images = array();

			foreach ($featured_images_results as $result) {
				$featured_images[] = $result->attachment_id;
			}

		}

		if ($post_content) {

			$posts_results = $wpdb->get_results("SELECT post_content FROM {$wpdb->posts} WHERE post_type != 'attachment' AND post_type != 'revision'");

			$posts = array();

			foreach ($posts_results as $result) {
				$posts[] = $result->post_content;
			}

		}

		if ($acf_fields) {

			$meta_results = $wpdb->get_results("SELECT pm2.meta_value FROM wp_postmeta AS pm1 LEFT JOIN wp_postmeta AS pm2 ON pm2.meta_key = SUBSTRING(pm1.meta_key, 2) WHERE pm1.meta_value LIKE 'field_%' GROUP BY pm1.meta_value");

			$postmeta = array();

			foreach ($meta_results as $result) {
				$postmeta[] = $result->meta_value;
			}

		}

		if ($theme_files) {

			$files = array();
			self::get_directory_files(get_template_directory(), $files, 'php');

			$theme_file_matches = array();

			foreach ($files as $file) {
				$theme_file_matches[] = file_get_contents($file);
			}

		}

		foreach ($attachments as $key => $attachment) {

			if ($featured_images && in_array($attachment->ID, $featured_images)) {
				unset($attachments[$key]);
				continue;
			}

			if ($post_content) {

				$attachment_url = $attachment->guid;

				$matches = array_filter($posts, function($haystack) use ($attachment_url) {
					return (strpos($haystack, $attachment_url));
				});

				if (count($matches) > 0) {
					unset($attachments[$key]);
					continue;
				}

			}

			if ($acf_fields) {

				if (in_array($attachment->ID, $postmeta)) {
					unset($attachments[$key]);
					continue;
				}

			}

			if ($theme_files) {

				foreach ($theme_file_matches as $file) {

					if (strpos($file, $attachment->guid) !== false) {
						unset($attachments[$key]);
						continue;
					}

					preg_match_all("/wp_get_attachment_image(?:_src)?\s*?\(\s*?{$attachment->ID}\s*?(?:,|\))/m", $file, $matches, PREG_SET_ORDER, 0);

					if (count($matches)) {
						unset($attachments[$key]);
						continue;
					}

				}

			}

		}

		echo json_encode(array(
			'attachments' => array_values($attachments)
		));
		exit;

	}

	public function ajax_delete_attachment() {

		$attachment_id = $_POST['attachment_id'];

		echo json_encode(array(
			'success' => wp_delete_attachment($attachment_id, true) ? 'true' : 'false'
		));
		exit;

	}

	public function get_directory_files($directory, &$files = array(), $extension = '*') {

		foreach (scandir($directory) as $key => $file) {

			$path = realpath($directory . DIRECTORY_SEPARATOR . $file);

			if (!is_dir($path)) {

				if ($extension != '*') {
					$pathinfo = pathinfo($path);
				}

				if ($extension == '*' || $pathinfo['extension'] == $extension) {
					$files[] = $path;
				}

			} else if ($file != '.' && $file != '..') {
				self::get_directory_files($path, $files, $extension);
			}

		}

		return $files;

	}

	public function file_contains_string($file, $string) {

		$handle = fopen($file, 'r');
		$found = false;

		while (($buffer = fgets($handle)) !== false) {

			if (strpos($buffer, $string) !== false) {
				$found = true;
				break;
			}

		}

		fclose($handle);

		return $found;

	}

}

new MediaCleanup();