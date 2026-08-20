import { test, expect } from '@playwright/test';

const BASE_URL = 'http://127.0.0.1:8001';

test.describe('Login', () => {

  test('login berhasil sebagai staff gudang', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);

    await page.getByRole('textbox', { name: 'Email address' }).fill('staff.gudang@erp.test');
    await page.getByRole('textbox', { name: 'Password' }).fill('password');
    await page.locator('[data-test="login-button"]').click();

    // TODO: sesuaikan dengan URL/elemen yang muncul setelah login berhasil
    // Contoh sementara berdasarkan rekaman: ada link "Customer" yang muncul
    await expect(page.getByRole('link', { name: 'Customer' })).toBeVisible();
  });

  test('login berhasil sebagai staff finance dengan remember me', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);

    await page.getByRole('textbox', { name: 'Email address' }).fill('staff.finance@erp.test');
    await page.getByRole('textbox', { name: 'Password' }).fill('password');
    await page.getByRole('checkbox', { name: 'Remember me' }).check();
    await page.locator('[data-test="login-button"]').click();

    // TODO: sesuaikan assertion sesuai halaman tujuan staff finance
    await expect(page).not.toHaveURL(/.*login/);
  });

  test('login gagal dengan password salah', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);

    await page.getByRole('textbox', { name: 'Email address' }).fill('staff.gudang@erp.test');
    await page.getByRole('textbox', { name: 'Password' }).fill('passwordSalah');
    await page.locator('[data-test="login-button"]').click();

    // TODO: sesuaikan dengan pesan error asli yang muncul di app kamu
    // await expect(page.getByText('Kredensial tidak valid')).toBeVisible();
    await expect(page).toHaveURL(/.*login/);
  });

  test('tombol show/hide password berfungsi', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);

    const passwordField = page.getByRole('textbox', { name: 'Password' });
    await passwordField.fill('password');

    // Defaultnya password disembunyikan
    await expect(passwordField).toHaveAttribute('type', 'password');

    await page.getByRole('button', { name: 'Show password' }).click();
    await expect(passwordField).toHaveAttribute('type', 'text');

    await page.getByRole('button', { name: 'Show password' }).click();
    await expect(passwordField).toHaveAttribute('type', 'password');
  });

});