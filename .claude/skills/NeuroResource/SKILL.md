```markdown
# NeuroResource Development Patterns

> Auto-generated skill from repository analysis

## Overview
This skill teaches the core development patterns and conventions used in the NeuroResource JavaScript repository. You will learn how to structure files, write imports/exports, follow commit message conventions, and write tests in alignment with the project's standards. This guide is ideal for contributors aiming for consistency and maintainability in NeuroResource.

## Coding Conventions

### File Naming
- Use **PascalCase** for all file names.
  - Example: `UserProfile.js`, `ResourceList.js`

### Import Style
- Use **relative imports** for modules within the project.
  - Example:
    ```javascript
    import { fetchData } from './DataFetcher';
    ```

### Export Style
- Use **named exports** for all modules.
  - Example:
    ```javascript
    // In DataFetcher.js
    export function fetchData() { ... }
    ```

### Commit Messages
- Follow **conventional commit** format.
- Use the `feat` prefix for new features.
- Keep commit messages concise (average ~68 characters).
  - Example:
    ```
    feat: add resource filtering by category
    ```

## Workflows

### Adding a New Feature
**Trigger:** When implementing a new feature in the codebase  
**Command:** `/add-feature`

1. Create a new file using PascalCase (e.g., `NewFeature.js`).
2. Implement your feature using named exports.
3. Import dependencies using relative paths.
4. Write a corresponding test file (`NewFeature.test.js`).
5. Commit your changes using the `feat` prefix and a clear message.
    ```
    feat: implement new feature for resource tagging
    ```

### Writing Tests
**Trigger:** When adding or updating functionality  
**Command:** `/write-test`

1. Create a test file with the pattern `*.test.*` (e.g., `ResourceList.test.js`).
2. Write tests for all exported functions/components.
3. Use the project's preferred (unknown) testing framework.
4. Run tests to ensure correctness before committing.

### Refactoring Code
**Trigger:** When improving or restructuring existing code  
**Command:** `/refactor`

1. Update file names to PascalCase if needed.
2. Ensure all imports are relative and exports are named.
3. Update or add tests as necessary.
4. Commit with a clear message (use `feat` if adding features).

## Testing Patterns

- Test files follow the `*.test.*` naming convention.
  - Example: `DataFetcher.test.js`
- Each exported function/component should have corresponding tests.
- The specific testing framework is not specified; follow existing patterns in the codebase.

## Commands

| Command        | Purpose                                           |
|----------------|--------------------------------------------------|
| /add-feature   | Scaffold and implement a new feature             |
| /write-test    | Create and update tests for modules              |
| /refactor      | Refactor code to align with conventions          |
```
