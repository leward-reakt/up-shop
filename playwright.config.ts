import { defineConfig } from '@playwright/test';

const baseURL = 'http://127.0.0.1:8123';
const databasePath = '$PWD/database/playwright.sqlite';

const applicationEnvironment = [
    'APP_ENV=testing',
    `APP_URL=${baseURL}`,
    'DB_CONNECTION=sqlite',
    `DB_DATABASE="${databasePath}"`,
].join(' ');

export default defineConfig({
    testDir: './tests/Browser',

    fullyParallel: false,
    forbidOnly: true,
    retries: 0,
    workers: 1,

    reporter: 'list',

    outputDir: 'test-results',

    use: {
        baseURL,
        screenshot: 'only-on-failure',
    },

    projects: [
        {
            name: 'desktop-chromium',
            use: {
                browserName: 'chromium',
                viewport: {
                    width: 1440,
                    height: 900,
                },
            },
        },
        {
            name: 'tablet-chromium',
            use: {
                browserName: 'chromium',
                viewport: {
                    width: 1024,
                    height: 768,
                },
                hasTouch: true,
            },
        },
        {
            name: 'mobile-chromium',
            use: {
                browserName: 'chromium',
                viewport: {
                    width: 390,
                    height: 844,
                },
                hasTouch: true,
                isMobile: true,
            },
        },
    ],

    webServer: {
        command: [
            'rm -f database/playwright.sqlite',
            'touch database/playwright.sqlite',
            `${applicationEnvironment} php artisan migrate:fresh --force`,
            `${applicationEnvironment} php artisan db:seed --class=DevelopmentSeeder --force`,
            `${applicationEnvironment} php artisan serve --host=127.0.0.1 --port=8123`,
        ].join(' && '),
        url: baseURL,
        reuseExistingServer: false,
        timeout: 120_000,
    },
});
