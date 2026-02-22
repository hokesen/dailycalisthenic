import { Page } from '@playwright/test';

const loginEmail = process.env.PLAYWRIGHT_EMAIL || 'codex.tester.02032026@example.com';
const loginPassword = process.env.PLAYWRIGHT_PASSWORD || 'codex1234';

export async function loginIfNeeded(page: Page) {
  await page.goto('/login');
  if (await page.getByRole('button', { name: /log in/i }).isVisible()) {
    await page.getByLabel('Email').fill(loginEmail);
    await page.getByLabel('Password').fill(loginPassword);
    await page.getByRole('button', { name: /log in/i }).click();
    await page.waitForURL('**/');
  }
}

export async function startPracticeIfNeeded(page: Page) {
  const startButton = page.getByRole('button', { name: /start practice/i });
  if (await startButton.isVisible()) {
    await startButton.click();
  }
}

async function withWorkoutTimerData<T>(
  page: Page,
  callback: (data: any) => T | Promise<T>,
): Promise<T> {
  const dataHandle = await page.evaluateHandle(() => {
    const root = document.querySelector('[x-data^="workoutTimer"]');
    if (!root) {
      throw new Error('workoutTimer root not found');
    }
    const stack = (root as any)._x_dataStack;
    const data =
      (window as any).Alpine?.$data?.(root) ??
      (root as any).__x?.$data ??
      (Array.isArray(stack) ? stack[0] : undefined);
    if (!data) {
      throw new Error('Alpine data not found');
    }
    return data;
  });

  try {
    return await dataHandle.evaluate(callback);
  } finally {
    await dataHandle.dispose();
  }
}

export async function patchWorkoutTimer(page: Page, patch: Record<string, unknown>) {
  await withWorkoutTimerData(page, (data) => Object.assign(data, patch));
}

export async function setWorkoutProgress(
  page: Page,
  patch: Record<string, unknown>,
  { update = true }: { update?: boolean } = {},
) {
  await withWorkoutTimerData(page, (data) => {
    Object.assign(data, patch);
    if (update && typeof data.updateProgress === 'function') {
      data.updateProgress();
    }
  });
}
