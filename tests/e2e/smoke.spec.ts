import { test, expect } from '@playwright/test';

test('register, view dashboard, and add quick task', async ({ page }) => {
    test.setTimeout(60_000);
    const suffix = Date.now();
    await page.goto('/register');

    await page.getByLabel('Nama').fill('E2E User');
    await page.getByLabel('Email').fill(`e2e-${suffix}@example.com`);
    await page.getByLabel('Password', { exact: true }).fill('password123');
    await page.getByLabel('Konfirmasi Password').fill('password123');
    const registerResponse = page.waitForResponse((response) => response.url().includes('/api/v1/register'));
    await page.getByRole('button', { name: 'Buat akun' }).click();
    const response = await registerResponse;
    expect(response.status()).toBe(200);

    await expect(page.getByRole('heading', { name: 'Halo, E2E User' })).toBeVisible({ timeout: 30_000 });
    await page.getByRole('button', { name: 'Tambah tugas' }).click();
    await page.getByLabel('Judul tugas').fill(`Task E2E ${suffix}`);
    await page.getByRole('button', { name: 'Simpan tugas' }).click();
    await expect(page.getByText(`Task E2E ${suffix}`)).toBeVisible({ timeout: 20_000 });
});

test('desktop navigation opens every menu', async ({ page }) => {
    test.setTimeout(60_000);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/login');
    await page.getByLabel('Email').fill('test@example.com');
    await page.getByLabel('Password', { exact: true }).fill('password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await expect(page.getByRole('heading', { name: /Halo,/ })).toBeVisible({ timeout: 30_000 });

    await page.getByRole('button', { name: 'Tugas', exact: true }).click();
    await expect(page).toHaveURL(/\/tasks$/);
    await expect(page.getByRole('heading', { name: 'Daftar Tugas' })).toBeVisible();

    await page.getByRole('button', { name: 'Kalender', exact: true }).click();
    await expect(page).toHaveURL(/\/calendar$/);
    await expect(page.getByRole('heading', { name: 'Kalender' })).toBeVisible();

    await page.getByRole('button', { name: 'Pengingat', exact: true }).click();
    await expect(page).toHaveURL(/\/reminders$/);
    await expect(page.getByRole('heading', { name: 'Pengingat' })).toBeVisible();

    await page.getByRole('button', { name: 'Pengaturan', exact: true }).click();
    await expect(page).toHaveURL(/\/settings$/);
    await expect(page.getByRole('heading', { name: 'Pengaturan' })).toBeVisible();
});

test('mobile bottom navigation opens every menu', async ({ page }) => {
    test.setTimeout(60_000);
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/login');
    await page.getByLabel('Email').fill('test@example.com');
    await page.getByLabel('Password', { exact: true }).fill('password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await expect(page.getByRole('heading', { name: /Halo,/ })).toBeVisible({ timeout: 30_000 });

    for (const [label, path, heading] of [
        ['Tugas', '/tasks', 'Daftar Tugas'],
        ['Kalender', '/calendar', 'Kalender'],
        ['Pengingat', '/reminders', 'Pengingat'],
        ['Pengaturan', '/settings', 'Pengaturan'],
        ['Dashboard', '/', 'Halo,'],
    ] as const) {
        await page.getByRole('button', { name: label, exact: true }).click();
        await expect(page).toHaveURL(new RegExp(`${path === '/' ? '/$' : path + '$'}`));
        if (heading !== 'Halo,' && heading !== 'Dashboard') {
            await expect(page.getByRole('heading', { name: heading })).toBeVisible();
        }
    }
});
