# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - YYYY-MM-DD

### Added
- Full PHP 8.x+ compatibility with type declarations
- Improved error handling with try/catch blocks
- Stronger property typing with nullable types
- Security improvements for plural form handling
- Comprehensive examples and demo files
- Unit tests for verifying functionality
- Composer support

### Changed
- Updated constructor methods to use `__construct()`
- Better property visibility (protected instead of public where appropriate)
- Refactored `select_string()` method to reduce eval() usage
- Improved documentation with better examples

### Fixed
- Compatibility issues with PHP 8.0+
- Security vulnerabilities in plural form handling
- Error reporting in FileReader class 