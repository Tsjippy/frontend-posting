# Changelog
## [Unreleased] - yyyy-mm-dd

### Added

### Changed

### Fixed

### Updated

## [10.4.4] - 2026-06-26


### Changed
- pass sanitized request to tsjippy-frontend-content-after-post-save
- sanitize post on original function
- print close buttont in funtion

### Fixed
- non-prefixd post meta's

## [10.4.3] - 2026-06-25


### Added
- tsjippy-frontend-posting-allowed-to-edit to filter post edit rights

### Changed
- change post type on select
- wrapped front-end options in a collapsible
- options in collapsible wrappers
- html update

## [10.4.2] - 2026-06-24


## [10.4.1] - 2026-06-23


## [10.4.0] - 2026-06-23


### Changed
- modal close button

## [10.3.9] - 2026-06-23


## [10.3.8] - 2026-06-23


## [10.3.7] - 2026-06-21


## [10.3.6] - 2026-06-20


## [10.3.5] - 2026-06-19


### Added
- request sanitazion

## [10.3.4] - 2026-06-18


### Changed
- hook and filter name update
- prefix all hooks with plugin name

### Fixed
- empty index bug

## [10.3.3] - 2026-06-15


## [10.3.2] - 2026-06-15


## [10.3.1] - 2026-06-15


## [10.3.0] - 2026-06-15


## [10.2.9] - 2026-06-13


## [10.2.8] - 2026-06-13


### Changed
- prefix meta key in get_users

### Fixed
- shared code loader
- activation hook
- use correct shortcodes on auto created pages

## [10.2.7] - 2026-06-11


### Added
- placeholder for textdomain
- user, post and rest_meta prefixing

### Changed
- prefixed post metas and shortcodes
- use named arguments for fileuploadhtml

### Fixed
- prefix meta_query

## [10.2.6] - 2026-06-09


### Added
- shared functionality loader

### Changed
- comply to coding standards
- code layout
- namespaced all constants
- use wp_kses_post to keep html
- sanitize all posts and get vars
- js updated
- moved inline style to scss file

### Fixed
- spacing problem
- space before dot bug

## [10.2.5] - 2026-06-03


### Added
- echo escaping

### Changed
- addSaveButton with echo param

### Fixed
- attachment page template

## [10.2.4] - 2026-06-01


### Changed
- merged hooks.md into readme.md

### Fixed
- added domain to __ function
- bug

## [10.2.3] - 2026-06-01


### Fixed
- insert media contents

## [10.2.2] - 2026-06-01


### Changed
- use named params for userSelect function

## [10.2.1] - 2026-05-30


### Changed
- do not store get_plugin_data in global variable

## [10.2.0] - 2026-05-29


### Added
- wp_unslash

## [10.1.9] - 2026-05-28


### Fixed
- admin menu bug

## [10.1.8] - 2026-05-28


### Fixed
- empty post stus bug

## [10.1.7] - 2026-05-28


### Fixed
- ?? bug

## [10.1.6] - 2026-05-27


### Fixed
- empty post bug

## [10.1.5] - 2026-05-22


### Fixed
- bugs

## [10.1.4] - 2026-05-14


### Changed
- date( to gmdate(

## [10.1.3] - 2026-05-12


### Fixed
- store frontend page id properly

## [10.1.1] - 2026-05-12


### Changed
- permission callback for rest api

## [10.1.0] - 2026-05-11


## [10.0.9] - 2026-05-11


### Added
- cancel button in popup

### Changed
- removed pclzip as it is already in wp
- removed admin login for cron

### Updated
- css

## [10.0.7] - 2026-05-08


### Fixed
- post deletion on deactivation

## [10.0.6] - 2026-05-08


### Fixed
- account page retrieval
- edit button

## [10.0.5] - 2026-05-08


### Fixed
- error in rest api

## [10.0.4] - 2026-05-07


### Changed
- replaced sweetalert

## [10.0.3] - 2026-05-06


## [10.0.2] - 2026-05-05


### Fixed
- admin menu

## [10.0.1.2] - 2026-05-04


### Fixed
- plugin settings

## [10.0.1.1] - 2026-05-04


### Added
- redirection to settings page on plugin activation

### Changed
- main plugin name from sim-base to tsjippy-shared-functionality
- module to plugin
- base namespace to TSJIPPY
- filternames to include tsjippy
- block apt to version 3
- PLUGINCONSTANT value
- lib updates
- recurrence selector code
- exclude .vscode from releases
- updated github workflow versions
- removed the redirection at activation as it is done by the share plugin
- use shared github workflows

## [8.3.3] - 2026-01-30


### Changed
- no edit button in mailchimp campaigns

## [8.3.2] - 2026-01-12


### Fixed
- old content warnings

## [8.3.1] - 2025-12-01


### Changed
- lib update

## [8.3.0] - 2025-11-04


### Changed
- clearer data attributes

## [8.2.9] - 2025-11-03


### Changed
- stop listening to events if we have a match

## [8.2.8] - 2025-10-31


### Changed
- render loader image using js

## [8.2.7] - 2025-10-30


### Changed
- less hooks

## [8.2.6] - 2025-10-25


### Changed
- code cleanup

### Fixed
- deleting posts

## [8.2.5] - 2025-10-16


### Fixed
- loading edit page

## [8.2.4] - 2025-10-14


### Added
- edit buttons on nested posts

## [8.2.3] - 2025-10-13


### Changed
- classnames
- data attribute names
- pages maintenance
- dataset names
- class name

### Fixed
- bugs

## [8.2.2] - 2025-10-06


### Changed
- in-button loader

## [8.2.1] - 2025-09-26


### Changed
- classnames replace _ with -

## [8.2.0] - 2025-09-25


## [8.1.9] - 2025-09-24


## [8.1.8] - 2025-09-24


### Changed
- new loader image

### Fixed
- nice selects

## [8.1.7] - 2025-08-27


### Added
- page filtering

## [8.1.6] - 2025-08-08


### Fixed
- issue when updating post title
- new post message

## [8.1.5] - 2025-08-06


### Changed
- less niceselect code

## [8.1.4] - 2025-08-04


### Fixed
- add categories

## [8.1.3] - 2025-08-01


### Fixed
- edit posts

## [8.1.2] - 2025-07-30


### Fixed
- bug with encoding issues

## [8.1.1] - 2025-05-08


### Changed
- removed signal messaging

## [8.1.0] - 2025-03-21


## [8.0.9] - 2025-02-13


### Changed
- module hooks now include module slug

## [8.0.8] - 2025-02-11


### Changed
- sim_module_updated filter to new format
- use site date and time format

## [8.0.7] - 2025-02-09


### Added
- open pending post in new tab

## [8.0.6] - 2025-02-04


### Fixed
- save tinymce plain text value before submitting the post

## [8.0.5] - 2025-02-04


### Added
- support for inline script over AJAX

## [8.0.4] - 2024-11-22


### Changed
- removed anonymous functions

## [8.0.3] - 2024-11-18


### Changed
- removed anomhynous functions

## [8.0.2] - 2024-10-17


### Changed
- readme

### Fixed
- global css reference
- issue with tinymce

### Updated
- blocks

## [8.0.1] - 2024-10-11


### Changed
- deps
- redering of asset urls

## [8.0.0] - 2024-10-04


## [8.0.0] - 2024-10-03
