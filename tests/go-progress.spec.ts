import { test, expect } from '@playwright/test';
import { loginIfNeeded, startPracticeIfNeeded, setWorkoutProgress } from './playwright-utils';

const goPath = process.env.GO_PATH || '/go?template=9';

test('go edge progress starts at top center and completes full rotation', async ({ page }) => {
  await loginIfNeeded(page);
  await page.goto(goPath);

  // Start the workout so the progress frame is visible.
  await startPracticeIfNeeded(page);

  // Main progress stroke is the direct rect with dashoffset.
  const progressStroke = page.locator('div.pointer-events-none svg > rect[stroke-dashoffset]').first();
  await expect(progressStroke).toBeVisible();

  // Compute the dashoffset needed to start at top-center.
  const geometry = await progressStroke.evaluate((el) => {
    if (!(el instanceof SVGGeometryElement)) {
      throw new Error('Progress stroke is not an SVGGeometryElement');
    }
    const x = parseFloat(el.getAttribute('x') || '0');
    const y = parseFloat(el.getAttribute('y') || '0');
    const width = parseFloat(el.getAttribute('width') || '0');
    const target = { x: x + width / 2, y };
    const total = el.getTotalLength();

    let best = { length: 0, dist: Infinity };
    const steps = 2000;
    for (let i = 0; i <= steps; i += 1) {
      const length = (total * i) / steps;
      const point = el.getPointAtLength(length);
      const dx = point.x - target.x;
      const dy = point.y - target.y;
      const dist = Math.hypot(dx, dy);
      if (dist < best.dist) {
        best = { length, dist };
      }
    }

    const dashoffset = parseFloat(el.getAttribute('stroke-dashoffset') || '0');
    return {
      total,
      target,
      dashoffset,
      idealDashoffset: -best.length,
      distanceToTarget: best.dist,
    };
  });

  expect(geometry.distanceToTarget).toBeLessThan(0.5);
  expect(geometry.dashoffset).toBeCloseTo(geometry.idealDashoffset, 0);

  // Force progress to a tiny value and then to complete for full rotation.
  await setWorkoutProgress(page, {
    currentSegmentMs: 1000,
    remainingMs: 990,
    totalElapsedMs: 10,
    timeRemaining: 990,
    state: 'running',
  });

  const dashStart = await progressStroke.getAttribute('stroke-dasharray');
  expect(dashStart).toContain('1');

  await setWorkoutProgress(page, {
    currentSegmentMs: 1000,
    remainingMs: 0,
    totalElapsedMs: 1000,
    timeRemaining: 0,
    state: 'running',
  });

  const dashEnd = await progressStroke.getAttribute('stroke-dasharray');
  expect(dashEnd).toContain('100');
});
