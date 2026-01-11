# Neatly Development Rules

> **Source of Truth for AI-Assisted Development**
> This document defines the coding standards, architecture patterns, and workflow rules derived from the existing codebase.

---

## 1. Project Context

**Neatly** is a task management and cleaning schedule application built as a technical mastery project for the **Laravel + Vue.js (Inertia.js)** ecosystem. It demonstrates full-stack development with real-time synchronization and cross-platform mobile capabilities.

---

## 2. Tech Stack Constraints

### Core Stack Reference

| Component         | Technology               | Version    |
| :---------------- | :----------------------- | :--------- |
| **Backend**       | Laravel                  | `^11.31`   |
| **Frontend**      | Vue.js                   | `^3.4`     |
| **PHP**           | PHP                      | `^8.2`     |
| **Bridge**        | Inertia.js               | `^2.0`     |
| **Styling**       | Tailwind CSS             | `^3.2`     |
| **Database**      | SQLite                   | (Default)  |
| **Real-time**     | Laravel Reverb + Echo    | `^1.0`     |
| **Mobile**        | Capacitor                | `^8.0`     |
| **Build Tool**    | Vite                     | `^6.0`     |

### Key Dependencies
-   **Authentication**: `laravel/sanctum ^4.0`
-   **Route Generation**: `tightenco/ziggy ^2.0`
-   **Formatting**: `laravel/pint ^1.13`
-   **HTTP Client**: `axios ^1.7`

### ✅ DO
-   Use **Carbon** for all date/time manipulation in PHP.
-   Use **Tailwind CSS** utility classes for all styling.
-   Use **Inertia.js** for page routing and SPA-like transitions.
-   Use **Laravel Echo** with **Reverb** for real-time features.

### ❌ DO NOT
-   **DO NOT** use `moment.js` or `dayjs` on the frontend. Use native `Date` or minimal alternatives.
-   **DO NOT** write custom CSS unless extending the Tailwind theme.
-   **DO NOT** install Vuex or Pinia. Use Inertia props and local `ref()` state.
-   **DO NOT** create API routes for features that should use Inertia.

---

## 3. Coding Style

### PHP (Backend)
-   **Standard**: Follow **PSR-12**. Use `./vendor/bin/pint` for formatting.
-   **Type Hinting**: Type-hint all method parameters and return types.
-   **Eloquent**: Use `$fillable` arrays for mass assignment protection. Use `$casts` for attribute casting.
-   **Inline Validation**: Perform validation directly in controller actions using `$request->validate([...])`.
-   **Authorization**: Inline ownership checks are currently used (e.g., `$task->user_id !== Auth::id()`). For complex rules, consider policies.

### Vue.js / TypeScript (Frontend)
-   **Syntax**: Use the **Composition API** with `<script setup lang="ts">`.
-   **Type Safety**: Define `props` with explicit TypeScript interfaces using `defineProps<T>()`.
-   **State Management**: No external store (Pinia/Vuex). Use local `ref()` for component state and Inertia props for page-level data.
-   **Watchers**: Use `watch()` to synchronize Inertia props with local reactive state for real-time updates.
-   **TypeScript Strictness**: `tsconfig.json` has `strict: true` enabled.

### Formatting (`.editorconfig`)
-   **Indentation**: 4 spaces for all files (except YAML: 2 spaces).
-   **Line Endings**: `lf` (Unix).
-   **Trailing Whitespace**: Trimmed (except for `.md` files).
-   **Final Newline**: Always insert.

### Styling (CSS)
-   **Approach**: Use **Tailwind utility classes** exclusively. Avoid custom CSS unless extending theme.
-   **Forms**: `@tailwindcss/forms` plugin is active.
-   **Font**: `Figtree` as the primary sans-serif font.

---

## 4. Architectural Patterns

### Backend Architecture
```
app/
├── Events/          # Domain events (TaskCreated, TaskUpdated, TaskDeleted)
├── Http/
│   └── Controllers/ # Resource controllers (TaskController)
├── Models/          # Eloquent models (User, Task)
├── Providers/       # Service providers
└── Services/        # Business logic (TaskService for seeding default tasks)
```

-   **Controllers**: Standard resource controllers. Business logic that isn't request-specific should be extracted to `Services/`.
-   **Events**: CRUD actions dispatch events for real-time broadcasting via Reverb.

### Frontend Architecture
```
resources/js/
├── Components/      # Reusable UI components (Modal, PrimaryButton, TextInput)
├── Layouts/         # Page layouts (AuthenticatedLayout, GuestLayout)
├── Pages/           # Inertia pages, organized by domain (e.g., Pages/Tasks/)
├── types/           # Shared TypeScript types
├── app.ts           # Inertia application entry point
└── echo.js          # Laravel Echo initialization
```

-   **Page Props**: Data is passed from controllers via `Inertia::render()` and accessed via `defineProps<T>()`.
-   **Real-time Updates**: Pages subscribe to Echo channels on `onMounted()` and update local `ref()` state.

### Building a New Feature
1.  **Migration**: `php artisan make:migration create_[feature]_table`
2.  **Model**: `php artisan make:model [Feature]`
3.  **Controller**: `php artisan make:controller [Feature]Controller --resource`
4.  **Route**: Register in `routes/web.php` using `Route::resource()`.
5.  **Events** (if real-time): Create events in `app/Events/`.
6.  **Vue Page**: Create `resources/js/Pages/[Feature]/Index.vue`.

---

## 5. Testing Mandates

### Testing Framework
-   **Framework**: **PHPUnit** (`^11.0.1`)
-   **Location**: `tests/Feature/` and `tests/Unit/`

### Rules
-   Every new feature **MUST** have at least one Feature test.
-   Run `php artisan test` before committing any code.
-   Use `RefreshDatabase` trait for tests that modify the database.

### Commands
```bash
# Run the full test suite
php artisan test

# Run a specific test file
php artisan test --filter=TaskTest
```

---

## 6. Workflow Rules

### Development Server
```bash
# Start all services concurrently (Server, Queue, Logs, Vite)
composer dev
```

### Linting / Formatting
```bash
# PHP (PSR-12)
./vendor/bin/pint

# Vue/TypeScript (Type Checking)
npx vue-tsc
```

### Database
```bash
# Create SQLite file
touch database/database.sqlite

# Migrations
php artisan migrate

# Fresh start with seeding
php artisan migrate:fresh --seed
```

---

## 7. Naming Conventions

| Context              | Convention          | Example              |
| :------------------- | :------------------ | :------------------- |
| Database Columns     | `snake_case`        | `is_completed`       |
| Model Properties     | `snake_case`        | `$task->user_id`     |
| PHP Variables        | `camelCase`         | `$taskId`            |
| Vue Components       | `PascalCase`        | `PrimaryButton.vue`  |
| Vue Component Props  | `camelCase`         | `isModalOpen`        |
| TypeScript Types     | `PascalCase`        | `TaskType`           |
| Routes               | `kebab-case`        | `/tasks`             |
