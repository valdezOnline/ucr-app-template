---
applyTo: "**/*.{js,jsx,ts,tsx,vue,mjs,cjs}"
---
# JavaScript Review Rules

- Correctness: unhandled rejections, missing `await`, races between async calls, stale closures, shared-state mutation, `==` vs `===`.
- Modern syntax: `const`/`let` (no `var`), ES modules, optional chaining, `??`, `structuredClone`, `AbortController` for cancellable requests.
- Network: `fetch` does not reject on HTTP errors, so check `response.ok`. Handle timeouts/cancellation; debounce input-driven requests. State-changing requests must send the CSRF token.
- Security: use `textContent` over `innerHTML`; sanitize any HTML from user input; validate `postMessage` origins; no secrets or privileged logic in client code.
- Performance: avoid layout thrashing, needless re-renders, and long synchronous loops. Clean up listeners, timers, and observers.
- Follow the project's front-end conventions (Vite, Livewire/Alpine, Inertia, Vue/React). Don't add a library when an existing one covers the need.
- Accessibility: semantic elements, labels, keyboard operability, focus management; ARIA only when native elements won't do.
- No leftover `console.log`/`debugger`; consider bundle-size impact of new dependencies.
- Tests cover non-trivial logic; avoid brittle selectors and arbitrary timeouts.
- Code style: follow the project's ESLint/Prettier rules; maintain consistent indentation, spacing, and naming conventions.
- Comments: write meaningful comments for complex logic; avoid obvious or redundant comments.
- Documentation: keep README and inline documentation up to date with code changes; document public APIs and complex modules.
- Error handling: consistently handle errors and edge cases; avoid silent failures and unhandled promise rejections.
- Logging: use structured and meaningful logging; avoid leaking sensitive information in logs.
- Dependency management: keep dependencies up to date; avoid unnecessary or outdated packages; review security advisories for used packages.