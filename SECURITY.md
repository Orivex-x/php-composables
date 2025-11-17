# Security Policy
Security is taken seriously in the PHP Composables project. If you discover a vulnerability, please follow the process below.

## Supported Versions
Only the latest stable release is actively supported with security fixes.

| Version | Supported |
|---------|-----------|
| 1.x     | Active    |
| <1.0    | No        |

## Reporting a Vulnerability
**Do NOT open a public GitHub issue.**

Instead, email the project maintainer directly:
**orivex-x@proton.me**

Include:

- Detailed description of the issue
- Steps to reproduce
- Expected behaviour
- Impact assessment
- Potential fix ideas (optional)

The more detail provided, the faster the triage.

## Disclosure Process

1. Your report is acknowledged within **48 hours**.
2. A fix or mitigation is prepared privately.
3. A CVE may be assigned if appropriate.
4. A patched release is prepared.
5. A public advisory is published **after** affected users have a chance to upgrade.

## Scope

Security concerns may include:

- Remote code execution
- Arbitrary file access
- Privilege escalation within module pipelines
- Unsafe deserialization
- Injection vulnerabilities
- Logic flaws leading to privilege bypass
- Any behaviour that breaks module isolation guarantees

## Out of Scope
- Issues caused by unsupported PHP versions
- Issues caused by third-party libraries not bundled with this project
- Misconfigurations in consumer applications

Thank you for practicing responsible disclosure and helping maintain a secure ecosystem.
