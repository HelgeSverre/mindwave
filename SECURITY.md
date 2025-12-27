# Security Policy

## Supported Versions

We provide security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 2.x     | :white_check_mark: |
| 1.x     | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

**Please DO NOT create public GitHub issues for security vulnerabilities.**

### How to Report

Email security reports to: **helge.sverre@gmail.com**

Include:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

### What to Expect

- **Acknowledgment**: Within 48 hours
- **Initial assessment**: Within 5 business days
- **Fix timeline**: Depends on severity
  - Critical: 1-3 days
  - High: 1-2 weeks
  - Medium: 2-4 weeks
  - Low: Next release

### Disclosure Policy

- We will coordinate disclosure with you
- We prefer coordinated disclosure after fix is released
- You will be credited in release notes (if desired)

### Security Best Practices

When using Mindwave:

1. **API Keys**: Never commit API keys to version control
2. **PII Protection**: Enable `capture_messages: false` in production
3. **Rate Limiting**: Implement rate limiting for public-facing LLM endpoints
4. **Input Validation**: Sanitize user input before sending to LLMs
5. **Cost Controls**: Set budget limits in config

## Known Security Considerations

- **LLM Prompt Injection**: User input should be validated
- **Token Costs**: Implement rate limiting to prevent abuse
- **API Key Exposure**: Use environment variables, never hardcode
- **Tracing PII**: Disable message capture in production

## Security Updates

Subscribe to releases to be notified of security updates:
https://github.com/helgesverre/mindwave/releases
