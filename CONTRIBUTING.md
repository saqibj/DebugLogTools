# Contributing to Debug Log Tools

Thank you for your interest in contributing to Debug Log Tools! This document provides guidelines and instructions for contributing to the project.

## Development Environment

- PHP 7.4 or higher
- WordPress 5.0 or higher
- Composer (for development dependencies)
- PHPUnit (for running tests)

## Coding Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Use proper PHPDoc blocks for all functions and classes
- Follow PSR-4 autoloading standards
- Use strict type declarations
- Follow WordPress security best practices

## Development Workflow

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests and ensure they pass
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

## Testing

- Run PHPUnit tests: `vendor/bin/phpunit`
- Run PHPCS: `vendor/bin/phpcs`
- Run PHPStan: `vendor/bin/phpstan`

## Documentation

- Update README.md for new features
- Add inline documentation for new functions
- Update CHANGELOG.md for all changes
- Add/update unit tests for new features

## Security

- Report security vulnerabilities to security@saqibj.com
- Do not include sensitive information in commits
- Follow WordPress security best practices
- Use proper escaping and sanitization

## Pull Request Process

1. Update the README.md with details of changes
2. Update the CHANGELOG.md with details of changes
3. Ensure tests pass
4. Request review from maintainers

## Code Review Process

- All pull requests require review
- Maintainers will review within 48 hours
- Address all review comments
- Ensure all tests pass before merging

## License

By contributing, you agree that your contributions will be licensed under the GPL-3.0 license. 