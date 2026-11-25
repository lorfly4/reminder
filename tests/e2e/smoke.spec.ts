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

    await expect(page.getByText(`Selamat datang, E2E User`)).toBeVisible();
    await page.getByRole('button', { name: 'Tambah tugas' }).click();
    await page.getByLabel('Judul tugas').fill(`Task E2E ${suffix}`);
    await page.getByRole('button', { name: 'Simpan tugas' }).click();
    await expect(page.getByText(`Task E2E ${suffix}`)).toBeVisible({ timeout: 20_000 });
});
