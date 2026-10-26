# Documentation Index

All project documentation is consolidated under this [docs/](./) folder.

## Core Guides

- [setup.md](./setup.md)  
  Local setup and frontend asset pipeline notes.

- [authentication-guide.md](./authentication-guide.md)  
  Web authentication modes (Laravel/CAS), auth manager usage, impersonation, and external broker settings.

- [api-authentication-guide.md](./api-authentication-guide.md)  
  API authentication and external broker endpoint contracts.

- [user-management.md](./user-management.md)  
  User management and HR integration workflows.

## Architecture

- [modular-architecture.md](./modular-architecture.md)  
  Modular migration phases, module commands, and current runtime architecture.

## Recommended Reading Order

1. [setup.md](./setup.md)
2. [modular-architecture.md](./modular-architecture.md)
3. [authentication-guide.md](./authentication-guide.md)
4. [api-authentication-guide.md](./api-authentication-guide.md)
5. [user-management.md](./user-management.md)

## Documentation Contribution Standard

Use these rules whenever creating or updating project documentation.

### 1) Location and Scope

- Keep project documentation inside this [docs/](./) folder.
- Keep the root [README.md](../README.md) as a short entry point and navigator.
- Add a new document only when the topic cannot fit cleanly into an existing guide.

### 2) File Naming

- Use lowercase kebab-case file names.
- Prefer names that describe domain intent, for example:
  - `authentication-guide.md`
  - `api-authentication-guide.md`
  - `modular-architecture.md`

### 3) Document Structure

- Start with a short purpose statement.
- Use clear heading hierarchy (`#`, `##`, `###`).
- Prefer actionable sections:
  - prerequisites
  - configuration
  - usage
  - troubleshooting
- Include command examples in fenced code blocks with language labels (`bash`, `php`, `env`, etc.).

### 4) Linking Rules

- Use relative links for docs-to-docs references.
- Update links immediately when files are moved or renamed.
- Avoid duplicate content across files; link to the canonical section instead.

### 5) Update Expectations

- When behavior changes, update docs in the same change set.
- If commands, routes, env vars, or paths change, update all affected docs.
- Keep examples aligned with the current module-first architecture (`Modules\\...`).

### 6) Quality Checklist

Before finalizing doc changes, verify:

- links resolve
- commands are accurate
- terminology is consistent
- obsolete or legacy wording is removed
