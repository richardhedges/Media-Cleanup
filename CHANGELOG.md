# Changelog
All notable changes to this project will be documented in this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [1.2] - 2019-01-09
### Added
- This changelog.
- A progress bar below the activity log.
- README.md

### Changed
- Modified the search script when searching theme files. This now collates a list of all template files (PHP) and their content, ready for checking if the file contains an attachments URL, while also using regex to check for variations of the `wp_get_attachment_image` method.

## [1.1] - 2019-01-08
### Added
- Search theme files option in admin page.
- Searching attachments in: theme files.

## [1.0] - 2019-01-07
### Added
- Initial WordPress plugin environment setup, including admin menu item and page.
- Searching attachments in: featured images, ACF fields (if the plugin is installed) and post content.
- Deletion of attachents from the website.