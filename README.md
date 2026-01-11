# Neatly - A Sophisticated Task & Reminder Management System

Neatly is a robust, full-stack application engineered to streamline task organization and cleaning rituals. Developed as a technical showcase, this project demonstrates the synergy between **Laravel 11**, **Vue 3**, and **Inertia.js 2.0**, emphasizing modern architectural patterns and real-time state synchronization.

The application serves as a comprehensive exploration of the VILT/TALL stack philosophy, prioritizing clean code, separation of concerns, and native-like responsiveness. By leveraging a centralized backend for logic and a reactive frontend for interactivity, Neatly provides a professional solution for persistent task management.

---

## 🛠️ Built With

Neatly is architected using industry-standard technologies to ensure performance, maintainability, and scalability.

| Layer | Technology | Version | Key Utility |
| :--- | :--- | :--- | :--- |
| **Backend** | [Laravel](https://laravel.com) | 11.x | Core framework & API |
| **Frontend** | [Vue.js](https://vuejs.org) | 3.4+ | Composition API & Script Setup |
| **Bridge** | [Inertia.js](https://inertiajs.com) | 2.0 | Seamless SPA bridge |
| **Styling** | [Tailwind CSS](https://tailwindcss.com) | 3.x | Utility-first design system |
| **Database** | [SQLite](https://www.sqlite.org) | Portability | Embedded database engine |
| **Real-time**| [Laravel Reverb](https://reverb.laravel.com) | 1.x | WebSocket communication |
| **Mobile** | [Capacitor](https://capacitorjs.com) | 8.x | Native bridging (Notifications) |

---

## 🏗️ Architecture & Design Decisions

### Architectural Overview
Neatly utilizes a **Hybrid Single-Page Application (SPA)** architecture. Unlike traditional SPAs that communicate via REST/GraphQL, Neatly leverages **Inertia.js** to use the Laravel server as the primary router and state provider, drastically reducing boilerplate while maintaining a highly reactive user experience.

### Key Design Patterns
-   **Service Pattern**: Business logic is encapsulated within specific services (e.g., `App\Services\TaskService`). This ensures that controllers remain lean and focus solely on request handling and response coordination.
-   **Event-Driven Reactivity**: The application employs an event-driven model. Backend mutations (Create/Update/Delete) trigger **Laravel Events** (e.g., `TaskCreated`), which are broadcast via **Laravel Reverb**. The frontend listens to these broadcasts using **Laravel Echo**, maintaining a consistent UI state across multiple devices in real-time.
-   **Component-Driven Frontend**: The UI is built using modular, highly reusable Vue components located in `resources/js/Components`. This promotes **Don't Repeat Yourself (DRY)** principles and simplifies UI testing.

### Recruiter & Technical Highlights
-   **Separation of Concerns**: Rigorous distinction between data persistence, business logic, and UI representation.
-   **Type Safety**: Extensive use of **TypeScript** on the frontend to catch potential errors during development and improve IDE support.
-   **Native Integration**: The inclusion of **Capacitor** demonstrates cross-platform readiness, specifically utilizing the `@capacitor/local-notifications` plugin for OS-level engagement.

---

## 🚀 Getting Started

### Prerequisites
Ensure your development environment meets the following minimum requirements:
-   **PHP**: 8.2 or higher
-   **Node.js**: 20.x or higher
-   **Composer**: 2.x
-   **Git**

### Installation

1.  **Clone the Repository**
    ```bash
    git clone https://github.com/your-username/neatly.git
    cd neatly
    ```

2.  **Backend Configuration**
    ```bash
    composer install
    cp .env.example .env
    php artisan key:generate
    ```

3.  **Database Strategy**
    Neatly uses SQLite for its simplicity in local development.
    ```bash
    touch database/database.sqlite
    php artisan migrate --seed
    ```

4.  **Frontend Compilation**
    ```bash
    npm install
    ```

5.  **Execution**
    Run the development environment using the bundled orchestrator:
    ```bash
    composer dev
    ```
    > [!IMPORTANT]
    > The `composer dev` command concurrently executes `php artisan serve`, `npm run dev`, and `php artisan reverb:start`.

---

## 💻 Usage & Implementation

### Real-time Task Synchronization
When a task is updated via the `TaskController`, an event is dispatched to notify all connected clients:

```php
// app/Http/Controllers/TaskController.php
public function update(Request $request, Task $task) {
    // ... validation and persistence ...
    $task->update($validated);
    \App\Events\TaskUpdated::dispatch($task);
    return redirect()->back();
}
```

On the frontend, the `Index.vue` page listens for these updates to maintain state:

```typescript
// resources/js/Pages/Tasks/Index.vue
window.Echo.private(`tasks.${authUser.id}`)
    .listen('TaskUpdated', (e: any) => {
        const index = localTasks.value.findIndex(t => t.id === e.task.id);
        if (index !== -1) localTasks.value[index] = e.task;
    });
```

---

## 🗺️ Roadmap

-   [ ] **Comprehensive Testing Suite**: Implement unit and feature tests using **Pest**.
-   [ ] **Caching Layer**: Integrate **Redis** for optimized task retrieval and session management.
-   [ ] **Advanced Analytics**: User dashboard for tracking task completion velocity.

---

## 📄 License & Contact

Distributed under the **MIT License**.

-   **Developer**: Geoff Kevin G. Apologista
-   **Email**: gkapologista0800@gmail.com
-   **LinkedIn**: https://www.linkedin.com/in/gkapologista/
