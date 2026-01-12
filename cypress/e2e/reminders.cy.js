describe('Task Reminders', () => {
    beforeEach(() => {
        // Mock authentication if possible or just register/login via UI
        // Since we don't have a factory-based login helper easily accessible from here without setup, 
        // we will do a UI login flow or assume a seeder.
        // For robustness, I'll register a new user for each test run to avoid state issues.

        cy.visit('/register');
        const email = `test${Math.floor(Math.random() * 10000)}@example.com`;
        cy.get('#name').type('Test User');
        cy.get('#email').type(email);
        cy.get('#password').type('password');
        cy.get('#password_confirmation').type('password');
        cy.contains('Register').click();

        // Assert we are on dashboard
        cy.url().should('include', '/dashboard');
        cy.visit('/tasks');
    });

    it('can schedule a task with a reminder', () => {
        cy.contains('+ New Task').click();

        cy.get('input[placeholder="e.g. Wash the car"]').type('Test Scheduled Task');

        // Future time string for input
        const now = new Date();
        now.setMinutes(now.getMinutes() + 20); // 20 mins from now
        const futureTime = now.toISOString().slice(0, 16);

        // We might need to select the input by index or label if multiple exist, 
        // but Create Modal is unique here.
        // The label is "Schedule Reminder (Optional)"
        cy.contains('input-label', 'Schedule Reminder (Optional)', { includeShadowDom: true }) // Adjust if needed
        // Simpler: Find the label content, go up to label, then find next input
        cy.contains('Schedule Reminder (Optional)').parents('div.mt-4').find('input[type="datetime-local"]')
            .type(futureTime);

        cy.contains('Add Task').click();

        // Verification: Check if it appears in the list with the time
        cy.contains('Test Scheduled Task');
        // The format produced by toLocaleString might vary by locale, but let's check for the time part roughly
        // or just check that the icon/text exists now.
        cy.contains('Test Scheduled Task').parents('.group').within(() => {
            cy.get('svg.text-orange-500').should('exist'); // The clock icon color class we added
        });
    });

    it('triggers notification logic (simulated)', () => {
        // Stub Notification API persists across reloads
        const notificationStub = cy.stub().as('notificationConstructor');

        cy.on('window:before:load', (win) => {
            win.Notification = class Notification {
                constructor(title, options) {
                    notificationStub(title, options);
                }
                static requestPermission() {
                    return Promise.resolve('granted');
                }
                static get permission() {
                    return 'granted';
                }
            };
        });

        // We need to manipulate the clock or create a task that is due NOW.
        // Let's create a task due in 1 minute.

        cy.contains('+ New Task').click();
        cy.get('input[placeholder="e.g. Wash the car"]').type('Immediate Task');

        const now = new Date();
        now.setMinutes(now.getMinutes() + 1);
        const dueTime = now.toISOString().slice(0, 16);

        cy.contains('Schedule Reminder (Optional)').parents('div.mt-4').find('input[type="datetime-local"]')
            .type(dueTime);

        cy.contains('Add Task').click();

        // Wait for the polling interval (we set 30s in code, but init check + 2s timeout)
        // We can wait 2.5s to catch the "Initial check" inside onMounted
        cy.wait(4000);

        // Since we just created it, the "upcoming_tasks" prop needs to refresh.
        // Inertia might not refresh props automatically unless we trigger a visit or poll.
        // However, our logic relies on `page.props` which is updated on page loads.
        // The `setInterval` inside layout reads `page.props.upcoming_tasks`. 
        // DOES Inertia update `page.props` in background? NO.
        // We need to reload the page to get the fresh props with the new task.

        cy.reload();

        // Just reload, re-stub notification (handled by window:before:load), and wait for the "Initial check" (2s timeout).
        cy.reload();

        // Wait for polling (2s timeout + buffer)
        cy.wait(4000);

        cy.get('@notificationConstructor').should('be.called');
    });
});
